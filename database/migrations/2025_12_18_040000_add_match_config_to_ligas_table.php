<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ligas', function (Blueprint $table): void {
            $table->json('dias_permitidos')->default('[]');
            $table->json('horarios_permitidos')->default('[]');
            $table->integer('antecedencia_minima_alteracao_horas')->default(10);
            $table->integer('max_alteracoes_horario')->default(1);
            $table->integer('prazo_confirmacao_horas')->default(48);
            $table->string('timezone', 64)->default('America/Sao_Paulo');
        });
    }

    public function down(): void
    {
        Schema::table('ligas', function (Blueprint $table): void {
            $table->dropColumn([
                'dias_permitidos',
                'horarios_permitidos',
                'antecedencia_minima_alteracao_horas',
                'max_alteracoes_horario',
                'prazo_confirmacao_horas',
                'timezone',
            ]);
        });
    }
};
