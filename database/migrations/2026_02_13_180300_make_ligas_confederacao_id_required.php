<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ligas') || ! Schema::hasColumn('ligas', 'confederacao_id')) {
            return;
        }

        $nullCount = DB::table('ligas')->whereNull('confederacao_id')->count();
        if ($nullCount > 0) {
            throw new RuntimeException(
                "Nao foi possivel tornar ligas.confederacao_id obrigatorio: {$nullCount} registros sem confederacao."
            );
        }

        Schema::table('ligas', function (Blueprint $table): void {
            $table->unsignedBigInteger('confederacao_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ligas') || ! Schema::hasColumn('ligas', 'confederacao_id')) {
            return;
        }

        Schema::table('ligas', function (Blueprint $table): void {
            $table->unsignedBigInteger('confederacao_id')->nullable()->change();
        });
    }
};
