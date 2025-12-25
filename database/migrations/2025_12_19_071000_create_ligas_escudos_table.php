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
        Schema::create('ligas_escudos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pais_id')->constrained('paises')->cascadeOnDelete();
            $table->string('liga_nome', 150);
            $table->string('liga_imagem');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ligas_escudos');
    }
};
