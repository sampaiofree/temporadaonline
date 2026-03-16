<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicated players per jogo keeping the oldest id when player_id is set
        DB::statement(
            "DELETE FROM elencopadrao WHERE id IN (
                SELECT id FROM (
                    SELECT id,
                           ROW_NUMBER() OVER (PARTITION BY jogo_id, player_id ORDER BY id) AS rn
                    FROM elencopadrao
                    WHERE player_id IS NOT NULL
                ) t
                WHERE t.rn > 1
            )"
        );

        Schema::table('elencopadrao', function (Blueprint $table) {
            $table->dropUnique('elencopadrao_jogo_id_long_name_unique');
            $table->unique(['jogo_id', 'player_id'], 'elencopadrao_jogo_id_player_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('elencopadrao', function (Blueprint $table) {
            $table->dropUnique('elencopadrao_jogo_id_player_id_unique');
            $table->unique(['jogo_id', 'long_name'], 'elencopadrao_jogo_id_long_name_unique');
        });
    }
};
