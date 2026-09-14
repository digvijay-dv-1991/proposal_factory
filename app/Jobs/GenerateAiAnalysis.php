<?php

namespace App\Jobs;

use App\Models\Opportunity;
use App\Services\OpenAiAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class GenerateAiAnalysis implements ShouldQueue
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
     * Covers this call's own up-to-3 rate-limit-backoff attempts (see
     * SendsOpenAiRequests) at worst case, not typical runtime — the
     * previous unset value fell back to the queue worker's default, which
     * is too tight for that worst case.
     */
    public int $timeout = 300;

    /**
     * Bump this whenever OpenAiAnalysisService's output shape changes
     * (a new field, a renamed key). The cache key below is a hash of the
     * opportunity's own content, not of the code — an unchanged opportunity
     * re-processed after a prompt/schema change would otherwise silently
     * get served a stale result missing the new field(s), exactly like the
     * "Undefined array key" crash this version bump fixes.
     */
    private const CACHE_VERSION = 2;

    public function __construct(private readonly int $opportunityId) {}

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
}
