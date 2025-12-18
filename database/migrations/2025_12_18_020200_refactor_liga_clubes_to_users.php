<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('liga_clubes')) {
            return;
        }

        Schema::table('liga_clubes', function (Blueprint $table): void {
            if (! Schema::hasColumn('liga_clubes', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('liga_id');
            }
        });

        if (Schema::hasColumn('liga_clubes', 'jogador_id') && Schema::hasTable('profiles')) {
            DB::statement("
                UPDATE liga_clubes
                SET user_id = (
                    SELECT profiles.user_id
                    FROM profiles
                    WHERE profiles.id = liga_clubes.jogador_id
                )
                WHERE user_id IS NULL
            ");
        }

        Schema::table('liga_clubes', function (Blueprint $table): void {
            if (Schema::hasColumn('liga_clubes', 'jogador_id')) {
                $table->dropConstrainedForeignId('jogador_id');
            }

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['liga_id', 'user_id']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('liga_clubes')) {
            return;
        }

        Schema::table('liga_clubes', function (Blueprint $table): void {
            if (Schema::hasColumn('liga_clubes', 'user_id')) {
                $table->dropUnique('liga_clubes_liga_id_user_id_unique');
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }

            if (! Schema::hasColumn('liga_clubes', 'jogador_id')) {
                $table->foreignId('jogador_id')->constrained('jogadores')->cascadeOnDelete();
            }
        });
    }
};

