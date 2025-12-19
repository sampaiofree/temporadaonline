<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_disponibilidades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana'); // 0 = domingo (Carbon), 6 = sábado
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->timestamps();
            $table->unique(['user_id', 'dia_semana', 'hora_inicio', 'hora_fim'], 'user_day_time_unique');
            $table->index(['user_id', 'dia_semana']);
        });

        Schema::create('partidas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('liga_id')->constrained('ligas')->cascadeOnDelete();
            $table->foreignId('mandante_id')->constrained('liga_clubes')->cascadeOnDelete();
            $table->foreignId('visitante_id')->constrained('liga_clubes')->cascadeOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->enum('estado', [
                'agendada',
                'confirmacao_necessaria',
                'confirmada',
                'em_andamento',
                'finalizada',
                'wo',
                'cancelada',
            ])->default('confirmacao_necessaria');
            $table->unsignedInteger('alteracoes_usadas')->default(0);
            $table->boolean('forced_by_system')->default(false);
            $table->boolean('sem_slot_disponivel')->default(false);
            $table->foreignId('wo_para_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('wo_motivo', [
                'nao_compareceu',
                'escala_irregular',
                'conexao',
                'outro',
            ])->nullable();
            $table->timestamps();

            $table->index(['liga_id', 'estado']);
            $table->index('scheduled_at');
        });

        Schema::create('partida_opcoes_horario', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partida_id')->constrained('partidas')->cascadeOnDelete();
            $table->timestamp('datetime');
            $table->timestamps();
        });

        Schema::create('partida_confirmacoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partida_id')->constrained('partidas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('datetime');
            $table->timestamps();
            $table->unique(['partida_id', 'user_id', 'datetime'], 'partida_confirmacao_unique');
            $table->index(['partida_id', 'datetime']);
        });

        Schema::create('partida_alteracoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partida_id')->constrained('partidas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('old_datetime');
            $table->timestamp('new_datetime');
            $table->timestamps();
        });

        Schema::create('partida_eventos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partida_id')->constrained('partidas')->cascadeOnDelete();
            $table->enum('tipo', [
                'confirmacao_horario',
                'alteracao_horario',
                'wo_declarado',
                'inicio_partida',
                'finalizacao_partida',
            ]);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partida_eventos');
        Schema::dropIfExists('partida_alteracoes');
        Schema::dropIfExists('partida_confirmacoes');
        Schema::dropIfExists('partida_opcoes_horario');
        Schema::dropIfExists('partidas');
        Schema::dropIfExists('user_disponibilidades');
    }
};
