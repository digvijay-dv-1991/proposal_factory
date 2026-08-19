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
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();

            // Identity / Overview
            $table->string('external_id')->unique();
            $table->string('name');
            $table->string('agency');
            $table->string('agency_subsection')->nullable();
            $table->string('solicitation')->nullable();
            $table->string('naics')->nullable();
            $table->string('psc')->nullable();
            $table->decimal('value', 14, 2)->default(0);
            $table->string('vehicle')->nullable();
            $table->string('set_aside')->nullable();
            $table->enum('phase', [
                'Pre-Solicitation', 'RFI', 'Draft RFP', 'RFP Released', 'Submitted', 'Source Selection',
            ])->default('Pre-Solicitation');
            $table->enum('decision', [
                'Pending', 'More Info', 'Monitoring', 'Bid', 'No Bid',
            ])->default('Pending');
            $table->date('date_added')->nullable();
            $table->date('response_due')->nullable();
            $table->string('response_time')->nullable();
            $table->date('release_date')->nullable();
            $table->timestamp('discovered_at')->nullable();
            $table->string('link')->nullable();
            $table->string('govwin_link')->nullable();
            $table->text('product_alignment')->nullable();
            $table->string('alqimi_sme')->nullable();
            $table->text('section_rationale')->nullable();
            $table->enum('origin', [
                'CBRN', 'FOCI', 'General', 'Modernization', 'DoD Intelligence - Ops', 'Health', 'Digitization', 'MISC',
            ])->default('General');
            $table->json('focus')->nullable();
            $table->json('keywords')->nullable();
            $table->json('capture_plan')->nullable();

            // Source verification (100% populated in reference data, tied to the
            // required-source-link workflow on the Overview tab).
            $table->text('source_verification')->nullable();
            $table->boolean('source_title_verified')->default(false);
            $table->date('monitoring_last_checked')->nullable();

            // Capture Information
            $table->text('next_action')->nullable();
            $table->date('action_due')->nullable();
            $table->string('capture_owner')->nullable();
            $table->string('proposal_manager')->nullable();
            $table->text('source_description')->nullable();
            $table->text('source_requirements')->nullable();
            $table->text('description')->nullable();
            $table->text('scope')->nullable();
            $table->text('rfp_instructions')->nullable();
            $table->text('rfp_sections')->nullable();
            $table->string('rfp_format')->nullable();
            $table->text('evaluation_factors')->nullable();
            $table->unsignedTinyInteger('probability')->default(0);
            $table->unsignedTinyInteger('bid_priority')->nullable();

            // Gap Analysis
            $table->text('gap')->nullable();
            $table->text('gap_mitigation')->nullable();
            $table->string('gap_owner')->nullable();
            $table->enum('gap_status', [
                'Not Assessed', 'Open', 'Mitigation in Progress', 'Accepted', 'Resolved',
            ])->default('Not Assessed');

            // Competitive Analysis
            $table->text('competitive_analysis')->nullable();
            $table->text('competitive_discriminators')->nullable();
            $table->text('competitive_next_action')->nullable();
            $table->enum('competitive_position', [
                'Strong', 'Moderate', 'Weak', 'Unknown',
            ])->default('Unknown');
            $table->text('competitors')->nullable();

            // Incumbent
            $table->string('incumbent')->default('Unknown');
            $table->string('incumbent_contract')->nullable();
            $table->string('incumbent_award_value')->nullable();
            $table->string('incumbent_period')->nullable();
            $table->text('incumbent_brief')->nullable();
            $table->text('incumbent_performance')->nullable();
            $table->text('incumbent_strengths')->nullable();
            $table->text('incumbent_weaknesses')->nullable();
            $table->text('incumbent_customer_relationship')->nullable();
            $table->string('incumbent_source')->nullable();

            // Bid Strength (sub-scores are computed from go_strength, not stored)
            $table->unsignedTinyInteger('go_strength')->default(0);

            // Decision workflow
            $table->string('decision_by')->nullable();
            $table->text('decision_comment')->nullable();
            $table->date('decision_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
