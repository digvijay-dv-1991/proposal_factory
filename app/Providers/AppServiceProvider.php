<?php

namespace App\Providers;

use App\Models\Opportunity;
use App\Models\OpportunityBidComment;
use App\Observers\OpportunityBidCommentObserver;
use App\Observers\OpportunityObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Opportunity::observe(OpportunityObserver::class);
        OpportunityBidComment::observe(OpportunityBidCommentObserver::class);
    }
}
