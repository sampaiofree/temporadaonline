<?php

use App\Models\LigaClubeFinanceiroMovimento;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liga_clube_financeiro_movimentos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('liga_id')->constrained('ligas')->cascadeOnDelete();
            $table->foreignId('clube_id')->constrained('liga_clubes')->cascadeOnDelete();
            $table->string('operacao', 40);
            $table->string('descricao', 255)->nullable();
            $table->bigInteger('valor')->default(0);
            $table->bigInteger('saldo_antes');
            $table->bigInteger('saldo_depois');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['liga_id', 'clube_id', 'created_at', 'id'], 'liga_clube_fin_mov_ledger_idx');
            $table->index(['liga_id', 'clube_id', 'operacao'], 'liga_clube_fin_mov_op_idx');
        });

        $now = now();
        $rows = DB::table('liga_clube_financeiro')
            ->select(['liga_id', 'clube_id', 'saldo'])
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $chunks = $rows->chunk(1000);
        foreach ($chunks as $chunk) {
            $payload = [];
            foreach ($chunk as $wallet) {
                $saldo = (int) ($wallet->saldo ?? 0);
                $payload[] = [
                    'liga_id' => (int) $wallet->liga_id,
                    'clube_id' => (int) $wallet->clube_id,
                    'operacao' => LigaClubeFinanceiroMovimento::OPERATION_SNAPSHOT_OPENING,
                    'descricao' => 'Saldo de abertura ao ativar o extrato financeiro',
                    'valor' => 0,
                    'saldo_antes' => $saldo,
                    'saldo_depois' => $saldo,
                    'metadata' => json_encode([
                        'source' => 'bootstrap_existing_wallet',
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($payload !== []) {
                DB::table('liga_clube_financeiro_movimentos')->insert($payload);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('liga_clube_financeiro_movimentos');
    }
};

