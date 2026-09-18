<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            // Free-text "Recommended Teaming Strategy" narrative shown on the
            // Overview tab's Core Narrative panel — missed in the Day 1
            // schema, found only once the reference's Overview markup was
            // read in full for Day 3.
            $table->text('teaming')->nullable()->after('competitors');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn('teaming');
        });
    }
};
