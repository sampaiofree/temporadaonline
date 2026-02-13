<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('profiles')) {
            return;
        }

        $hasRegiaoId = Schema::hasColumn('profiles', 'regiao_id');
        $hasIdiomaId = Schema::hasColumn('profiles', 'idioma_id');

        if (! $hasRegiaoId || ! $hasIdiomaId) {
            Schema::table('profiles', function (Blueprint $table) use ($hasRegiaoId, $hasIdiomaId): void {
                if (! $hasRegiaoId) {
                    $table->foreignId('regiao_id')->nullable()->after('regiao')->constrained('regioes')->nullOnDelete();
                }

                if (! $hasIdiomaId) {
                    $table->foreignId('idioma_id')->nullable()->after('idioma')->constrained('idiomas')->nullOnDelete();
                }
            });
        }

        $dropColumns = [];
        foreach (['plataforma', 'geracao', 'jogo'] as $column) {
            if (Schema::hasColumn('profiles', $column)) {
                $dropColumns[] = $column;
            }
        }

        if ($dropColumns !== []) {
            Schema::table('profiles', function (Blueprint $table) use ($dropColumns): void {
                $table->dropColumn($dropColumns);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('profiles')) {
            return;
        }

        $addColumns = [];
        foreach (['plataforma', 'geracao', 'jogo'] as $column) {
            if (! Schema::hasColumn('profiles', $column)) {
                $addColumns[] = $column;
            }
        }

        if ($addColumns !== []) {
            Schema::table('profiles', function (Blueprint $table) use ($addColumns): void {
                foreach ($addColumns as $column) {
                    $table->string($column)->nullable();
                }
            });
        }

        if (Schema::hasColumn('profiles', 'idioma_id')) {
            Schema::table('profiles', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('idioma_id');
            });
        }

        if (Schema::hasColumn('profiles', 'regiao_id')) {
            Schema::table('profiles', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('regiao_id');
            });
        }
    }
};
