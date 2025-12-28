<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('liga_clubes')) {
            return;
        }

        Schema::table('liga_clubes', function (Blueprint $table): void {
            if (! Schema::hasColumn('liga_clubes', 'escudo_clube_id')) {
                $table->foreignId('escudo_clube_id')->nullable()->after('escudo_url');
            }
        });

        $escudos = DB::table('escudos_clubes')
            ->select(['id', 'clube_imagem'])
            ->whereNotNull('clube_imagem')
            ->get()
            ->keyBy('clube_imagem');

        if ($escudos->isNotEmpty()) {
            $clubes = DB::table('liga_clubes')
                ->select(['id', 'escudo_url'])
                ->whereNull('escudo_clube_id')
                ->whereNotNull('escudo_url')
                ->get();

            foreach ($clubes as $clube) {
                $normalized = $this->normalizeEscudoPath((string) $clube->escudo_url);
                if ($normalized === '') {
                    continue;
                }

                $escudoId = $escudos->get($normalized)?->id;
                if (! $escudoId) {
                    continue;
                }

                DB::table('liga_clubes')
                    ->where('id', $clube->id)
                    ->update(['escudo_clube_id' => $escudoId]);
            }
        }

        $duplicates = DB::table('liga_clubes')
            ->select('liga_id', 'escudo_clube_id', DB::raw('MIN(id) as keep_id'))
            ->whereNotNull('escudo_clube_id')
            ->groupBy('liga_id', 'escudo_clube_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('liga_clubes')
                ->where('liga_id', $duplicate->liga_id)
                ->where('escudo_clube_id', $duplicate->escudo_clube_id)
                ->where('id', '<>', $duplicate->keep_id)
                ->update([
                    'escudo_clube_id' => null,
                    'escudo_url' => null,
                ]);
        }

        Schema::table('liga_clubes', function (Blueprint $table): void {
            if (Schema::hasColumn('liga_clubes', 'escudo_clube_id')) {
                $table->foreign('escudo_clube_id')
                    ->references('id')
                    ->on('escudos_clubes')
                    ->nullOnDelete();
                $table->unique(['liga_id', 'escudo_clube_id'], 'liga_clubes_liga_escudo_unique');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('liga_clubes') || ! Schema::hasColumn('liga_clubes', 'escudo_clube_id')) {
            return;
        }

        Schema::table('liga_clubes', function (Blueprint $table): void {
            $table->dropUnique('liga_clubes_liga_escudo_unique');
            $table->dropForeign(['escudo_clube_id']);
            $table->dropColumn('escudo_clube_id');
        });
    }

    private function normalizeEscudoPath(string $value): string
    {
        $path = trim($value);
        if ($path === '') {
            return '';
        }

        $path = Str::before($path, '?');

        if (Str::contains($path, '/storage/')) {
            $path = Str::after($path, '/storage/');
        }

        return ltrim($path, '/');
    }
};
