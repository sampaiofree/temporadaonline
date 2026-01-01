<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liga_propostas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('confederacao_id')->nullable()->constrained('confederacoes')->restrictOnDelete();
            $table->foreignId('liga_origem_id')->constrained('ligas')->cascadeOnDelete();
            $table->foreignId('liga_destino_id')->constrained('ligas')->cascadeOnDelete();
            $table->foreignId('elencopadrao_id')->constrained('elencopadrao')->cascadeOnDelete();
            $table->foreignId('clube_origem_id')->constrained('liga_clubes')->cascadeOnDelete();
            $table->foreignId('clube_destino_id')->constrained('liga_clubes')->cascadeOnDelete();
            $table->bigInteger('valor')->default(0);
            $table->json('oferta_elencopadrao_ids')->nullable();
            $table->string('status', 20)->default('aberta');
            $table->timestamps();

            $table->index(['clube_origem_id', 'status']);
            $table->index(['clube_destino_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liga_propostas');
    }
};
