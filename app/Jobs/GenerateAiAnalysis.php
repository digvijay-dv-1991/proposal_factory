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
        $cacheKey = 'ai-analysis:'.hash('sha256', (string) json_encode($opportunity->only([
            'name', 'agency', 'description', 'focus', 'go_strength', 'gap',
            'incumbent', 'competitive_position', 'competitive_analysis',
        ])));

        $result = Cache::remember($cacheKey, now()->addDay(), fn () => $service->generate($opportunity));

        $opportunity->update([
            'ai_executive_summary' => $result['executive_summary'],
            'ai_why_it_matters' => $result['why_it_matters'],
            'ai_red_team_critique' => $result['red_team_critique'],
            'ai_competitive_outlook' => $result['competitive_outlook'],
            'ai_analysis_generated_at' => now(),
        ]);
    }
}
