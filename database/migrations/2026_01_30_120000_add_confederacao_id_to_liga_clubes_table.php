<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('liga_clubes')) {
            return;
        }

        Schema::table('liga_clubes', function (Blueprint $table): void {
            if (! Schema::hasColumn('liga_clubes', 'confederacao_id')) {
                $table->foreignId('confederacao_id')
                    ->nullable()
                    ->after('liga_id')
                    ->constrained('confederacoes')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('liga_clubes') || ! Schema::hasColumn('liga_clubes', 'confederacao_id')) {
            return;
        }

        Schema::table('liga_clubes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('confederacao_id');
        });
    }
};
