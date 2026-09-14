<?php

namespace App\Jobs;

use App\Models\MarketCompetitor;
use App\Models\Opportunity;
use App\Services\OpenAiCompetitiveResearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class GenerateCompetitiveAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    /**
     * A pause before Laravel's own automatic retry (on top of the internal
     * rate-limit backoff in SendsOpenAiRequests) — a retry fired instantly
     * after a failure is likely to hit the exact same transient condition
     * again. Gives whatever caused it a real chance to clear first.
     */
    public int $backoff = 60;

    /**
     * Covers the web-search call's own up-to-3 rate-limit-backoff attempts
     * (see SendsOpenAiRequests) at worst case, not typical runtime.
     */
    public int $timeout = 450;

    /**
     * Bump whenever OpenAiCompetitiveResearchService's output shape changes
     * — see GenerateAiAnalysis::CACHE_VERSION for why this matters: the
     * cache key is a hash of the opportunity's own content, not of the
     * code, so an unchanged opportunity re-processed after a schema change
     * would otherwise silently get served a stale, incompatible result.
     */
    private const CACHE_VERSION = 1;

    public function __construct(private readonly int $opportunityId) {}

    public function handle(OpenAiCompetitiveResearchService $service): void
    {
        $opportunity = Opportunity::query()->find($this->opportunityId);

        if ($opportunity === null) {
            return;
        }

        // Cached by a hash of the identifying fields the research prompt is
        // built from, so re-clicking "Generate" with the same identifying
        // details doesn't pay for a repeat web-search call.
        $cacheKey = 'competitive-research:v'.self::CACHE_VERSION.':'.hash('sha256', (string) json_encode($opportunity->only([
            'name', 'agency', 'solicitation', 'naics', 'description', 'link',
        ])));

        $result = Cache::remember($cacheKey, now()->addDay(), fn () => $service->generate($opportunity));

        $opportunity->update([
            ...$result['fields'],
            'competitive_analysis_sources' => $result['sources'],
            'competitive_analysis_generated_at' => now(),
        ]);

        foreach ($result['competitor_profiles'] as $profile) {
            $this->recordCompetitorWebsite($profile);
        }
    }

    /**
     * Feeds the "Competitor Links" panel (Opportunity::competitorLinks(),
     * which matches names against this catalog) with a verified website —
     * only ever backfilling a blank url on an EXISTING catalog entry, never
     * creating a new one. A new row would need real values for the
     * catalog's other required fields (label, alqimi_advantage, strategy,
     * etc.) that only a capture manager's own analysis can honestly supply.
     *
     * @param  array<string, mixed>  $profile
     */
    private function recordCompetitorWebsite(array $profile): void
    {
        $name = trim((string) ($profile['name'] ?? ''));
        $url = $profile['url'] ?? null;

        if ($name === '' || ! is_string($url) || $url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return;
        }

        MarketCompetitor::query()
            ->where('name', $name)
            ->where(fn ($query) => $query->whereNull('url')->orWhere('url', ''))
            ->update(['url' => $url]);
    }
}
