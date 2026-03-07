<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('liga_transferencias')) {
            Schema::table('liga_transferencias', function (Blueprint $table): void {
                $table->index(['confederacao_id', 'tipo', 'clube_destino_id'], 'liga_transferencias_conf_tipo_dest_idx');
            });
        }

        if (Schema::hasTable('liga_propostas')) {
            Schema::table('liga_propostas', function (Blueprint $table): void {
                $table->index(['confederacao_id', 'clube_destino_id'], 'liga_propostas_conf_destino_idx');
                $table->index(['confederacao_id', 'clube_origem_id'], 'liga_propostas_conf_origem_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('liga_transferencias')) {
            Schema::table('liga_transferencias', function (Blueprint $table): void {
                $table->dropIndex('liga_transferencias_conf_tipo_dest_idx');
            });
        }

        if (Schema::hasTable('liga_propostas')) {
            Schema::table('liga_propostas', function (Blueprint $table): void {
                $table->dropIndex('liga_propostas_conf_destino_idx');
                $table->dropIndex('liga_propostas_conf_origem_idx');
            });
        }
    }
};
