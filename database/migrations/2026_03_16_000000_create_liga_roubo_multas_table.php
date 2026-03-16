<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liga_roubo_multas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('confederacao_id')->constrained('confederacoes')->cascadeOnDelete();
            $table->dateTime('inicio');
            $table->dateTime('fim');
            $table->timestamps();

            $table->index(['confederacao_id', 'inicio', 'fim'], 'liga_roubo_multas_conf_inicio_fim_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liga_roubo_multas');
    }
};
