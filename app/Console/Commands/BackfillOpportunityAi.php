<?php

namespace App\Console\Commands;

use App\Jobs\DiscoverContractingOfficer;
use App\Jobs\GenerateAiAnalysis;
use App\Jobs\GenerateCompetitiveAnalysis;
use App\Models\Opportunity;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Every opportunity created before AI Analysis / Competitive Analysis /
 * Contracting Officer discovery existed (or added via SAM.gov sync, which
 * deliberately never dispatches AI jobs itself) has none of that data —
 * this queues it up without re-running it for anything already generated.
 * Each underlying job already only fills a field when it's still blank
 * (see GenerateAiAnalysis/GenerateCompetitiveAnalysis), so re-running this
 * command is always safe and never overwrites a capture manager's own work.
 *
 * Deliberately manual, like the SAM.gov and OpenAI discovery commands — an
 * unbounded run here would dispatch three OpenAI-calling jobs per
 * opportunity across the whole backlog at once. Run it in controlled
 * batches (the default --limit) rather than all at once.
 */
#[Signature(<<<'SIGNATURE'
    opportunities:backfill-ai
        {--limit=10 : Max opportunities to queue per run, per job type — keep this small to control OpenAI spend}
    SIGNATURE)]
#[Description('Queue AI Analysis, Competitive Analysis, and Contracting Officer discovery for opportunities that never had them run')]
class BackfillOpportunityAi extends Command
{
    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $aiAnalysisCount = $this->queueMissing(
            Opportunity::query()->whereNull('ai_analysis_generated_at'),
            $limit,
            fn (Opportunity $opportunity) => GenerateAiAnalysis::dispatch($opportunity->id),
        );
        $this->info("Queued AI Analysis for {$aiAnalysisCount} opportunity(ies).");

        $competitiveCount = $this->queueMissing(
            Opportunity::query()->whereNull('competitive_analysis_generated_at'),
            $limit,
            fn (Opportunity $opportunity) => GenerateCompetitiveAnalysis::dispatch($opportunity->id),
        );
        $this->info("Queued Competitive Analysis for {$competitiveCount} opportunity(ies).");

        $contractingOfficerCount = $this->queueMissing(
            Opportunity::query()->doesntHave('contacts'),
            $limit,
            fn (Opportunity $opportunity) => DiscoverContractingOfficer::dispatch($opportunity->id),
        );
        $this->info("Queued Contracting Officer discovery for {$contractingOfficerCount} opportunity(ies).");

        $this->info('Run `php artisan queue:work` if it isn\'t already running to process these. Re-run this command with the same --limit to work through the rest of the backlog in batches.');

        return self::SUCCESS;
    }

    /**
     * @param  Builder<Opportunity>  $query
     */
    private function queueMissing(Builder $query, int $limit, \Closure $dispatch): int
    {
        $opportunities = $query->orderBy('id')->limit($limit)->get(['id']);

        foreach ($opportunities as $opportunity) {
            $dispatch($opportunity);
        }

        return $opportunities->count();
    }
}
