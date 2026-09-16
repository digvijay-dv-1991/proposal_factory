<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Client review: Contracting Officers should only ever be Name/Email/Phone
 * — Title/Organization/Role are dropped outright (confirmed with the user;
 * any previously-entered values in those columns are not preserved).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunity_contacts', function (Blueprint $table) {
            $table->dropColumn(['title', 'organization', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('opportunity_contacts', function (Blueprint $table) {
            $table->string('title')->nullable()->after('name');
            $table->string('organization')->nullable()->after('title');
            $table->string('role')->nullable()->after('organization');
        });
    }
};
