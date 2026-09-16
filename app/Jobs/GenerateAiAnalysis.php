<?php

namespace App\Jobs;

use App\Models\Opportunity;
use App\Services\OpenAiAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateAiAnalysis implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Staged backoff (1 min, then 3 min) before Laravel's own automatic
     * retry, on top of the internal rate-limit backoff in
     * SendsOpenAiRequests — a retry fired instantly after a failure is
     * likely to hit the exact same transient condition again. Two retries
     * with growing gaps rides out a longer OpenAI-side blip than a single
     * fixed-delay retry would.
     *
     * @var array<int, int>
     */
    public array $backoff = [60, 180];

    /**
     * Covers this call's own up-to-3 rate-limit-backoff attempts (see
     * SendsOpenAiRequests) at worst case, not typical runtime — the
     * previous unset value fell back to the queue worker's default, which
     * is too tight for that worst case.
     */
    public int $timeout = 300;

    /**
     * Must stay >= $timeout (with margin): the uniqueness lock has to
     * outlive the job's own worst-case runtime, or it could expire mid-run
     * and let a second worker pick up the same job — the exact scenario
     * DB_QUEUE_RETRY_AFTER being left at its 90s default already risks (see
     * project notes). This is a defense-in-depth guard against that same
     * failure mode: even if the queue's retry_after is misconfigured, two
     * concurrent runs for the same opportunity can never both pay for and
     * apply an OpenAI call.
     */
    public int $uniqueFor = 360;

    /**
     * Bump this whenever OpenAiAnalysisService's output shape changes
     * (a new field, a renamed key). The cache key below is a hash of the
     * opportunity's own content, not of the code — an unchanged opportunity
     * re-processed after a prompt/schema change would otherwise silently
     * get served a stale result missing the new field(s), exactly like the
     * "Undefined array key" crash this version bump fixes.
     */
    private const CACHE_VERSION = 3;

    public function __construct(private readonly int $opportunityId) {}

    public function uniqueId(): string
    {
        return (string) $this->opportunityId;
    }

    public function handle(OpenAiAnalysisService $service): void
    {
        $opportunity = Opportunity::query()->find($this->opportunityId);

        if ($opportunity === null) {
            return;
        }

        // Cached by a hash of the exact fields the prompt is built from, so
        // clicking "Generate" again with unchanged data doesn't pay for a
        // repeat OpenAI call.
        $cacheKey = 'ai-analysis:v'.self::CACHE_VERSION.':'.hash('sha256', (string) json_encode($opportunity->only([
            'name', 'agency', 'description', 'focus', 'go_strength', 'gap',
            'incumbent', 'competitive_position', 'competitive_analysis',
            'source_description', 'source_requirements',
        ])));

        $result = Cache::remember($cacheKey, now()->addDay(), fn () => $service->generate($opportunity));

        $updates = [
            'ai_executive_summary' => $result['executive_summary'],
            'ai_why_it_matters' => $result['why_it_matters'],
            'ai_red_team_critique' => $result['red_team_critique'],
            'ai_competitive_outlook' => $result['competitive_outlook'],
            'ai_analysis_generated_at' => now(),
        ];

        // Same rule for focus tags — untagged is what makes Capability Fit
        // scoring blind, so this has to land before/with go_strength, not
        // overwrite a capture manager's own tagging.
        if (blank($opportunity->focus)) {
            $updates['focus'] = $result['focus'];
        }

        // Only draft the Gap Analysis tab's own fields when a capture
        // manager hasn't already filled them in — never overwrite their work.
        if (blank($opportunity->gap)) {
            $updates['gap'] = $result['gap'];
        }

        if (blank($opportunity->gap_mitigation)) {
            $updates['gap_mitigation'] = $result['gap_mitigation'];
        }

        // Core Narrative's Solicitation Description was never written by any
        // pipeline before this — reuse the executive summary (already a
        // short, dense paragraph) instead of paying for a second, near-
        // duplicate OpenAI field.
        if (blank($opportunity->source_description)) {
            $updates['source_description'] = $result['executive_summary'];
        }

        if (blank($opportunity->key_points)) {
            $updates['key_points'] = $result['key_points'];
        }

        if (blank($opportunity->requirement_key_points)) {
            $updates['requirement_key_points'] = $result['requirement_key_points'];
        }

        // Same rule for Bid Strength / Win Probability — 0 is each column's
        // default, so it means "nobody has scored this yet," never a
        // manager's actual 0. Both fields read the same underlying number:
        // "Bid Strength" (the card badge) and "Win Probability" (the modal
        // summary) are the same concept shown in two places, not two
        // independent scores.
        if ($opportunity->go_strength === 0) {
            $updates['go_strength'] = $result['go_strength'];
        }

        if ($opportunity->probability === 0) {
            $updates['probability'] = $result['go_strength'];
        }

        $opportunity->update($updates);
    }

    /**
     * All retries exhausted — this must be loud, not silent. Without this,
     * an opportunity can sit indefinitely with a misleading 0% Bid
     * Strength / Win Probability and no one would know AI Analysis never
     * actually ran, since a failed queue job leaves no trace in the UI.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('GenerateAiAnalysis: permanently failed after all retries.', [
            'opportunity_id' => $this->opportunityId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
