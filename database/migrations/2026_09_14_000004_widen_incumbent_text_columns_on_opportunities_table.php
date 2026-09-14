<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Same class of bug as rfp_format (see the earlier migration): these
     * are VARCHAR(255) but the Competitive Analysis prompt's own house
     * style examples show full sentences for these fields, which can
     * exceed 255 characters and fail the save the same way.
     */
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->text('incumbent_contract')->nullable()->change();
            $table->text('incumbent_award_value')->nullable()->change();
            $table->text('incumbent_period')->nullable()->change();
            $table->text('incumbent_source')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->string('incumbent_contract')->nullable()->change();
            $table->string('incumbent_award_value')->nullable()->change();
            $table->string('incumbent_period')->nullable()->change();
            $table->string('incumbent_source')->nullable()->change();
        });
    }
};
