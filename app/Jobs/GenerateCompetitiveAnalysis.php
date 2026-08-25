<?php

namespace App\Jobs;

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

    public int $timeout = 150;

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
        $cacheKey = 'competitive-research:'.hash('sha256', (string) json_encode($opportunity->only([
            'name', 'agency', 'solicitation', 'naics', 'description', 'link',
        ])));

        $result = Cache::remember($cacheKey, now()->addDay(), fn () => $service->generate($opportunity));

        $opportunity->update([
            ...$result['fields'],
            'competitive_analysis_sources' => $result['sources'],
            'competitive_analysis_generated_at' => now(),
        ]);
    }
}
