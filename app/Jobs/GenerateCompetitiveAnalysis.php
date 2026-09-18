<?php

namespace App\Jobs;

use App\Models\MarketCompetitor;
use App\Models\Opportunity;
use App\Models\OpportunityPartner;
use App\Services\OpenAiCompetitiveResearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateCompetitiveAnalysis implements ShouldBeUnique, ShouldQueue
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
     * Must stay >= $timeout (with margin) — see GenerateAiAnalysis::$uniqueFor
     * for why: this is what actually stops a second worker from re-running
     * (and re-paying for) the same opportunity's research if
     * DB_QUEUE_RETRY_AFTER is ever left too low relative to this job's
     * runtime.
     */
    public int $uniqueFor = 500;

    /**
     * Bump whenever OpenAiCompetitiveResearchService's output shape changes
     * — see GenerateAiAnalysis::CACHE_VERSION for why this matters: the
     * cache key is a hash of the opportunity's own content, not of the
     * code, so an unchanged opportunity re-processed after a schema change
     * would otherwise silently get served a stale, incompatible result.
     */
    private const CACHE_VERSION = 2;

    public function __construct(private readonly int $opportunityId) {}

    public function uniqueId(): string
    {
        return (string) $this->opportunityId;
    }

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

        $this->recordTeamingRecommendation($opportunity, $result['teaming_recommendation']);
    }

    /**
     * Surfaces the AI's teaming recommendation on the actual Teaming tab
     * (opportunity_partners) instead of leaving it buried in the
     * Competitive Analysis tab's prose — client review flagged that a real
     * teaming suggestion previously never reached the Teaming tab. Contact
     * info lives only here, never in the "teaming" prose field.
     *
     * @param  array{company: string|null, contact_email: string|null, contact_phone: string|null, rationale: string}  $recommendation
     */
    private function recordTeamingRecommendation(Opportunity $opportunity, array $recommendation): void
    {
        $company = trim((string) ($recommendation['company'] ?? ''));

        if ($company === '') {
            return;
        }

        $alreadyExists = OpportunityPartner::query()
            ->where('opportunity_id', $opportunity->id)
            ->where('company', $company)
            ->exists();

        if ($alreadyExists) {
            return;
        }

        OpportunityPartner::query()->create([
            'opportunity_id' => $opportunity->id,
            'company' => $company,
            'status' => 'AI Recommended',
            'contact_email' => $recommendation['contact_email'],
            'contact_phone' => $recommendation['contact_phone'],
            'rationale' => $recommendation['rationale'],
        ]);
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

    /**
     * All retries exhausted — log it loudly rather than let a Competitive
     * Analysis silently never generate (an opportunity could otherwise sit
     * with an empty tab and no teaming recommendation forever, with nothing
     * in the UI to show a run was ever attempted).
     */
    public function failed(Throwable $exception): void
    {
        Log::error('GenerateCompetitiveAnalysis: permanently failed after all retries.', [
            'opportunity_id' => $this->opportunityId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
