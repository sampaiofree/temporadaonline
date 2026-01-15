<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $allowed = [
        'confirmacao_horario',
        'alteracao_horario',
        'wo_declarado',
        'inicio_partida',
        'finalizacao_partida',
        'placar_registrado',
        'placar_confirmado',
        'placar_reclamacao',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $allowedList = implode("','", $this->allowed);

        DB::statement('ALTER TABLE partida_eventos DROP CONSTRAINT IF EXISTS partida_eventos_tipo_check');
        DB::statement("ALTER TABLE partida_eventos ADD CONSTRAINT partida_eventos_tipo_check CHECK (tipo IN ('{$allowedList}'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $original = [
            'confirmacao_horario',
            'alteracao_horario',
            'wo_declarado',
            'inicio_partida',
            'finalizacao_partida',
        ];
        $allowedList = implode("','", $original);

        DB::statement('ALTER TABLE partida_eventos DROP CONSTRAINT IF EXISTS partida_eventos_tipo_check');
        DB::statement("ALTER TABLE partida_eventos ADD CONSTRAINT partida_eventos_tipo_check CHECK (tipo IN ('{$allowedList}'))");
    }
};
