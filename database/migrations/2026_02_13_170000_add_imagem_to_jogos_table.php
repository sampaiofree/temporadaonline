<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('jogos') || Schema::hasColumn('jogos', 'imagem')) {
            return;
        }

        Schema::table('jogos', function (Blueprint $table): void {
            $table->string('imagem')->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('jogos') || ! Schema::hasColumn('jogos', 'imagem')) {
            return;
        }

        Schema::table('jogos', function (Blueprint $table): void {
            $table->dropColumn('imagem');
        });
    }
};
