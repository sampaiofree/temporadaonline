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
        Schema::table('confederacoes', function (Blueprint $table): void {
            if (! Schema::hasColumn('confederacoes', 'jogo_id')) {
                $table->foreignId('jogo_id')
                    ->nullable()
                    ->after('imagem')
                    ->constrained('jogos')
                    ->restrictOnDelete();
            }
            if (! Schema::hasColumn('confederacoes', 'geracao_id')) {
                $table->foreignId('geracao_id')
                    ->nullable()
                    ->after('jogo_id')
                    ->constrained('geracoes')
                    ->restrictOnDelete();
            }
            if (! Schema::hasColumn('confederacoes', 'plataforma_id')) {
                $table->foreignId('plataforma_id')
                    ->nullable()
                    ->after('geracao_id')
                    ->constrained('plataformas')
                    ->restrictOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('confederacoes', function (Blueprint $table): void {
            if (Schema::hasColumn('confederacoes', 'plataforma_id')) {
                $table->dropConstrainedForeignId('plataforma_id');
            }
            if (Schema::hasColumn('confederacoes', 'geracao_id')) {
                $table->dropConstrainedForeignId('geracao_id');
            }
            if (Schema::hasColumn('confederacoes', 'jogo_id')) {
                $table->dropConstrainedForeignId('jogo_id');
            }
        });
    }
};
