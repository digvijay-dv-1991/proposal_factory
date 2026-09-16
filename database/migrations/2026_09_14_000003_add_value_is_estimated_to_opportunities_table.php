<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distinguishes an officially-published contract value from an
     * AI-reasoned estimate, so the two never get silently blended together
     * in the "total pipeline value" KPI (OpportunityStatsOverview sums
     * `value` directly).
     */
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->boolean('value_is_estimated')->default(false)->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn('value_is_estimated');
        });
    }
};
