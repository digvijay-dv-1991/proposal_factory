<?php

namespace App\Console\Commands;

use App\Services\SamGovOpportunityService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature(<<<'SIGNATURE'
    opportunities:fetch-sam-gov
        {--limit= : Only search the first N keyword buckets — use this locally to stay under a low-tier SAM.gov daily request quota}
        {--detail-limit= : How many newly-added notices get their full description text fetched (a second API call each); defaults to 8}
    SIGNATURE)]
#[Description('Search SAM.gov for new federal opportunities across ALQIMI\'s mission keyword buckets and add any not already in the pipeline')]
class FetchSamGovOpportunities extends Command
{
    public function handle(SamGovOpportunityService $samGov): int
    {
        $limit = $this->option('limit');
        $detailLimit = $this->option('detail-limit');

        $result = $samGov->sync(
            $limit !== null ? (int) $limit : null,
            $detailLimit !== null ? (int) $detailLimit : null,
        );

        $this->info(sprintf(
            'Searched %d keyword bucket(s), found %d unique notice(s): %d added (%d with full description), %d already known, %d not actionable.',
            $result['terms_searched'],
            $result['fetched'],
            $result['created'],
            $result['described'],
            $result['duplicate'],
            $result['not_actionable'],
        ));

        return self::SUCCESS;
    }
}
