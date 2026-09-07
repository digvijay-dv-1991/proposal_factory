<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
        DB::statement("ALTER TABLE opportunities MODIFY origin ENUM('CBRN', 'FOCI', 'General', 'Modernization', 'DoD Intelligence - Ops', 'Health', 'Digitization', 'MISC', 'DFaaS') NOT NULL DEFAULT 'General'");
        DB::statement("UPDATE opportunities SET origin = 'DFaaS' WHERE origin = 'Modernization'");
        DB::statement("ALTER TABLE opportunities MODIFY origin ENUM('CBRN', 'FOCI', 'General', 'DoD Intelligence - Ops', 'Health', 'Digitization', 'MISC', 'DFaaS') NOT NULL DEFAULT 'General'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE opportunities MODIFY origin ENUM('CBRN', 'FOCI', 'General', 'DoD Intelligence - Ops', 'Health', 'Digitization', 'MISC', 'DFaaS', 'Modernization') NOT NULL DEFAULT 'General'");
        DB::statement("UPDATE opportunities SET origin = 'Modernization' WHERE origin = 'DFaaS'");
        DB::statement("ALTER TABLE opportunities MODIFY origin ENUM('CBRN', 'FOCI', 'General', 'Modernization', 'DoD Intelligence - Ops', 'Health', 'Digitization', 'MISC') NOT NULL DEFAULT 'General'");
    }
};
