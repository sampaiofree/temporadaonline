<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partida_avaliacoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partida_id')->constrained('partidas')->cascadeOnDelete();
            $table->foreignId('avaliador_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('avaliado_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('nota');
            $table->timestamps();

            $table->unique(['partida_id', 'avaliador_user_id']);
            $table->index(['avaliado_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partida_avaliacoes');
    }
};
