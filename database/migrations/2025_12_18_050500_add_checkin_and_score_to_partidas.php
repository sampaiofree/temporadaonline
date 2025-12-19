<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partidas', function (Blueprint $table): void {
            $table->unsignedSmallInteger('placar_mandante')->nullable()->after('visitante_id');
            $table->unsignedSmallInteger('placar_visitante')->nullable()->after('placar_mandante');
            $table->timestamp('checkin_mandante_at')->nullable()->after('sem_slot_disponivel');
            $table->timestamp('checkin_visitante_at')->nullable()->after('checkin_mandante_at');
        });
    }

    public function down(): void
    {
        Schema::table('partidas', function (Blueprint $table): void {
            $table->dropColumn([
                'placar_mandante',
                'placar_visitante',
                'checkin_mandante_at',
                'checkin_visitante_at',
            ]);
        });
    }
};
