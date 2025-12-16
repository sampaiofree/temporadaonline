<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jogadores', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('nickname')->unique()->nullable();
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jogadores');
    }
};
