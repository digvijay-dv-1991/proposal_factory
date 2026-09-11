<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Modernization" was too broad — it describes an enterprise-IT level of
     * effort that could apply across every other mission section. Renamed to
     * "DFaaS", a more specific mission category for the opportunities this
     * section is actually meant to group.
     */
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->enum('origin', ['CBRN', 'FOCI', 'General', 'Modernization', 'DoD Intelligence - Ops', 'Health', 'Digitization', 'MISC', 'DFaaS'])
                ->default('General')
                ->change();
        });

        DB::statement("UPDATE opportunities SET origin = 'DFaaS' WHERE origin = 'Modernization'");

        Schema::table('opportunities', function (Blueprint $table) {
            $table->enum('origin', ['CBRN', 'FOCI', 'General', 'DoD Intelligence - Ops', 'Health', 'Digitization', 'MISC', 'DFaaS'])
                ->default('General')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->enum('origin', ['CBRN', 'FOCI', 'General', 'DoD Intelligence - Ops', 'Health', 'Digitization', 'MISC', 'DFaaS', 'Modernization'])
                ->default('General')
                ->change();
        });

        DB::statement("UPDATE opportunities SET origin = 'Modernization' WHERE origin = 'DFaaS'");

        Schema::table('opportunities', function (Blueprint $table) {
            $table->enum('origin', ['CBRN', 'FOCI', 'General', 'Modernization', 'DoD Intelligence - Ops', 'Health', 'Digitization', 'MISC'])
                ->default('General')
                ->change();
        });
    }
};
