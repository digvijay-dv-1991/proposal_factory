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
            // AI Analysis tab — generated once, stored, and reused; only
            // regenerated when the user explicitly asks for it. Synthesized
            // from the opportunity's own already-recorded fields, no web
            // search involved.
            $table->text('ai_executive_summary')->nullable();
            $table->text('ai_why_it_matters')->nullable();
            $table->text('ai_red_team_critique')->nullable();
            $table->text('ai_competitive_outlook')->nullable();
            $table->timestamp('ai_analysis_generated_at')->nullable();

            // Competitive Analysis tab — AI writes directly into the
            // existing competitive_*/incumbent_* columns (no separate copy),
            // this just tracks when that last happened and the source links
            // the web search grounded its answers in, so a person can verify
            // before trusting it.
            $table->timestamp('competitive_analysis_generated_at')->nullable();
            $table->json('competitive_analysis_sources')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn([
                'ai_executive_summary',
                'ai_why_it_matters',
                'ai_red_team_critique',
                'ai_competitive_outlook',
                'ai_analysis_generated_at',
                'competitive_analysis_generated_at',
                'competitive_analysis_sources',
            ]);
        });
    }
};
