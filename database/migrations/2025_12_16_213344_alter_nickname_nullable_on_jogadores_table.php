<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite não suporta MODIFY COLUMN; recriamos a tabela com nickname opcional.
            DB::statement('PRAGMA foreign_keys=OFF;');

            Schema::rename('jogadores', 'jogadores_old');

            DB::statement('DROP INDEX IF EXISTS jogadores_email_unique;');
            DB::statement('DROP INDEX IF EXISTS jogadores_nickname_unique;');

            Schema::create('jogadores', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->string('email')->unique();
                $table->string('nickname')->nullable()->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('avatar')->nullable();
                $table->string('whatsapp', 20)->nullable();
                $table->string('plataforma')->nullable();
                $table->string('geracao')->nullable();
                $table->string('jogo')->nullable();
                $table->string('regiao')->default('Brasil');
                $table->string('idioma')->default('Português do Brasil');
                $table->unsignedTinyInteger('reputacao_score')->default(99);
                $table->unsignedInteger('nivel')->default(0);
                $table->rememberToken();
                $table->timestamps();
            });

            DB::statement('
                INSERT INTO jogadores (id, nome, email, nickname, email_verified_at, password, avatar, whatsapp, plataforma, geracao, jogo, regiao, idioma, reputacao_score, nivel, remember_token, created_at, updated_at)
                SELECT id, nome, email, nickname, email_verified_at, password, avatar, whatsapp, plataforma, geracao, jogo, regiao, idioma, reputacao_score, nivel, remember_token, created_at, updated_at
                FROM jogadores_old
            ');

            Schema::drop('jogadores_old');

            DB::statement('PRAGMA foreign_keys=ON;');
        } else {
            Schema::table('jogadores', function (Blueprint $table) {
                $table->string('nickname')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF;');

            Schema::rename('jogadores', 'jogadores_old');

            DB::statement('DROP INDEX IF EXISTS jogadores_email_unique;');
            DB::statement('DROP INDEX IF EXISTS jogadores_nickname_unique;');

            Schema::create('jogadores', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->string('email')->unique();
                $table->string('nickname')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('avatar')->nullable();
                $table->string('whatsapp', 20)->nullable();
                $table->string('plataforma')->nullable();
                $table->string('geracao')->nullable();
                $table->string('jogo')->nullable();
                $table->string('regiao')->default('Brasil');
                $table->string('idioma')->default('Português do Brasil');
                $table->unsignedTinyInteger('reputacao_score')->default(99);
                $table->unsignedInteger('nivel')->default(0);
                $table->rememberToken();
                $table->timestamps();
            });

            DB::statement('
                INSERT INTO jogadores (id, nome, email, nickname, email_verified_at, password, avatar, whatsapp, plataforma, geracao, jogo, regiao, idioma, reputacao_score, nivel, remember_token, created_at, updated_at)
                SELECT id, nome, email, nickname, email_verified_at, password, avatar, whatsapp, plataforma, geracao, jogo, regiao, idioma, reputacao_score, nivel, remember_token, created_at, updated_at
                FROM jogadores_old
            ');

            Schema::drop('jogadores_old');

            DB::statement('PRAGMA foreign_keys=ON;');
        } else {
            Schema::table('jogadores', function (Blueprint $table) {
                $table->string('nickname')->unique()->change();
            });
        }
    }
};
