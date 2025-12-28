<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liga_periodos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('liga_id')->constrained('ligas')->cascadeOnDelete();
            $table->date('inicio');
            $table->date('fim');
            $table->timestamps();

            $table->index(['liga_id', 'inicio', 'fim']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liga_periodos');
    }
};
