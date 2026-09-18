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
            $table->index('decision');
            $table->index('phase');
            $table->index('origin');
            $table->index('go_strength');
            $table->index('discovered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropIndex(['decision']);
            $table->dropIndex(['phase']);
            $table->dropIndex(['origin']);
            $table->dropIndex(['go_strength']);
            $table->dropIndex(['discovered_at']);
        });
    }
};
