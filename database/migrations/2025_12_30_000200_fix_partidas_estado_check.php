<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $states = [
            'agendada',
            'confirmacao_necessaria',
            'confirmada',
            'em_andamento',
            'placar_registrado',
            'placar_confirmado',
            'em_reclamacao',
            'finalizada',
            'wo',
            'cancelada',
        ];

        $driver = DB::getDriverName();
        $allowed = implode("','", $states);

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE partidas DROP CONSTRAINT IF EXISTS partidas_estado_check');
            DB::statement("ALTER TABLE partidas ADD CONSTRAINT partidas_estado_check CHECK (estado IN ('{$allowed}'))");
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE partidas MODIFY estado ENUM('{$allowed}') DEFAULT 'confirmacao_necessaria'");
        }
    }

    public function down(): void
    {
        $states = [
            'agendada',
            'confirmacao_necessaria',
            'confirmada',
            'em_andamento',
            'finalizada',
            'wo',
            'cancelada',
        ];

        $driver = DB::getDriverName();
        $allowed = implode("','", $states);

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE partidas DROP CONSTRAINT IF EXISTS partidas_estado_check');
            DB::statement("ALTER TABLE partidas ADD CONSTRAINT partidas_estado_check CHECK (estado IN ('{$allowed}'))");
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE partidas MODIFY estado ENUM('{$allowed}') DEFAULT 'confirmacao_necessaria'");
        }
    }
};
