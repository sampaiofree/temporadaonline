<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrocinios', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150)->unique();
            $table->text('descricao')->nullable();
            $table->string('imagem');
            $table->unsignedInteger('valor');
            $table->unsignedInteger('fans');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrocinios');
    }
};
