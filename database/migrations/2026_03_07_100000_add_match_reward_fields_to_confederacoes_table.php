<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('confederacoes')) {
            return;
        }

        Schema::table('confederacoes', function (Blueprint $table): void {
            if (! Schema::hasColumn('confederacoes', 'ganho_vitoria_partida')) {
                $table->bigInteger('ganho_vitoria_partida')->default(750000)->after('timezone');
            }

            if (! Schema::hasColumn('confederacoes', 'ganho_empate_partida')) {
                $table->bigInteger('ganho_empate_partida')->default(300000)->after('ganho_vitoria_partida');
            }

            if (! Schema::hasColumn('confederacoes', 'ganho_derrota_partida')) {
                $table->bigInteger('ganho_derrota_partida')->default(50000)->after('ganho_empate_partida');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('confederacoes')) {
            return;
        }

        Schema::table('confederacoes', function (Blueprint $table): void {
            if (Schema::hasColumn('confederacoes', 'ganho_derrota_partida')) {
                $table->dropColumn('ganho_derrota_partida');
            }

            if (Schema::hasColumn('confederacoes', 'ganho_empate_partida')) {
                $table->dropColumn('ganho_empate_partida');
            }

            if (Schema::hasColumn('confederacoes', 'ganho_vitoria_partida')) {
                $table->dropColumn('ganho_vitoria_partida');
            }
        });
    }
};
