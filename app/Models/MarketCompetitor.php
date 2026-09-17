<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MarketCompetitor extends Model
{
    /**
     * Cache key for the name->url catalog read by
     * Opportunity::competitorLinks() — near-static reference data,
     * invalidated below whenever this table changes.
     */
    public const CATALOG_CACHE_KEY = 'market-competitors:catalog';

    protected $fillable = [
        'name',
        'url',
        'label',
        'alqimi_products',
        'competitor_offering',
        'overlap',
        'alqimi_advantage',
        'competitor_advantage',
        'strategy',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CATALOG_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CATALOG_CACHE_KEY));
    }
}
