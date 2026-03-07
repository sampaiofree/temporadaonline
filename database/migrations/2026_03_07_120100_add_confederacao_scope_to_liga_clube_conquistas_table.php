<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('liga_clube_conquistas')) {
            return;
        }

        Schema::table('liga_clube_conquistas', function (Blueprint $table): void {
            if (! Schema::hasColumn('liga_clube_conquistas', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('liga_clube_id');
            }

            if (! Schema::hasColumn('liga_clube_conquistas', 'confederacao_id')) {
                $table->unsignedBigInteger('confederacao_id')->nullable()->after('user_id');
            }
        });

        $this->backfillScopeColumns();
        $this->deduplicateScopeRows();
        $this->dropRowsWithoutScope();

        Schema::table('liga_clube_conquistas', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->unsignedBigInteger('confederacao_id')->nullable(false)->change();

            $table->index(['user_id', 'confederacao_id'], 'lcc_user_confederacao_idx');
            $table->unique(['user_id', 'confederacao_id', 'conquista_id'], 'lcc_user_confederacao_conquista_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('liga_clube_conquistas')) {
            return;
        }

        Schema::table('liga_clube_conquistas', function (Blueprint $table): void {
            if (Schema::hasColumn('liga_clube_conquistas', 'user_id')) {
                $table->dropUnique('lcc_user_confederacao_conquista_unique');
                $table->dropIndex('lcc_user_confederacao_idx');
                $table->dropColumn('user_id');
            }

            if (Schema::hasColumn('liga_clube_conquistas', 'confederacao_id')) {
                $table->dropColumn('confederacao_id');
            }
        });
    }

    private function backfillScopeColumns(): void
    {
        $clubes = DB::table('liga_clubes')
            ->select(['id', 'user_id', 'confederacao_id', 'liga_id'])
            ->get()
            ->keyBy('id');

        $ligasConfederacao = DB::table('ligas')
            ->select(['id', 'confederacao_id'])
            ->pluck('confederacao_id', 'id');

        DB::table('liga_clube_conquistas')
            ->select(['id', 'liga_clube_id', 'liga_id'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($clubes, $ligasConfederacao): void {
                foreach ($rows as $row) {
                    $clube = $clubes->get((int) $row->liga_clube_id);
                    $userId = $clube?->user_id;
                    $confederacaoId = $clube?->confederacao_id
                        ?? $ligasConfederacao->get((int) ($row->liga_id ?? $clube?->liga_id ?? 0));

                    DB::table('liga_clube_conquistas')
                        ->where('id', $row->id)
                        ->update([
                            'user_id' => $userId,
                            'confederacao_id' => $confederacaoId,
                        ]);
                }
            });
    }

    private function deduplicateScopeRows(): void
    {
        $rows = DB::table('liga_clube_conquistas')
            ->select(['id', 'user_id', 'confederacao_id', 'conquista_id', 'claimed_at', 'created_at'])
            ->whereNotNull('user_id')
            ->whereNotNull('confederacao_id')
            ->orderBy('id')
            ->get();

        $grouped = $rows->groupBy(fn ($row) => implode('|', [
            (string) $row->user_id,
            (string) $row->confederacao_id,
            (string) $row->conquista_id,
        ]));

        foreach ($grouped as $records) {
            if ($records->count() <= 1) {
                continue;
            }

            $ordered = $records->sort(function ($a, $b): int {
                $aClaimed = $a->claimed_at;
                $bClaimed = $b->claimed_at;

                if ($aClaimed !== null && $bClaimed !== null && $aClaimed !== $bClaimed) {
                    return strcmp((string) $aClaimed, (string) $bClaimed);
                }

                if ($aClaimed !== null && $bClaimed === null) {
                    return -1;
                }

                if ($aClaimed === null && $bClaimed !== null) {
                    return 1;
                }

                $aCreated = $a->created_at;
                $bCreated = $b->created_at;

                if ($aCreated !== null && $bCreated !== null && $aCreated !== $bCreated) {
                    return strcmp((string) $aCreated, (string) $bCreated);
                }

                return (int) $a->id <=> (int) $b->id;
            })->values();

            $keep = $ordered->first();
            if (! $keep) {
                continue;
            }

            $oldestClaimedAt = $ordered
                ->pluck('claimed_at')
                ->filter(fn ($claimedAt) => $claimedAt !== null)
                ->sort()
                ->first();

            if ($oldestClaimedAt !== null && $keep->claimed_at !== $oldestClaimedAt) {
                DB::table('liga_clube_conquistas')
                    ->where('id', $keep->id)
                    ->update(['claimed_at' => $oldestClaimedAt]);
            }

            $idsToDelete = $ordered
                ->skip(1)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($idsToDelete !== []) {
                DB::table('liga_clube_conquistas')
                    ->whereIn('id', $idsToDelete)
                    ->delete();
            }
        }
    }

    private function dropRowsWithoutScope(): void
    {
        DB::table('liga_clube_conquistas')
            ->whereNull('user_id')
            ->orWhereNull('confederacao_id')
            ->delete();
    }
};
