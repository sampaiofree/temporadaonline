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
        Schema::table('liga_clubes', function (Blueprint $table): void {
            $table->string('esquema_tatico_imagem')->nullable();
            $table->json('esquema_tatico_layout')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('liga_clubes', function (Blueprint $table): void {
            $table->dropColumn(['esquema_tatico_layout', 'esquema_tatico_imagem']);
        });
    }
};
