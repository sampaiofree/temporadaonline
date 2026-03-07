<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liga_clube_vendas_mercado', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('confederacao_id');
            $table->unsignedBigInteger('liga_id');
            $table->unsignedBigInteger('liga_clube_id');
            $table->unsignedBigInteger('elencopadrao_id');
            $table->bigInteger('valor_base')->default(0);
            $table->bigInteger('valor_credito')->default(0);
            $table->unsignedInteger('tax_percent')->default(0);
            $table->bigInteger('tax_value')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'confederacao_id'], 'lcvm_user_confederacao_idx');
            $table->index(['confederacao_id', 'created_at'], 'lcvm_confederacao_created_at_idx');
            $table->index('liga_clube_id', 'lcvm_liga_clube_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liga_clube_vendas_mercado');
    }
};
