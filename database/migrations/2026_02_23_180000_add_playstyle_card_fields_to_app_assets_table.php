<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_assets', function (Blueprint $table): void {
            $table->string('card_completo')->nullable()->after('background_app');
            $table->string('card_reduzido')->nullable()->after('card_completo');
            $table->string('img_jogador')->nullable()->after('card_reduzido');
        });
    }

    public function down(): void
    {
        Schema::table('app_assets', function (Blueprint $table): void {
            $table->dropColumn(['card_completo', 'card_reduzido', 'img_jogador']);
        });
    }
};
