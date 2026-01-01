<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('player_favorites')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF;');

            Schema::rename('player_favorites', 'player_favorites_old');

            Schema::create('player_favorites', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('liga_id')->constrained('ligas')->cascadeOnDelete();
                $table->foreignId('confederacao_id')->nullable()->constrained('confederacoes')->restrictOnDelete();
                $table->foreignId('elencopadrao_id')->constrained('elencopadrao')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'confederacao_id', 'elencopadrao_id'], 'player_favorites_confederacao_unique');
            });

            DB::statement('
                INSERT INTO player_favorites (id, user_id, liga_id, confederacao_id, elencopadrao_id, created_at, updated_at)
                SELECT
                    old.id,
                    old.user_id,
                    old.liga_id,
                    (SELECT confederacao_id FROM ligas WHERE ligas.id = old.liga_id),
                    old.elencopadrao_id,
                    old.created_at,
                    old.updated_at
                FROM player_favorites_old old
            ');

            Schema::drop('player_favorites_old');

            DB::statement('PRAGMA foreign_keys=ON;');

            return;
        }

        Schema::table('player_favorites', function (Blueprint $table): void {
            if (! Schema::hasColumn('player_favorites', 'confederacao_id')) {
                $table->foreignId('confederacao_id')
                    ->nullable()
                    ->after('liga_id')
                    ->constrained('confederacoes')
                    ->restrictOnDelete();
            }

            $table->dropUnique('player_favorites_unique');
        });

        DB::statement('
            UPDATE player_favorites
            SET confederacao_id = (
                SELECT confederacao_id
                FROM ligas
                WHERE ligas.id = player_favorites.liga_id
            )
            WHERE confederacao_id IS NULL
        ');

        Schema::table('player_favorites', function (Blueprint $table): void {
            $table->unique(['user_id', 'confederacao_id', 'elencopadrao_id'], 'player_favorites_confederacao_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('player_favorites')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF;');

            Schema::rename('player_favorites', 'player_favorites_old');

            Schema::create('player_favorites', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('liga_id')->constrained('ligas')->cascadeOnDelete();
                $table->foreignId('elencopadrao_id')->constrained('elencopadrao')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'liga_id', 'elencopadrao_id'], 'player_favorites_unique');
            });

            DB::statement('
                INSERT INTO player_favorites (id, user_id, liga_id, elencopadrao_id, created_at, updated_at)
                SELECT
                    id,
                    user_id,
                    liga_id,
                    elencopadrao_id,
                    created_at,
                    updated_at
                FROM player_favorites_old
            ');

            Schema::drop('player_favorites_old');

            DB::statement('PRAGMA foreign_keys=ON;');

            return;
        }

        Schema::table('player_favorites', function (Blueprint $table): void {
            $table->dropUnique('player_favorites_confederacao_unique');

            if (Schema::hasColumn('player_favorites', 'confederacao_id')) {
                $table->dropConstrainedForeignId('confederacao_id');
            }

            $table->unique(['user_id', 'liga_id', 'elencopadrao_id'], 'player_favorites_unique');
        });
    }
};
