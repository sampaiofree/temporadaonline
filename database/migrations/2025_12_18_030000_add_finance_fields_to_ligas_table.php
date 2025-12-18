<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ligas', function (Blueprint $table): void {
            $table->unsignedSmallInteger('max_jogadores_por_clube')->default(18);
            $table->bigInteger('saldo_inicial')->default(0);
            $table->decimal('multa_multiplicador', 4, 2)->default(2.00);
            $table->enum('cobranca_salario', ['rodada'])->default('rodada');
            $table->unsignedSmallInteger('venda_min_percent')->default(100);
            $table->boolean('bloquear_compra_saldo_negativo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('ligas', function (Blueprint $table): void {
            $table->dropColumn([
                'max_jogadores_por_clube',
                'saldo_inicial',
                'multa_multiplicador',
                'cobranca_salario',
                'venda_min_percent',
                'bloquear_compra_saldo_negativo',
            ]);
        });
    }
};

