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
        Schema::create('opportunity_bid_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();

            // moved_to_bid, day_14, day_7, day_3, day_1, due_day
            $table->string('milestone');

            // Snapshot of response_due at send time. A later edit to the
            // opportunity's response_due changes this value going forward,
            // so the (opportunity_id, milestone, response_due) tuple no
            // longer matches an existing row — the milestone becomes
            // eligible to fire again against the new due date automatically.
            $table->date('response_due')->nullable();

            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['opportunity_id', 'milestone', 'response_due'], 'opportunity_bid_alerts_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunity_bid_alerts');
    }
};
