<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Client opportunities are now coming in tagged "Shape" — actively
     * working to influence requirements before a Bid/No Bid call — which
     * isn't one of the five decision values the column was created with.
     */
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->enum('decision', ['Pending', 'More Info', 'Monitoring', 'Shape', 'Bid', 'No Bid'])
                ->default('Pending')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->enum('decision', ['Pending', 'More Info', 'Monitoring', 'Bid', 'No Bid'])
                ->default('Pending')
                ->change();
        });
    }
};
