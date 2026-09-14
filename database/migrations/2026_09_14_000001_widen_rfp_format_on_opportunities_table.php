<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * rfp_format is a VARCHAR(255) but the Competitive Analysis AI call
     * writes a full paragraph here — MySQL rejects the UPDATE with "Data
     * too long for column 'rfp_format'", which fails the whole save and
     * silently drops competitors/incumbent/RFP details along with it.
     */
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->text('rfp_format')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->string('rfp_format')->nullable()->change();
        });
    }
};
