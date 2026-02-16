<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('liga_leiloes')) {
            return;
        }

        $hasLigaId = Schema::hasColumn('liga_leiloes', 'liga_id');

        if (! Schema::hasColumn('liga_leiloes', 'confederacao_id')) {
            Schema::table('liga_leiloes', function (Blueprint $table) use ($hasLigaId): void {
                $after = $hasLigaId ? 'liga_id' : 'id';
                $table->foreignId('confederacao_id')
                    ->nullable()
                    ->after($after)
                    ->constrained('confederacoes')
                    ->cascadeOnDelete();
            });
        }

        if ($hasLigaId) {
            $ligaMap = DB::table('ligas')
                ->pluck('confederacao_id', 'id');

            DB::table('liga_leiloes')
                ->select(['id', 'liga_id'])
                ->orderBy('id')
                ->chunkById(500, function ($rows) use ($ligaMap): void {
                    foreach ($rows as $row) {
                        $confederacaoId = $ligaMap[$row->liga_id] ?? null;
                        if (! $confederacaoId) {
                            continue;
                        }

                        DB::table('liga_leiloes')
                            ->where('id', $row->id)
                            ->update(['confederacao_id' => $confederacaoId]);
                    }
                });
        }

        DB::table('liga_leiloes')
            ->whereNull('confederacao_id')
            ->delete();

        $duplicates = DB::table('liga_leiloes')
            ->select('confederacao_id', 'inicio', 'fim', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->groupBy('confederacao_id', 'inicio', 'fim')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('liga_leiloes')
                ->where('confederacao_id', $duplicate->confederacao_id)
                ->where('inicio', $duplicate->inicio)
                ->where('fim', $duplicate->fim)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }

        if (Schema::hasColumn('liga_leiloes', 'liga_id')) {
            try {
                Schema::table('liga_leiloes', function (Blueprint $table): void {
                    $table->dropIndex('liga_leiloes_liga_id_inicio_fim_index');
                });
            } catch (\Throwable) {
                // Ignora quando o índice não existir neste ambiente.
            }

            Schema::table('liga_leiloes', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('liga_id');
            });
        }

        Schema::table('liga_leiloes', function (Blueprint $table): void {
            $table->unsignedBigInteger('confederacao_id')->nullable(false)->change();
            $table->index(['confederacao_id', 'inicio', 'fim'], 'liga_leiloes_confederacao_inicio_fim_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('liga_leiloes')) {
            return;
        }

        if (! Schema::hasColumn('liga_leiloes', 'liga_id')) {
            Schema::table('liga_leiloes', function (Blueprint $table): void {
                $table->foreignId('liga_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('ligas')
                    ->cascadeOnDelete();
            });
        }

        $ligaByConfederacao = DB::table('ligas')
            ->select('confederacao_id', DB::raw('MIN(id) as liga_id'))
            ->whereNotNull('confederacao_id')
            ->groupBy('confederacao_id')
            ->pluck('liga_id', 'confederacao_id');

        DB::table('liga_leiloes')
            ->select(['id', 'confederacao_id'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($ligaByConfederacao): void {
                foreach ($rows as $row) {
                    $ligaId = $ligaByConfederacao[$row->confederacao_id] ?? null;

                    DB::table('liga_leiloes')
                        ->where('id', $row->id)
                        ->update(['liga_id' => $ligaId]);
                }
            });

        if (Schema::hasColumn('liga_leiloes', 'confederacao_id')) {
            Schema::table('liga_leiloes', function (Blueprint $table): void {
                $table->dropIndex('liga_leiloes_confederacao_inicio_fim_idx');
                $table->dropConstrainedForeignId('confederacao_id');
            });
        }

        Schema::table('liga_leiloes', function (Blueprint $table): void {
            $table->index(['liga_id', 'inicio', 'fim']);
        });
    }
};
