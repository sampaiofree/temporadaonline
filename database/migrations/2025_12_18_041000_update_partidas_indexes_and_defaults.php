<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajuste de default de timezone
        if (app('db')->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE ligas ALTER COLUMN timezone SET DEFAULT 'America/Sao_Paulo'");
        }

        DB::statement("UPDATE ligas SET timezone = 'America/Sao_Paulo' WHERE timezone IS NULL OR timezone = ''");

        // Índices adicionais (usando IF NOT EXISTS para evitar colisão)
        DB::statement('CREATE INDEX IF NOT EXISTS partidas_scheduled_at_index ON partidas (scheduled_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS partida_confirmacoes_partida_datetime_idx ON partida_confirmacoes (partida_id, datetime)');
        DB::statement('CREATE INDEX IF NOT EXISTS user_disponibilidades_user_day_idx ON user_disponibilidades (user_id, dia_semana)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS partidas_scheduled_at_index');
        DB::statement('DROP INDEX IF EXISTS partida_confirmacoes_partida_datetime_idx');
        DB::statement('DROP INDEX IF EXISTS user_disponibilidades_user_day_idx');
        if (app('db')->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE ligas ALTER COLUMN timezone SET DEFAULT 'UTC'");
        }
    }
};
