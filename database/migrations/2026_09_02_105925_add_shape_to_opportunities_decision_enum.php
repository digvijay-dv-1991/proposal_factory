<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Client opportunities are now coming in tagged "Shape" — actively
     * working to influence requirements before a Bid/No Bid call — which
     * isn't one of the five decision values the column was created with.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE opportunities MODIFY decision ENUM('Pending', 'More Info', 'Monitoring', 'Shape', 'Bid', 'No Bid') NOT NULL DEFAULT 'Pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE opportunities MODIFY decision ENUM('Pending', 'More Info', 'Monitoring', 'Bid', 'No Bid') NOT NULL DEFAULT 'Pending'");
    }
};
