<?php

namespace App\Jobs;

use App\Models\Opportunity;
use App\Services\OpenAiOpportunityDiscoveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DiscoverOpportunitiesViaOpenAi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * A hard safety ceiling regardless of what's requested — see the
     * artisan command, which also clamps to this before dispatching. Kept
     * here too as a defensive floor in case this job is ever dispatched
     * directly.
     */
    public const MAX_LIMIT = 20;

    /**
     * How many opportunities to ask OpenAI for per call. One large request
     * risks a slow/failed connection losing everything it already found;
     * fetching in small batches means a failure only costs that batch, and
     * each completed batch is safe before the next one starts.
     */
    private const BATCH_SIZE = 5;

    /**
     * Bump whenever OpenAiOpportunityDiscoveryService's output shape
     * changes. This cache key is date + limit only (not a content hash),
     * so without a version a same-day schema change would silently serve
     * an incompatible cached candidate list to a later run.
     */
    private const CACHE_VERSION = 2;

    public int $tries = 1;

    /**
     * Generous on purpose: this runs in the background with nobody
     * watching a spinner, and up to 4 batches (20 ÷ 5) each with their own
     * rate-limit backoff can genuinely take several minutes in the worst
     * case. IMPORTANT: DB_QUEUE_RETRY_AFTER in .env must stay comfortably
     * above this number, or the queue will consider the job "lost" and let
     * a second worker pick it up mid-run — duplicating every OpenAI call.
     */
    public int $timeout = 1800;

    public function __construct(
        private readonly int $limit,
        private readonly bool $fresh = false,
    ) {}

    public function handle(OpenAiOpportunityDiscoveryService $service): void
    {
        $limit = min($this->limit, self::MAX_LIMIT);

        // Cached by day + limit so repeat runs on the same day (e.g. while
        // testing locally) don't pay for a repeat web-search call.
        $cacheKey = 'openai-opportunity-discovery:v'.self::CACHE_VERSION.':'.now()->toDateString().':'.$limit;

        if ($this->fresh) {
            Cache::forget($cacheKey);
        }

        $candidates = Cache::remember(
            $cacheKey,
            now()->addHours(6),
            fn () => $this->fetchInBatches($service, $limit),
        );

        foreach ($candidates as $candidate) {
            // One bad record (a save error, an unexpected shape) must never
            // abort the rest of the batch — everything else OpenAI already
            // found and we already paid for is still worth keeping.
            try {
                $this->upsert($candidate);
            } catch (Throwable $exception) {
                Log::error('OpenAI opportunity discovery: failed to save a candidate, skipping it.', [
                    'name' => $candidate['name'] ?? null,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * Fetches up to $limit opportunities in batches of BATCH_SIZE, telling
     * each later batch what earlier batches already found so OpenAI isn't
     * asked (and paid) to redescribe the same notice — and stops as soon as
     * a batch turns up nothing genuinely new, since further batches are
     * very unlikely to do better.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchInBatches(OpenAiOpportunityDiscoveryService $service, int $limit): array
    {
        $collected = [];
        $seen = [];

        while (count($collected) < $limit) {
            $batchSize = min(self::BATCH_SIZE, $limit - count($collected));

            $batch = $service->discover($batchSize, array_map(
                fn (array $candidate): string => (string) ($candidate['name'] ?? ''),
                $collected,
            ));

            $newInBatch = 0;

            foreach ($batch as $candidate) {
                $identity = $this->identity($candidate);

                if (isset($seen[$identity])) {
                    continue;
                }

                $seen[$identity] = true;
                $collected[] = $candidate;
                $newInBatch++;
            }

            if ($newInBatch === 0) {
                break;
            }
        }

        return $collected;
    }

    /**
     * A stable key for a candidate within one discovery run: the
     * solicitation number when known (the real identity of an opportunity
     * regardless of which link variant points to it), otherwise a
     * normalized form of the link.
     *
     * @param  array<string, mixed>  $candidate
     */
    private function identity(array $candidate): string
    {
        $solicitation = is_string($candidate['solicitation'] ?? null) ? trim($candidate['solicitation']) : '';

        if ($solicitation !== '') {
            return 'solicitation:'.strtolower($solicitation);
        }

        return 'link:'.$this->normalizeLink((string) ($candidate['link'] ?? ''));
    }

    private function normalizeLink(string $link): string
    {
        return rtrim(strtolower(trim($link)), '/');
    }

    /**
     * Inserts a brand-new candidate only, keyed by a synthetic external_id
     * derived from its normalized source link — namespaced with an
     * "openai:" prefix so it can never collide with a real SAM.gov
     * noticeId — and cross-checked against any opportunity already in the
     * pipeline under the same solicitation number, however it got there
     * (SAM.gov sync, manual entry, or an earlier discovery run).
     *
     * @param  array<string, mixed>  $candidate
     */
    private function upsert(array $candidate): void
    {
        $link = $candidate['link'] ?? null;

        if (! is_string($link) || $link === '') {
            Log::warning('OpenAI opportunity discovery: dropped a candidate with no source link.', [
                'name' => $candidate['name'] ?? null,
            ]);

            return;
        }

        $solicitation = is_string($candidate['solicitation'] ?? null) ? trim($candidate['solicitation']) : '';
        $solicitation = $solicitation !== '' ? $solicitation : null;

        if ($solicitation !== null && Opportunity::query()->where('solicitation', $solicitation)->exists()) {
            return;
        }

        $externalId = 'openai:'.sha1($this->normalizeLink($link));

        if (Opportunity::query()->where('external_id', $externalId)->exists()) {
            return;
        }

        if (! $this->linkIsReachable($link)) {
            Log::warning('OpenAI opportunity discovery: dropped a candidate whose link did not resolve.', [
                'name' => $candidate['name'] ?? null,
                'link' => $link,
            ]);

            return;
        }

        $opportunity = Opportunity::query()->create([
            'external_id' => $externalId,
            'discovered_at' => now(),
            'date_added' => now()->toDateString(),
            'name' => $candidate['name'],
            'agency' => $candidate['agency'],
            'agency_subsection' => $candidate['agency_subsection'] ?? null,
            'solicitation' => $solicitation,
            'naics' => $candidate['naics'] ?? null,
            'phase' => $candidate['phase'],
            'response_due' => $candidate['response_due'] ?? null,
            'release_date' => $candidate['release_date'] ?? null,
            'value' => $candidate['value'] ?? 0,
            'value_is_estimated' => $candidate['value'] !== null && ($candidate['value_is_estimated'] ?? false),
            'vehicle' => $candidate['vehicle'] ?? null,
            'set_aside' => $candidate['set_aside'] ?? null,
            'link' => $link,
            'description' => $candidate['description'] ?? null,
        ]);

        // Fill competitors/incumbent/RFP details and the AI-analysis narrative
        // the same real, source-grounded way every other opportunity gets
        // them — reusing the existing queued jobs rather than duplicating
        // their logic here.
        GenerateCompetitiveAnalysis::dispatch($opportunity->id);
        GenerateAiAnalysis::dispatch($opportunity->id);
        DiscoverContractingOfficer::dispatch($opportunity->id);
    }

    /**
     * A candidate's link must actually resolve before it's worth inserting
     * — a capture manager needs to be able to click through and verify the
     * notice on SAM.gov or wherever it's actually posted.
     */
    private function linkIsReachable(string $link): bool
    {
        try {
            if (Http::timeout(10)->head($link)->successful()) {
                return true;
            }

            // Some government sites reject HEAD requests (405) even though
            // the page itself is live — fall back to a real GET before
            // giving up on the link.
            return Http::timeout(10)->get($link)->successful();
        } catch (Throwable) {
            return false;
        }
    }
}
