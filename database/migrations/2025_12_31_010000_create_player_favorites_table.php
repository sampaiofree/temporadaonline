<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_favorites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('liga_id')->constrained('ligas')->cascadeOnDelete();
            $table->foreignId('elencopadrao_id')->constrained('elencopadrao')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'liga_id', 'elencopadrao_id'], 'player_favorites_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_favorites');
    }
};
