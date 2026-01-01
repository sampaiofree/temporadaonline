<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('liga_clube_elencos')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF;');

            Schema::rename('liga_clube_elencos', 'liga_clube_elencos_old');

            Schema::create('liga_clube_elencos', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('confederacao_id')->nullable()->constrained('confederacoes')->restrictOnDelete();
                $table->foreignId('liga_id')->nullable()->constrained('ligas')->cascadeOnDelete();
                $table->foreignId('liga_clube_id')->constrained('liga_clubes')->cascadeOnDelete();
                $table->foreignId('elencopadrao_id')->constrained('elencopadrao')->cascadeOnDelete();
                $table->bigInteger('value_eur')->nullable();
                $table->bigInteger('wage_eur')->nullable();
                $table->boolean('ativo')->default(true);
                $table->timestamps();

                $table->unique(['confederacao_id', 'elencopadrao_id'], 'liga_clube_elencos_confederacao_elencopadrao_unique');
                $table->index(['liga_id', 'elencopadrao_id'], 'liga_clube_elencos_liga_elencopadrao_index');
            });

            DB::statement('
                INSERT INTO liga_clube_elencos (id, confederacao_id, liga_id, liga_clube_id, elencopadrao_id, value_eur, wage_eur, ativo, created_at, updated_at)
                SELECT
                    old.id,
                    (SELECT confederacao_id FROM ligas WHERE ligas.id = old.liga_id),
                    old.liga_id,
                    old.liga_clube_id,
                    old.elencopadrao_id,
                    old.value_eur,
                    old.wage_eur,
                    old.ativo,
                    old.created_at,
                    old.updated_at
                FROM liga_clube_elencos_old old
            ');

            Schema::drop('liga_clube_elencos_old');

            DB::statement('PRAGMA foreign_keys=ON;');

            return;
        }

        Schema::table('liga_clube_elencos', function (Blueprint $table): void {
            if (! Schema::hasColumn('liga_clube_elencos', 'confederacao_id')) {
                $table->foreignId('confederacao_id')
                    ->nullable()
                    ->after('liga_id')
                    ->constrained('confederacoes')
                    ->restrictOnDelete();
            }

            $table->dropUnique('liga_clube_elencos_liga_id_elencopadrao_id_unique');
        });

        DB::statement('
            UPDATE liga_clube_elencos
            SET confederacao_id = (
                SELECT confederacao_id
                FROM ligas
                WHERE ligas.id = liga_clube_elencos.liga_id
            )
            WHERE confederacao_id IS NULL
        ');

        Schema::table('liga_clube_elencos', function (Blueprint $table): void {
            $table->unique(['confederacao_id', 'elencopadrao_id'], 'liga_clube_elencos_confederacao_elencopadrao_unique');
            $table->index(['liga_id', 'elencopadrao_id'], 'liga_clube_elencos_liga_elencopadrao_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('liga_clube_elencos')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF;');

            Schema::rename('liga_clube_elencos', 'liga_clube_elencos_old');

            Schema::create('liga_clube_elencos', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('liga_id')->nullable()->constrained('ligas')->cascadeOnDelete();
                $table->foreignId('liga_clube_id')->constrained('liga_clubes')->cascadeOnDelete();
                $table->foreignId('elencopadrao_id')->constrained('elencopadrao')->cascadeOnDelete();
                $table->bigInteger('value_eur')->nullable();
                $table->bigInteger('wage_eur')->nullable();
                $table->boolean('ativo')->default(true);
                $table->timestamps();

                $table->unique(['liga_id', 'elencopadrao_id'], 'liga_clube_elencos_liga_id_elencopadrao_id_unique');
            });

            DB::statement('
                INSERT INTO liga_clube_elencos (id, liga_id, liga_clube_id, elencopadrao_id, value_eur, wage_eur, ativo, created_at, updated_at)
                SELECT
                    id,
                    liga_id,
                    liga_clube_id,
                    elencopadrao_id,
                    value_eur,
                    wage_eur,
                    ativo,
                    created_at,
                    updated_at
                FROM liga_clube_elencos_old
            ');

            Schema::drop('liga_clube_elencos_old');

            DB::statement('PRAGMA foreign_keys=ON;');

            return;
        }

        Schema::table('liga_clube_elencos', function (Blueprint $table): void {
            $table->dropUnique('liga_clube_elencos_confederacao_elencopadrao_unique');
            $table->dropIndex('liga_clube_elencos_liga_elencopadrao_index');

            if (Schema::hasColumn('liga_clube_elencos', 'confederacao_id')) {
                $table->dropConstrainedForeignId('confederacao_id');
            }

            $table->unique(['liga_id', 'elencopadrao_id'], 'liga_clube_elencos_liga_id_elencopadrao_id_unique');
        });
    }
};
