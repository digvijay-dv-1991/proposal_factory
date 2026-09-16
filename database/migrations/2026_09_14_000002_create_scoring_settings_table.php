<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A single editable row of Bid Strength (win %) rubric weights, so a
     * capture manager can retune how much each factor counts without a code
     * deploy. Capability Fit is weighted highest per the client's explicit
     * direction: ALQIMI's own ability to do the work matters most, other
     * factors are secondary.
     */
    public function up(): void
    {
        Schema::create('scoring_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('capability_fit_weight')->default(40);
            $table->unsignedTinyInteger('competitive_position_weight')->default(20);
            $table->unsignedTinyInteger('mission_fit_weight')->default(15);
            $table->unsignedTinyInteger('timing_weight')->default(15);
            $table->unsignedTinyInteger('vehicle_accessibility_weight')->default(10);
            $table->timestamps();
        });

        DB::table('scoring_settings')->insert([
            'capability_fit_weight' => 40,
            'competitive_position_weight' => 20,
            'mission_fit_weight' => 15,
            'timing_weight' => 15,
            'vehicle_accessibility_weight' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('scoring_settings');
    }
};
