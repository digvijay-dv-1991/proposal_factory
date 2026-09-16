<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Client opportunities now include standing commercial-solutions-opening
 * (CSO) submission pathways — a live, always-open channel rather than a
 * dated solicitation to Bid/No Bid on — which needs its own decision value
 * distinct from the other six.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->enum('decision', ['Pending', 'More Info', 'Monitoring', 'Shape', 'Open Call', 'Bid', 'No Bid'])
                ->default('Pending')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->enum('decision', ['Pending', 'More Info', 'Monitoring', 'Shape', 'Bid', 'No Bid'])
                ->default('Pending')
                ->change();
        });
    }
};
