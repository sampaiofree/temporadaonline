<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conquistas', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->text('descricao');
            $table->string('imagem');
            $table->enum('tipo', ['gols', 'assistencias', 'quantidade_jogos']);
            $table->unsignedInteger('quantidade');
            $table->unsignedInteger('fans');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conquistas');
    }
};
