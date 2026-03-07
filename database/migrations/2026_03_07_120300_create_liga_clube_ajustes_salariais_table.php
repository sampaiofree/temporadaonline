<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liga_clube_ajustes_salariais', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('confederacao_id');
            $table->unsignedBigInteger('liga_id');
            $table->unsignedBigInteger('liga_clube_id');
            $table->unsignedBigInteger('liga_clube_elenco_id');
            $table->bigInteger('wage_anterior')->default(0);
            $table->bigInteger('wage_novo')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'confederacao_id'], 'lcas_user_confederacao_idx');
            $table->index(['confederacao_id', 'created_at'], 'lcas_confederacao_created_at_idx');
            $table->index('liga_clube_elenco_id', 'lcas_elenco_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liga_clube_ajustes_salariais');
    }
};
