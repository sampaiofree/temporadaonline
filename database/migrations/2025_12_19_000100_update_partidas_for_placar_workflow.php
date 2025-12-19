<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partidas', function (Blueprint $table): void {
            $table->foreignId('placar_registrado_por')->nullable()->constrained('users')->nullOnDelete()->after('placar_visitante');
            $table->timestamp('placar_registrado_em')->nullable()->after('placar_registrado_por');
        });

        // Afrouxa o tipo para permitir novos valores de estado; funciona mesmo se já for varchar.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE partidas ALTER COLUMN estado TYPE VARCHAR(50) USING estado::text");
            DB::statement("ALTER TABLE partidas ALTER COLUMN estado SET DEFAULT 'confirmacao_necessaria'");
        }
    }

    public function down(): void
    {
        Schema::table('partidas', function (Blueprint $table): void {
            $table->dropForeign(['placar_registrado_por']);
            $table->dropColumn(['placar_registrado_por', 'placar_registrado_em']);
        });
    }
};
