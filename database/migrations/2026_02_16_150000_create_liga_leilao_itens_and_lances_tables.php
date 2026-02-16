<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liga_leilao_itens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('confederacao_id')->constrained('confederacoes')->cascadeOnDelete();
            $table->foreignId('elencopadrao_id')->constrained('elencopadrao')->cascadeOnDelete();
            $table->foreignId('clube_lider_id')->nullable()->constrained('liga_clubes')->nullOnDelete();
            $table->bigInteger('valor_inicial')->default(0);
            $table->bigInteger('valor_atual')->nullable();
            $table->timestamp('expira_em')->nullable();
            $table->string('status', 20)->default('aberto');
            $table->string('motivo_cancelamento')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(['confederacao_id', 'elencopadrao_id'], 'liga_leilao_itens_confed_player_unique');
            $table->index(['status', 'expira_em'], 'liga_leilao_itens_status_expira_idx');
            $table->index(['confederacao_id', 'status'], 'liga_leilao_itens_confed_status_idx');
        });

        Schema::create('liga_leilao_lances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('liga_leilao_item_id')->constrained('liga_leilao_itens')->cascadeOnDelete();
            $table->foreignId('confederacao_id')->constrained('confederacoes')->cascadeOnDelete();
            $table->foreignId('elencopadrao_id')->constrained('elencopadrao')->cascadeOnDelete();
            $table->foreignId('clube_id')->constrained('liga_clubes')->cascadeOnDelete();
            $table->bigInteger('valor');
            $table->timestamp('expira_em');
            $table->timestamps();

            $table->index(['liga_leilao_item_id', 'created_at'], 'liga_leilao_lances_item_created_idx');
            $table->index(['confederacao_id', 'elencopadrao_id'], 'liga_leilao_lances_confed_player_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liga_leilao_lances');
        Schema::dropIfExists('liga_leilao_itens');
    }
};

