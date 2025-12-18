<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liga_folha_pagamento', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('liga_id')->constrained('ligas')->cascadeOnDelete();
            $table->unsignedInteger('rodada');
            $table->foreignId('clube_id')->constrained('liga_clubes')->cascadeOnDelete();
            $table->bigInteger('total_wage');
            $table->timestamps();

            $table->unique(['liga_id', 'rodada', 'clube_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liga_folha_pagamento');
    }
};

