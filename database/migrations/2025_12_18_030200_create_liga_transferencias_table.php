<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liga_transferencias', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('liga_id')->constrained('ligas')->cascadeOnDelete();
            $table->foreignId('elencopadrao_id')->constrained('elencopadrao')->cascadeOnDelete();
            $table->foreignId('clube_origem_id')->nullable()->constrained('liga_clubes')->nullOnDelete();
            $table->foreignId('clube_destino_id')->constrained('liga_clubes')->cascadeOnDelete();
            $table->enum('tipo', ['compra', 'venda', 'troca', 'multa', 'jogador_livre']);
            $table->bigInteger('valor')->default(0);
            $table->string('observacao')->nullable();
            $table->timestamps();

            $table->index(['liga_id', 'elencopadrao_id']);
            $table->index(['liga_id', 'clube_destino_id']);
            $table->index(['liga_id', 'clube_origem_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liga_transferencias');
    }
};

