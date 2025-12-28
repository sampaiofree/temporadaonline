<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partida_confirmacoes')) {
            Schema::drop('partida_confirmacoes');
        }

        if (Schema::hasTable('partida_opcoes_horario')) {
            Schema::drop('partida_opcoes_horario');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('partida_opcoes_horario')) {
            Schema::create('partida_opcoes_horario', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('partida_id')->constrained('partidas')->cascadeOnDelete();
                $table->timestamp('datetime');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('partida_confirmacoes')) {
            Schema::create('partida_confirmacoes', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('partida_id')->constrained('partidas')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamp('datetime');
                $table->timestamps();
                $table->unique(['partida_id', 'user_id', 'datetime'], 'partida_confirmacao_unique');
                $table->index(['partida_id', 'datetime']);
            });
        }
    }
};
