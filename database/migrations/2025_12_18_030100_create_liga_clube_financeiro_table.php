<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liga_clube_financeiro', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('liga_id')->constrained('ligas')->cascadeOnDelete();
            $table->foreignId('clube_id')->constrained('liga_clubes')->cascadeOnDelete();
            $table->bigInteger('saldo')->default(0);
            $table->timestamps();

            $table->unique(['liga_id', 'clube_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liga_clube_financeiro');
    }
};

