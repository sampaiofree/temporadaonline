<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $newTypes = ['placar_registrado', 'placar_confirmado', 'placar_reclamacao'];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $typeName = $this->detectEnumName();

        if (! $typeName) {
            return;
        }

        foreach ($this->newTypes as $type) {
            $exists = DB::select(
                "SELECT 1 FROM pg_type t JOIN pg_enum e ON t.oid = e.enumtypid WHERE t.typname = ? AND e.enumlabel = ?",
                [$typeName, $type]
            );
            if (! $exists) {
                DB::statement("ALTER TYPE \"{$typeName}\" ADD VALUE '{$type}'");
            }
        }
    }

    public function down(): void
    {
        // Cannot easily remove enum values.
    }

    private function detectEnumName(): ?string
    {
        $candidates = ['enum_partida_eventos_tipo', 'partida_eventos_tipo_enum'];
        foreach ($candidates as $candidate) {
            $exists = DB::select("SELECT 1 FROM pg_type WHERE typname = ?", [$candidate]);
            if ($exists) {
                return $candidate;
            }
        }

        return null;
    }
};
