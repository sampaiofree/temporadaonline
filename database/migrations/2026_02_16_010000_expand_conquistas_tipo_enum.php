<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $oldTipos = [
        'gols',
        'assistencias',
        'quantidade_jogos',
    ];

    /**
     * @var array<int, string>
     */
    private array $newTipos = [
        'gols',
        'assistencias',
        'quantidade_jogos',
        'skill_rating',
        'score',
        'n_gols_sofridos',
        'n_vitorias',
        'n_hat_trick',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('conquistas')) {
            return;
        }

        $this->updateTipoConstraint($this->newTipos, 'quantidade_jogos');
    }

    public function down(): void
    {
        if (! Schema::hasTable('conquistas')) {
            return;
        }

        DB::table('conquistas')
            ->whereNotIn('tipo', $this->oldTipos)
            ->update(['tipo' => 'quantidade_jogos']);

        $this->updateTipoConstraint($this->oldTipos, 'quantidade_jogos');
    }

    /**
     * @param array<int, string> $tipos
     */
    private function updateTipoConstraint(array $tipos, string $fallback): void
    {
        $driver = DB::getDriverName();
        $allowed = implode("','", $tipos);

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE conquistas MODIFY tipo ENUM('{$allowed}') NOT NULL");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE conquistas DROP CONSTRAINT IF EXISTS conquistas_tipo_check');
            DB::statement("ALTER TABLE conquistas ADD CONSTRAINT conquistas_tipo_check CHECK (tipo IN ('{$allowed}'))");

            return;
        }

        if ($driver === 'sqlite') {
            $this->rebuildSqliteConquistasTable($tipos, $fallback);
        }
    }

    /**
     * @param array<int, string> $tipos
     */
    private function rebuildSqliteConquistasTable(array $tipos, string $fallback): void
    {
        DB::statement('PRAGMA foreign_keys=OFF;');

        if (Schema::hasTable('conquistas_tmp')) {
            Schema::drop('conquistas_tmp');
        }

        Schema::create('conquistas_tmp', function (Blueprint $table) use ($tipos): void {
            $table->id();
            $table->string('nome', 150);
            $table->text('descricao');
            $table->string('imagem');
            $table->enum('tipo', $tipos);
            $table->unsignedInteger('quantidade');
            $table->unsignedInteger('fans');
            $table->timestamps();
        });

        $allowed = implode("','", $tipos);

        DB::statement("
            INSERT INTO conquistas_tmp (id, nome, descricao, imagem, tipo, quantidade, fans, created_at, updated_at)
            SELECT
                id,
                nome,
                descricao,
                imagem,
                CASE
                    WHEN tipo IN ('{$allowed}') THEN tipo
                    ELSE '{$fallback}'
                END,
                quantidade,
                fans,
                created_at,
                updated_at
            FROM conquistas
        ");

        Schema::drop('conquistas');
        Schema::rename('conquistas_tmp', 'conquistas');

        DB::statement('PRAGMA foreign_keys=ON;');
    }
};
