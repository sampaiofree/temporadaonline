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
        Schema::create('escudos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pais_id')->constrained('paises')->cascadeOnDelete();
            $table->string('liga_nome', 150);
            $table->string('clube_nome', 150);
            $table->string('clube_imagem')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['pais_id', 'liga_nome', 'clube_nome', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escudos');
    }
};
