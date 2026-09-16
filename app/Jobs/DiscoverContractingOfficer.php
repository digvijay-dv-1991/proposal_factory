<?php

namespace App\Jobs;

use App\Models\Opportunity;
use App\Models\OpportunityContact;
use App\Services\OpenAiContractingOfficerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class DiscoverContractingOfficer implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Staged backoff (1 min, then 3 min) — see GenerateAiAnalysis for why
     * two growing-gap retries beat one fixed-delay retry here.
     *
     * @var array<int, int>
     */
    public array $backoff = [60, 180];

    /**
     * Covers the web-search call's own up-to-3 rate-limit-backoff attempts
     * (see SendsOpenAiRequests) at worst case, not typical runtime.
     */
    public int $timeout = 450;

    /**
     * Must stay >= $timeout (with margin) — see GenerateAiAnalysis::$uniqueFor.
     */
    public int $uniqueFor = 500;

    /**
     * Bump whenever OpenAiContractingOfficerService's output shape changes
     * — see GenerateAiAnalysis::CACHE_VERSION for why this matters: the
     * cache key is a hash of the opportunity's own content, not of the
     * code, so an unchanged opportunity re-processed after a schema change
     * would otherwise silently get served a stale, incompatible result.
     */
    private const CACHE_VERSION = 1;

    public function __construct(private readonly int $opportunityId) {}

    public function uniqueId(): string
    {
        return (string) $this->opportunityId;
    }

    public function handle(OpenAiContractingOfficerService $service): void
    {
        $opportunity = Opportunity::query()->find($this->opportunityId);

        if ($opportunity === null) {
            return;
        }

        $cacheKey = 'contracting-officer:v'.self::CACHE_VERSION.':'.hash('sha256', (string) json_encode($opportunity->only([
            'name', 'agency', 'solicitation', 'link',
        ])));

        $result = Cache::remember($cacheKey, now()->addDay(), fn () => $service->generate($opportunity));

        foreach ($result['officers'] as $officer) {
            $this->recordOfficer($opportunity, $officer);
        }
    }

    /**
     * Only ever adds a contact — never overwrites one a capture manager
     * already entered or edited, and skips a re-run's duplicate of a
     * contact already recorded under the same name.
     *
     * @param  array{name: string, email: string|null, phone: string|null}  $officer
     */
    private function recordOfficer(Opportunity $opportunity, array $officer): void
    {
        $name = trim($officer['name']);

        if ($name === '') {
            return;
        }

        $alreadyExists = OpportunityContact::query()
            ->where('opportunity_id', $opportunity->id)
            ->where('name', $name)
            ->exists();

        if ($alreadyExists) {
            return;
        }

        OpportunityContact::query()->create([
            'opportunity_id' => $opportunity->id,
            'name' => $name,
            'email' => $officer['email'],
            'phone' => $officer['phone'],
        ]);
    }

    /**
     * All retries exhausted — log it loudly rather than let this fail
     * silently, leaving an opportunity with no contracting officer info and
     * no visible sign a discovery attempt was ever made.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('DiscoverContractingOfficer: permanently failed after all retries.', [
            'opportunity_id' => $this->opportunityId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
