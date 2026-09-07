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
        Schema::table('opportunity_bid_comments', function (Blueprint $table) {
            // Holds the typed name for a comment posted by someone not
            // logged in — the chat stays open to everyone until the
            // auth/roles phase starts, so we can't rely on user_id alone.
            $table->string('author_name')->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunity_bid_comments', function (Blueprint $table) {
            $table->dropColumn('author_name');
        });
    }
};
