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
                $table->foreignId('liga_id')->constrained('ligas')->cascadeOnDelete();
                $table->foreignId('liga_clube_id')->constrained('liga_clubes')->cascadeOnDelete();
                $table->foreignId('elencopadrao_id')->constrained('elencopadrao')->cascadeOnDelete();
                $table->bigInteger('value_eur')->nullable();
                $table->bigInteger('wage_eur')->nullable();
                $table->boolean('ativo')->default(true);
                $table->timestamps();

                $table->unique(['liga_id', 'elencopadrao_id']);
            });

            DB::statement('
                INSERT INTO liga_clube_elencos (id, liga_id, liga_clube_id, elencopadrao_id, value_eur, wage_eur, ativo, created_at, updated_at)
                SELECT
                    old.id,
                    (SELECT liga_id FROM liga_clubes WHERE liga_clubes.id = old.liga_clube_id),
                    old.liga_clube_id,
                    old.elencopadrao_id,
                    (SELECT value_eur FROM elencopadrao WHERE elencopadrao.id = old.elencopadrao_id),
                    (SELECT wage_eur FROM elencopadrao WHERE elencopadrao.id = old.elencopadrao_id),
                    1,
                    old.created_at,
                    old.updated_at
                FROM liga_clube_elencos_old old
            ');

            Schema::drop('liga_clube_elencos_old');

            DB::statement('PRAGMA foreign_keys=ON;');

            return;
        }

        Schema::table('liga_clube_elencos', function (Blueprint $table): void {
            if (! Schema::hasColumn('liga_clube_elencos', 'liga_id')) {
                $table->foreignId('liga_id')->nullable()->after('liga_clube_id')->constrained('ligas')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('liga_clube_elencos', 'value_eur')) {
                $table->bigInteger('value_eur')->nullable()->after('elencopadrao_id');
            }

            if (! Schema::hasColumn('liga_clube_elencos', 'wage_eur')) {
                $table->bigInteger('wage_eur')->nullable()->after('value_eur');
            }

            if (! Schema::hasColumn('liga_clube_elencos', 'ativo')) {
                $table->boolean('ativo')->default(true)->after('wage_eur');
            }

            $table->dropUnique('liga_clube_elencos_elencopadrao_id_unique');
        });

        DB::statement('
            UPDATE liga_clube_elencos
            SET liga_id = (
                SELECT liga_id
                FROM liga_clubes
                WHERE liga_clubes.id = liga_clube_elencos.liga_clube_id
            )
            WHERE liga_id IS NULL
        ');

        DB::statement('
            UPDATE liga_clube_elencos
            SET
                value_eur = (SELECT value_eur FROM elencopadrao WHERE elencopadrao.id = liga_clube_elencos.elencopadrao_id),
                wage_eur = (SELECT wage_eur FROM elencopadrao WHERE elencopadrao.id = liga_clube_elencos.elencopadrao_id)
            WHERE value_eur IS NULL OR wage_eur IS NULL
        ');

        Schema::table('liga_clube_elencos', function (Blueprint $table): void {
            $table->unique(['liga_id', 'elencopadrao_id']);
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
                $table->foreignId('liga_clube_id')->constrained('liga_clubes')->cascadeOnDelete();
                $table->foreignId('elencopadrao_id')->constrained('elencopadrao')->cascadeOnDelete();
                $table->timestamps();

                $table->unique('elencopadrao_id');
            });

            DB::statement('
                INSERT INTO liga_clube_elencos (id, liga_clube_id, elencopadrao_id, created_at, updated_at)
                SELECT id, liga_clube_id, elencopadrao_id, created_at, updated_at
                FROM liga_clube_elencos_old
            ');

            Schema::drop('liga_clube_elencos_old');

            DB::statement('PRAGMA foreign_keys=ON;');

            return;
        }

        Schema::table('liga_clube_elencos', function (Blueprint $table): void {
            $table->dropUnique('liga_clube_elencos_liga_id_elencopadrao_id_unique');

            if (Schema::hasColumn('liga_clube_elencos', 'liga_id')) {
                $table->dropConstrainedForeignId('liga_id');
            }

            $table->dropColumn(['value_eur', 'wage_eur', 'ativo']);

            $table->unique('elencopadrao_id');
        });
    }
};

