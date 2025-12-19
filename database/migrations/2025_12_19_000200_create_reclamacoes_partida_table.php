<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reclamacoes_partida', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partida_id')->constrained('partidas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('motivo', [
                'placar_incorreto',
                'wo_indevido',
                'queda_conexao',
                'outro',
            ]);
            $table->text('descricao');
            $table->string('imagem')->nullable();
            $table->enum('status', ['aberta', 'resolvida'])->default('aberta');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamacoes_partida');
    }
};
