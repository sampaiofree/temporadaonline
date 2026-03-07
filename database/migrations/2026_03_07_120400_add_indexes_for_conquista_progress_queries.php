<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partida_eventos')) {
            Schema::table('partida_eventos', function (Blueprint $table): void {
                $table->index(['user_id', 'tipo', 'partida_id'], 'partida_eventos_user_tipo_partida_idx');
            });
        }

        if (Schema::hasTable('partida_avaliacoes')) {
            Schema::table('partida_avaliacoes', function (Blueprint $table): void {
                $table->index(['avaliador_user_id', 'partida_id'], 'partida_avaliacoes_avaliador_partida_idx');
            });
        }

        if (Schema::hasTable('partidas')) {
            Schema::table('partidas', function (Blueprint $table): void {
                $table->index('placar_registrado_por', 'partidas_placar_registrado_por_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('partida_eventos')) {
            Schema::table('partida_eventos', function (Blueprint $table): void {
                $table->dropIndex('partida_eventos_user_tipo_partida_idx');
            });
        }

        if (Schema::hasTable('partida_avaliacoes')) {
            Schema::table('partida_avaliacoes', function (Blueprint $table): void {
                $table->dropIndex('partida_avaliacoes_avaliador_partida_idx');
            });
        }

        if (Schema::hasTable('partidas')) {
            Schema::table('partidas', function (Blueprint $table): void {
                $table->dropIndex('partidas_placar_registrado_por_idx');
            });
        }
    }
};
