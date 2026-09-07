<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('label');
            $table->text('what_they_do');
            $table->text('client_alignment');
            $table->text('product_areas');
            $table->text('partnership_value');
            $table->text('use_together');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_partners');
    }
};
