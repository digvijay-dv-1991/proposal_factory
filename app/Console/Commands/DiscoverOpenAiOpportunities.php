<?php

namespace App\Console\Commands;

use App\Jobs\DiscoverOpportunitiesViaOpenAi;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature(<<<'SIGNATURE'
    opportunities:discover-openai
        {--limit=4 : Max opportunities to find, fetched in batches of 5 — keep this small locally; capped at 20 regardless of what's passed}
        {--fresh : Bypass the same-day cached result and force a brand-new OpenAI call for this limit}
    SIGNATURE)]
#[Description('Use OpenAI web search to replicate the client\'s manual ChatGPT opportunity search and add any brand-new notices found')]
class DiscoverOpenAiOpportunities extends Command
{
    public function handle(): int
    {
        $requested = (int) $this->option('limit');
        $limit = min($requested, DiscoverOpportunitiesViaOpenAi::MAX_LIMIT);
        $fresh = (bool) $this->option('fresh');

        if ($limit < $requested) {
            $this->warn("Requested limit {$requested} exceeds the safety cap — using {$limit} instead.");
        }

        DiscoverOpportunitiesViaOpenAi::dispatch($limit, $fresh);

        $freshNote = $fresh ? ', bypassing the cache' : '';
        $this->info("Queued OpenAI opportunity discovery (limit: {$limit}{$freshNote}). Run `php artisan queue:work` if it isn't already running to process it.");

        return self::SUCCESS;
    }
}
