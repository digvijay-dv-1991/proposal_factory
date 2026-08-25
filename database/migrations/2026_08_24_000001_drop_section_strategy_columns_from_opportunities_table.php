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
            // The Section Strategy tab was removed — origin stays (it still
            // drives the board's section tabs and Added Today grouping),
            // but these two fields had no other purpose.
            $table->dropColumn(['product_alignment', 'section_rationale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->text('product_alignment')->nullable()->after('govwin_link');
            $table->text('section_rationale')->nullable()->after('alqimi_sme');
        });
    }
};
