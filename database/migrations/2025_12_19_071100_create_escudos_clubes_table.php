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
        Schema::create('escudos_clubes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pais_id')->constrained('paises')->cascadeOnDelete();
            $table->foreignId('liga_id')->constrained('ligas_escudos')->cascadeOnDelete();
            $table->string('clube_nome', 150);
            $table->string('clube_imagem');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escudos_clubes');
    }
};
