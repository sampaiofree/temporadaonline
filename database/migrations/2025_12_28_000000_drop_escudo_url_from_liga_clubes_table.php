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
            if (Schema::hasColumn('liga_clubes', 'escudo_url')) {
                $table->dropColumn('escudo_url');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('liga_clubes')) {
            return;
        }

        Schema::table('liga_clubes', function (Blueprint $table): void {
            if (! Schema::hasColumn('liga_clubes', 'escudo_url')) {
                $table->string('escudo_url')->nullable();
            }
        });
    }
};
