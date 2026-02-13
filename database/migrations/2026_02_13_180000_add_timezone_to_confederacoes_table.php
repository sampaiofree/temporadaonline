<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('confederacoes')) {
            return;
        }

        if (! Schema::hasColumn('confederacoes', 'timezone')) {
            $afterColumn = Schema::hasColumn('confederacoes', 'plataforma_id') ? 'plataforma_id' : 'imagem';

            Schema::table('confederacoes', function (Blueprint $table) use ($afterColumn): void {
                $table->string('timezone', 64)->default('America/Sao_Paulo')->after($afterColumn);
            });
        }

        $confederacaoIds = DB::table('confederacoes')->pluck('id');

        foreach ($confederacaoIds as $confederacaoId) {
            $timezone = DB::table('ligas')
                ->select('timezone', DB::raw('COUNT(*) as total'))
                ->where('confederacao_id', $confederacaoId)
                ->whereNotNull('timezone')
                ->where('timezone', '<>', '')
                ->groupBy('timezone')
                ->orderByDesc('total')
                ->orderBy('timezone')
                ->value('timezone');

            if (! $timezone) {
                continue;
            }

            DB::table('confederacoes')
                ->where('id', $confederacaoId)
                ->update(['timezone' => $timezone]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('confederacoes') || ! Schema::hasColumn('confederacoes', 'timezone')) {
            return;
        }

        Schema::table('confederacoes', function (Blueprint $table): void {
            $table->dropColumn('timezone');
        });
    }
};
