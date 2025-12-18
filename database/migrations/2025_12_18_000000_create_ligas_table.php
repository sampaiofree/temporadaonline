<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ligas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descricao');
            $table->text('regras');
            $table->string('imagem')->nullable();
            $table->enum('tipo', ['publica', 'privada'])->default('publica');
            $table->enum('status', ['ativa', 'encerrada', 'aguardando'])->default('aguardando');
            $table->unsignedSmallInteger('max_times')->default(20);
            $table->foreignId('jogo_id')->constrained('jogos')->cascadeOnDelete();
            $table->foreignId('geracao_id')->constrained('geracoes')->cascadeOnDelete();
            $table->foreignId('plataforma_id')->constrained('plataformas')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ligas');
    }
};
