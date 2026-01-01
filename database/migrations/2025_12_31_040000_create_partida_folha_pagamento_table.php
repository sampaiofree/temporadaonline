<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partida_folha_pagamento', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('liga_id')->constrained('ligas')->cascadeOnDelete();
            $table->foreignId('partida_id')->constrained('partidas')->cascadeOnDelete();
            $table->foreignId('clube_id')->constrained('liga_clubes')->cascadeOnDelete();
            $table->bigInteger('total_wage');
            $table->bigInteger('multa_wo')->default(0);
            $table->timestamps();

            $table->unique(['partida_id', 'clube_id']);
            $table->index(['clube_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partida_folha_pagamento');
    }
};
