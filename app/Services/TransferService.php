<?php

namespace App\Services;

use App\Models\Elencopadrao;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeElenco;
use App\Models\LigaProposta;
use App\Models\LigaTransferencia;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class TransferService
{
    public function __construct(private readonly LeagueFinanceService $finance)
    {
    }

    public function buyPlayer(int $ligaId, int $compradorClubeId, int $elencopadraoId, ?int $priceOptional = null): LigaClubeElenco
    {
        if ($priceOptional !== null) {
            throw new \DomainException('Preço manual não é permitido no momento.');
        }

        return DB::transaction(function () use ($ligaId, $compradorClubeId, $elencopadraoId): LigaClubeElenco {
            $liga = Liga::query()->lockForUpdate()->findOrFail($ligaId);
            $comprador = LigaClube::query()->lockForUpdate()->findOrFail($compradorClubeId);

            if ((int) $comprador->liga_id !== (int) $liga->id) {
                throw new \DomainException('Clube não pertence a esta liga.');
            }

            $player = Elencopadrao::query()->findOrFail($elencopadraoId);

            if ((int) $player->jogo_id !== (int) $liga->jogo_id) {
                throw new \DomainException('Este jogador não pertence ao jogo desta liga.');
            }

            [$scopeColumn, $scopeValue] = $this->resolveEntryScope($liga);
            $scopeLabel = $liga->confederacao_id ? 'confederacao' : 'liga';

            $jaNaLiga = LigaClubeElenco::query()
                ->where($scopeColumn, $scopeValue)
                ->where('elencopadrao_id', $elencopadraoId)
                ->exists();

            if ($jaNaLiga) {
                throw new \DomainException("Esse jogador ja faz parte de outro clube desta {$scopeLabel}.");
            }

            $this->assertRosterLimit($liga, $compradorClubeId);

            $price = (int) ($player->value_eur ?? 0);

            $this->assertClubCanSpend($liga, $compradorClubeId, $price);

            $this->finance->debit($ligaId, $compradorClubeId, $price, 'Compra de jogador livre');

            try {
                $entry = LigaClubeElenco::create([
                    'confederacao_id' => $liga->confederacao_id,
                    'liga_id' => $ligaId,
                    'liga_clube_id' => $compradorClubeId,
                    'elencopadrao_id' => $elencopadraoId,
                    'value_eur' => $player->value_eur,
                    'wage_eur' => $player->wage_eur,
                    'ativo' => true,
                ]);
            } catch (QueryException $exception) {
                if ($this->isUniqueViolation($exception)) {
                    throw new \DomainException("Esse jogador ja faz parte de outro clube desta {$scopeLabel}.");
                }

                throw $exception;
            }

            LigaTransferencia::create([
                'liga_id' => $ligaId,
                'confederacao_id' => $liga->confederacao_id,
                'liga_origem_id' => null,
                'liga_destino_id' => $ligaId,
                'elencopadrao_id' => $elencopadraoId,
                'clube_origem_id' => null,
                'clube_destino_id' => $compradorClubeId,
                'tipo' => 'jogador_livre',
                'valor' => $price,
                'observacao' => 'Jogador livre adquirido no mercado.',
            ]);

            return $entry;
        }, 3);
    }

    public function releaseToMarket(LigaClubeElenco $entry): array
    {
        return DB::transaction(function () use ($entry): array {
            $model = LigaClubeElenco::query()
                ->lockForUpdate()
                ->find($entry->id);

            if (! $model) {
                throw new \DomainException('Jogador não está disponível para transferência nesta liga.');
            }

            if (! $model->ativo) {
                throw new \DomainException('Jogador inativo não pode ser devolvido ao mercado.');
            }

            $model->loadMissing('elencopadrao');
            $baseValue = (int) ($model->elencopadrao?->value_eur ?? 0);

            // Default taxa de venda: 20%
            $taxPercent = 20;
            $taxValue = (int) round($baseValue * ($taxPercent / 100));
            $credit = max(0, $baseValue - $taxValue);

            $ligaId = (int) $model->liga_id;
            $clubeOrigemId = (int) $model->liga_clube_id;
            $elencopadraoId = (int) $model->elencopadrao_id;

            if ($credit > 0) {
                $this->finance->credit($ligaId, $clubeOrigemId, $credit, 'Venda ao mercado');
            }

            $model->delete();

            return [
                'base_value' => $baseValue,
                'tax_percent' => $taxPercent,
                'tax_value' => $taxValue,
                'credit' => $credit,
                'elencopadrao_id' => $elencopadraoId,
            ];
        }, 3);
    }

    public function sellPlayer(int $ligaId, int $vendedorClubeId, int $compradorClubeId, int $elencopadraoId, int $price): LigaClubeElenco
    {
        return DB::transaction(function () use ($ligaId, $vendedorClubeId, $compradorClubeId, $elencopadraoId, $price): LigaClubeElenco {
            $liga = Liga::query()->lockForUpdate()->findOrFail($ligaId);
            $vendedor = LigaClube::query()->lockForUpdate()->findOrFail($vendedorClubeId);
            $comprador = LigaClube::query()->lockForUpdate()->findOrFail($compradorClubeId);

            if ((int) $comprador->liga_id !== (int) $liga->id) {
                throw new \DomainException('Clube nao pertence a esta liga.');
            }

            $ligaVendedor = (int) $vendedor->liga_id === (int) $liga->id
                ? $liga
                : Liga::query()->lockForUpdate()->findOrFail($vendedor->liga_id);

            $this->assertSameScope($liga, $ligaVendedor);

            [$scopeColumn, $scopeValue] = $this->resolveEntryScope($liga);

            $entry = LigaClubeElenco::query()
                ->where($scopeColumn, $scopeValue)
                ->where('elencopadrao_id', $elencopadraoId)
                ->lockForUpdate()
                ->first();

            if (! $entry || ! $entry->ativo) {
                $scopeLabel = $liga->confederacao_id ? 'confederacao' : 'liga';
                throw new \DomainException("Jogador nao esta disponivel para transferencia nesta {$scopeLabel}.");
            }

            if ((int) $entry->liga_clube_id !== (int) $vendedorClubeId) {
                throw new \DomainException('O clube vendedor nao possui este jogador.');
            }

            $minPrice = $this->minSellPrice($ligaVendedor, (int) $entry->value_eur);
            if ($price < $minPrice) {
                throw new \DomainException('Preco abaixo do minimo permitido para venda.');
            }

            $this->assertRosterLimit($liga, $compradorClubeId);
            $this->assertClubCanSpend($liga, $compradorClubeId, $price);

            $this->finance->debit($liga->id, $compradorClubeId, $price, 'Compra de jogador');
            $this->finance->credit($ligaVendedor->id, $vendedorClubeId, $price, 'Venda de jogador');

            $entry->liga_clube_id = $compradorClubeId;
            $entry->liga_id = $liga->id;
            $entry->confederacao_id = $liga->confederacao_id;
            $entry->save();

            LigaTransferencia::create([
                'liga_id' => $liga->id,
                'confederacao_id' => $liga->confederacao_id,
                'liga_origem_id' => $ligaVendedor->id,
                'liga_destino_id' => $liga->id,
                'elencopadrao_id' => $elencopadraoId,
                'clube_origem_id' => $vendedorClubeId,
                'clube_destino_id' => $compradorClubeId,
                'tipo' => 'venda',
                'valor' => $price,
                'observacao' => 'Venda de jogador entre clubes.',
            ]);

            return $entry;
        }, 3);
    }

    public function payReleaseClause(int $ligaId, int $compradorClubeId, int $elencopadraoId): LigaClubeElenco
    {
        return DB::transaction(function () use ($ligaId, $compradorClubeId, $elencopadraoId): LigaClubeElenco {
            $liga = Liga::query()->lockForUpdate()->findOrFail($ligaId);
            $comprador = LigaClube::query()->lockForUpdate()->findOrFail($compradorClubeId);

            if ((int) $comprador->liga_id !== (int) $liga->id) {
                throw new \DomainException('Clube nao pertence a esta liga.');
            }

            [$scopeColumn, $scopeValue] = $this->resolveEntryScope($liga);

            $entry = LigaClubeElenco::query()
                ->where($scopeColumn, $scopeValue)
                ->where('elencopadrao_id', $elencopadraoId)
                ->lockForUpdate()
                ->first();

            if (! $entry || ! $entry->ativo) {
                $scopeLabel = $liga->confederacao_id ? 'confederacao' : 'liga';
                throw new \DomainException("Jogador nao esta em nenhum clube desta {$scopeLabel}.");
            }

            $clubeOrigemId = (int) $entry->liga_clube_id;

            $ligaOrigem = (int) $entry->liga_id === (int) $liga->id
                ? $liga
                : Liga::query()->lockForUpdate()->findOrFail($entry->liga_id);

            $this->assertSameScope($liga, $ligaOrigem);

            $multa = (int) round(((int) $entry->value_eur) * (float) $ligaOrigem->multa_multiplicador);

            $this->assertRosterLimit($liga, $compradorClubeId);
            $this->assertClubCanSpend($liga, $compradorClubeId, $multa);

            $this->finance->debit($liga->id, $compradorClubeId, $multa, 'Pagamento de multa');
            $this->finance->credit($ligaOrigem->id, $clubeOrigemId, $multa, 'Recebimento de multa');

            $entry->liga_clube_id = $compradorClubeId;
            $entry->liga_id = $liga->id;
            $entry->confederacao_id = $liga->confederacao_id;
            $entry->save();

            LigaTransferencia::create([
                'liga_id' => $liga->id,
                'confederacao_id' => $liga->confederacao_id,
                'liga_origem_id' => $ligaOrigem->id,
                'liga_destino_id' => $liga->id,
                'elencopadrao_id' => $elencopadraoId,
                'clube_origem_id' => $clubeOrigemId,
                'clube_destino_id' => $compradorClubeId,
                'tipo' => 'multa',
                'valor' => $multa,
                'observacao' => 'Multa paga via cláusula de rescisão.',
            ]);

            return $entry;
        }, 3);
    }

    public function swapPlayers(int $ligaId, int $clubeAId, int $jogadorAId, int $clubeBId, int $jogadorBId, int $ajusteValor = 0): array
    {
        return DB::transaction(function () use ($ligaId, $clubeAId, $jogadorAId, $clubeBId, $jogadorBId, $ajusteValor): array {
            $liga = Liga::query()->lockForUpdate()->findOrFail($ligaId);
            $clubeA = LigaClube::query()->lockForUpdate()->findOrFail($clubeAId);
            $clubeB = LigaClube::query()->lockForUpdate()->findOrFail($clubeBId);

            if ((int) $clubeA->liga_id !== (int) $liga->id) {
                throw new \DomainException('Clube nao pertence a esta liga.');
            }

            $ligaB = (int) $clubeB->liga_id === (int) $liga->id
                ? $liga
                : Liga::query()->lockForUpdate()->findOrFail($clubeB->liga_id);

            $this->assertSameScope($liga, $ligaB);

            [$scopeColumn, $scopeValue] = $this->resolveEntryScope($liga);

            $entryA = LigaClubeElenco::query()
                ->where($scopeColumn, $scopeValue)
                ->where('elencopadrao_id', $jogadorAId)
                ->lockForUpdate()
                ->first();

            $entryB = LigaClubeElenco::query()
                ->where($scopeColumn, $scopeValue)
                ->where('elencopadrao_id', $jogadorBId)
                ->lockForUpdate()
                ->first();

            if (! $entryA || ! $entryB || ! $entryA->ativo || ! $entryB->ativo) {
                throw new \DomainException('Ambos os jogadores precisam estar ativos para a troca.');
            }

            if ((int) $entryA->liga_clube_id !== (int) $clubeAId || (int) $entryB->liga_clube_id !== (int) $clubeBId) {
                throw new \DomainException('Os jogadores informados nao pertencem aos clubes selecionados.');
            }

            if ($ajusteValor !== 0) {
                if ($ajusteValor > 0) {
                    $this->assertClubCanSpend($liga, $clubeAId, $ajusteValor);
                    $this->finance->debit($liga->id, $clubeAId, $ajusteValor, 'Ajuste de troca');
                    $this->finance->credit($ligaB->id, $clubeBId, $ajusteValor, 'Ajuste de troca');
                } else {
                    $valor = abs($ajusteValor);
                    $this->assertClubCanSpend($ligaB, $clubeBId, $valor);
                    $this->finance->debit($ligaB->id, $clubeBId, $valor, 'Ajuste de troca');
                    $this->finance->credit($liga->id, $clubeAId, $valor, 'Ajuste de troca');
                }
            }

            $entryA->liga_clube_id = $clubeBId;
            $entryA->liga_id = $ligaB->id;
            $entryA->confederacao_id = $liga->confederacao_id;
            $entryA->save();

            $entryB->liga_clube_id = $clubeAId;
            $entryB->liga_id = $liga->id;
            $entryB->confederacao_id = $liga->confederacao_id;
            $entryB->save();

            $observacao = sprintf(
                'Troca: clubeA (%d) ↔ clubeB (%d) | Ajuste: %d (positivo = clubeA paga clubeB).',
                $clubeAId,
                $clubeBId,
                $ajusteValor,
            );

            LigaTransferencia::create([
                'liga_id' => $ligaB->id,
                'confederacao_id' => $liga->confederacao_id,
                'liga_origem_id' => $liga->id,
                'liga_destino_id' => $ligaB->id,
                'elencopadrao_id' => $jogadorAId,
                'clube_origem_id' => $clubeAId,
                'clube_destino_id' => $clubeBId,
                'tipo' => 'troca',
                'valor' => abs($ajusteValor),
                'observacao' => $observacao,
            ]);

            LigaTransferencia::create([
                'liga_id' => $liga->id,
                'confederacao_id' => $liga->confederacao_id,
                'liga_origem_id' => $ligaB->id,
                'liga_destino_id' => $liga->id,
                'elencopadrao_id' => $jogadorBId,
                'clube_origem_id' => $clubeBId,
                'clube_destino_id' => $clubeAId,
                'tipo' => 'troca',
                'valor' => abs($ajusteValor),
                'observacao' => $observacao,
            ]);

            return [$entryA, $entryB];
        }, 3);
    }

    public function acceptProposal(LigaProposta $proposta): void
    {
        DB::transaction(function () use ($proposta): void {
            $proposal = LigaProposta::query()->lockForUpdate()->findOrFail($proposta->id);

            if ($proposal->status !== 'aberta') {
                throw new \DomainException('Proposta nao esta mais disponivel.');
            }

            $ligaOrigem = Liga::query()->lockForUpdate()->findOrFail($proposal->liga_origem_id);
            $ligaDestino = Liga::query()->lockForUpdate()->findOrFail($proposal->liga_destino_id);
            $clubeOrigem = LigaClube::query()->lockForUpdate()->findOrFail($proposal->clube_origem_id);
            $clubeDestino = LigaClube::query()->lockForUpdate()->findOrFail($proposal->clube_destino_id);

            $this->assertSameScope($ligaDestino, $ligaOrigem);

            [$scopeColumn, $scopeValue] = $this->resolveEntryScope($ligaDestino);

            $targetEntry = LigaClubeElenco::query()
                ->where($scopeColumn, $scopeValue)
                ->where('elencopadrao_id', $proposal->elencopadrao_id)
                ->lockForUpdate()
                ->first();

            if (! $targetEntry || ! $targetEntry->ativo) {
                throw new \DomainException('Jogador nao esta mais disponivel para transferencia.');
            }

            if ((int) $targetEntry->liga_clube_id !== (int) $clubeOrigem->id) {
                throw new \DomainException('Jogador nao pertence mais ao clube de origem.');
            }

            $offerIds = array_values(array_unique(array_map('intval', $proposal->oferta_elencopadrao_ids ?? [])));
            $offerEntries = collect();

            if ($offerIds) {
                $offerEntries = LigaClubeElenco::query()
                    ->where($scopeColumn, $scopeValue)
                    ->where('liga_clube_id', $clubeDestino->id)
                    ->whereIn('elencopadrao_id', $offerIds)
                    ->where('ativo', true)
                    ->lockForUpdate()
                    ->get();

                if ($offerEntries->count() !== count($offerIds)) {
                    throw new \DomainException('Jogadores oferecidos nao pertencem mais ao clube de destino.');
                }
            }

            $sellerCount = $this->countActivePlayers($ligaOrigem, $clubeOrigem->id);
            $buyerCount = $this->countActivePlayers($ligaDestino, $clubeDestino->id);

            $sellerFinal = $sellerCount - 1 + $offerEntries->count();
            $buyerFinal = $buyerCount + 1 - $offerEntries->count();

            $this->assertRosterLimitForCount($ligaOrigem, $sellerFinal);
            $this->assertRosterLimitForCount($ligaDestino, $buyerFinal);

            $valor = (int) $proposal->valor;
            if ($valor > 0) {
                $this->assertClubCanSpend($ligaDestino, $clubeDestino->id, $valor);
                $this->finance->debit($ligaDestino->id, $clubeDestino->id, $valor, 'Proposta aceita');
                $this->finance->credit($ligaOrigem->id, $clubeOrigem->id, $valor, 'Proposta aceita');
            }

            $targetEntry->liga_clube_id = $clubeDestino->id;
            $targetEntry->liga_id = $ligaDestino->id;
            $targetEntry->confederacao_id = $ligaDestino->confederacao_id;
            $targetEntry->save();

            foreach ($offerEntries as $entry) {
                $entry->liga_clube_id = $clubeOrigem->id;
                $entry->liga_id = $ligaOrigem->id;
                $entry->confederacao_id = $ligaOrigem->confederacao_id;
                $entry->save();
            }

            $proposal->status = 'aceita';
            $proposal->save();

            $cancelQuery = LigaProposta::query()
                ->where('id', '<>', $proposal->id)
                ->where('status', 'aberta')
                ->where('elencopadrao_id', $proposal->elencopadrao_id);

            if ($proposal->confederacao_id) {
                $cancelQuery->where('confederacao_id', $proposal->confederacao_id);
            } else {
                $cancelQuery->where('liga_origem_id', $proposal->liga_origem_id);
            }

            $cancelQuery->update(['status' => 'cancelada']);

            $observacao = 'Proposta aceita #'.$proposal->id;

            LigaTransferencia::create([
                'liga_id' => $ligaDestino->id,
                'confederacao_id' => $ligaDestino->confederacao_id,
                'liga_origem_id' => $ligaOrigem->id,
                'liga_destino_id' => $ligaDestino->id,
                'elencopadrao_id' => $proposal->elencopadrao_id,
                'clube_origem_id' => $clubeOrigem->id,
                'clube_destino_id' => $clubeDestino->id,
                'tipo' => 'troca',
                'valor' => $valor,
                'observacao' => $observacao,
            ]);

            foreach ($offerEntries as $entry) {
                LigaTransferencia::create([
                    'liga_id' => $ligaOrigem->id,
                    'confederacao_id' => $ligaOrigem->confederacao_id,
                    'liga_origem_id' => $ligaDestino->id,
                    'liga_destino_id' => $ligaOrigem->id,
                    'elencopadrao_id' => $entry->elencopadrao_id,
                    'clube_origem_id' => $clubeDestino->id,
                    'clube_destino_id' => $clubeOrigem->id,
                    'tipo' => 'troca',
                    'valor' => 0,
                    'observacao' => $observacao,
                ]);
            }
        }, 3);
    }

    private function resolveEntryScope(Liga $liga): array
    {
        if ($liga->confederacao_id) {
            return ['confederacao_id', (int) $liga->confederacao_id];
        }

        return ['liga_id', (int) $liga->id];
    }

    private function assertSameScope(Liga $liga, Liga $otherLiga): void
    {
        if ($liga->confederacao_id) {
            if ((int) $otherLiga->confederacao_id !== (int) $liga->confederacao_id) {
                throw new \DomainException('Um dos clubes nao pertence a esta confederacao.');
            }

            return;
        }

        if ((int) $otherLiga->id !== (int) $liga->id) {
            throw new \DomainException('Um dos clubes nao pertence a esta liga.');
        }
    }

    private function countActivePlayers(Liga $liga, int $clubeId): int
    {
        return LigaClubeElenco::query()
            ->where('liga_id', $liga->id)
            ->where('liga_clube_id', $clubeId)
            ->where('ativo', true)
            ->count();
    }

    private function assertRosterLimitForCount(Liga $liga, int $count): void
    {
        $max = (int) ($liga->max_jogadores_por_clube ?? 18);

        if ($count > $max) {
            throw new \DomainException("Elenco cheio ({$count}/{$max}).");
        }
    }

    private function assertRosterLimit(Liga $liga, int $clubeId): void
    {
        $max = (int) ($liga->max_jogadores_por_clube ?? 18);

        $count = LigaClubeElenco::query()
            ->where('liga_id', $liga->id)
            ->where('liga_clube_id', $clubeId)
            ->where('ativo', true)
            ->count();

        if ($count >= $max) {
            throw new \DomainException("Elenco cheio ({$count}/{$max}).");
        }
    }

    private function assertClubCanSpend(Liga $liga, int $clubeId, int $amount): void
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount deve ser >= 0.');
        }

        $saldo = $this->finance->getSaldo((int) $liga->id, $clubeId);

        if ($liga->bloquear_compra_saldo_negativo && $saldo < 0) {
            throw new \DomainException('Seu clube está com saldo negativo e não pode realizar esta operação.');
        }

        if ($saldo < $amount) {
            throw new \DomainException("Saldo insuficiente. Saldo atual: {$saldo}. Necessário: {$amount}.");
        }
    }

    private function minSellPrice(Liga $liga, int $valueEur): int
    {
        $percent = (int) ($liga->venda_min_percent ?? 100);
        $numerator = $valueEur * $percent;

        return intdiv($numerator + 99, 100);
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'UNIQUE constraint failed')
            || str_contains($message, 'Duplicate entry')
            || str_contains($message, 'unique constraint')
            || str_contains($message, 'violates unique constraint');
    }
}
