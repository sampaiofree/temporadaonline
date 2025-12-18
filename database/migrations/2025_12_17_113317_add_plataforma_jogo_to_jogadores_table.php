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
        Schema::table('jogadores', function (Blueprint $table) {
            $table->foreignId('plataforma_id')->nullable()->constrained('plataformas')->nullOnDelete();
            $table->foreignId('jogo_id')->nullable()->constrained('jogos')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jogadores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plataforma_id');
            $table->dropConstrainedForeignId('jogo_id');
        });
    }
};
