<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_competitors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('label');
            $table->text('alqimi_products');
            $table->text('competitor_offering');
            $table->text('overlap');
            $table->text('alqimi_advantage');
            $table->text('competitor_advantage');
            $table->text('strategy');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_competitors');
    }
};
