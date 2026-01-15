<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('premiacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('posicao')->unique();
            $table->string('imagem');
            $table->unsignedInteger('premiacao');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('premiacoes');
    }
};
