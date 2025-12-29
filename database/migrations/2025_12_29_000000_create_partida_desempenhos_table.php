<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partida_desempenhos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partida_id')->constrained('partidas')->cascadeOnDelete();
            $table->foreignId('liga_clube_id')->constrained('liga_clubes')->cascadeOnDelete();
            $table->foreignId('elencopadrao_id')->constrained('elencopadrao')->cascadeOnDelete();
            $table->decimal('nota', 4, 2);
            $table->unsignedSmallInteger('gols')->default(0);
            $table->unsignedSmallInteger('assistencias')->default(0);
            $table->timestamps();

            $table->unique(['partida_id', 'elencopadrao_id']);
            $table->index(['partida_id', 'liga_clube_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partida_desempenhos');
    }
};
