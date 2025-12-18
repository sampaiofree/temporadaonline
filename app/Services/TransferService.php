<?php

namespace App\Services;

use App\Models\Elencopadrao;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\LigaClubeElenco;
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

            $jaNaLiga = LigaClubeElenco::query()
                ->where('liga_id', $ligaId)
                ->where('elencopadrao_id', $elencopadraoId)
                ->exists();

            if ($jaNaLiga) {
                throw new \DomainException('Esse jogador já faz parte de outro clube desta liga.');
            }

            $this->assertRosterLimit($liga, $compradorClubeId);

            $price = (int) ($player->value_eur ?? 0);

            $this->assertClubCanSpend($liga, $compradorClubeId, $price);

            $this->finance->debit($ligaId, $compradorClubeId, $price, 'Compra de jogador livre');

            try {
                $entry = LigaClubeElenco::create([
                    'liga_id' => $ligaId,
                    'liga_clube_id' => $compradorClubeId,
                    'elencopadrao_id' => $elencopadraoId,
                    'value_eur' => $player->value_eur,
                    'wage_eur' => $player->wage_eur,
                    'ativo' => true,
                ]);
            } catch (QueryException $exception) {
                if ($this->isUniqueViolation($exception)) {
                    throw new \DomainException('Esse jogador já faz parte de outro clube desta liga.');
                }

                throw $exception;
            }

            LigaTransferencia::create([
                'liga_id' => $ligaId,
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

    public function sellPlayer(int $ligaId, int $vendedorClubeId, int $compradorClubeId, int $elencopadraoId, int $price): LigaClubeElenco
    {
        return DB::transaction(function () use ($ligaId, $vendedorClubeId, $compradorClubeId, $elencopadraoId, $price): LigaClubeElenco {
            $liga = Liga::query()->lockForUpdate()->findOrFail($ligaId);
            $vendedor = LigaClube::query()->lockForUpdate()->findOrFail($vendedorClubeId);
            $comprador = LigaClube::query()->lockForUpdate()->findOrFail($compradorClubeId);

            if ((int) $vendedor->liga_id !== (int) $liga->id || (int) $comprador->liga_id !== (int) $liga->id) {
                throw new \DomainException('Um dos clubes não pertence a esta liga.');
            }

            $entry = LigaClubeElenco::query()
                ->where('liga_id', $ligaId)
                ->where('elencopadrao_id', $elencopadraoId)
                ->lockForUpdate()
                ->first();

            if (! $entry || ! $entry->ativo) {
                throw new \DomainException('Jogador não está disponível para transferência nesta liga.');
            }

            if ((int) $entry->liga_clube_id !== (int) $vendedorClubeId) {
                throw new \DomainException('O clube vendedor não possui este jogador.');
            }

            $minPrice = $this->minSellPrice($liga, (int) $entry->value_eur);
            if ($price < $minPrice) {
                throw new \DomainException('Preço abaixo do mínimo permitido para venda.');
            }

            $this->assertRosterLimit($liga, $compradorClubeId);
            $this->assertClubCanSpend($liga, $compradorClubeId, $price);

            $this->finance->debit($ligaId, $compradorClubeId, $price, 'Compra de jogador');
            $this->finance->credit($ligaId, $vendedorClubeId, $price, 'Venda de jogador');

            $entry->liga_clube_id = $compradorClubeId;
            $entry->save();

            LigaTransferencia::create([
                'liga_id' => $ligaId,
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
                throw new \DomainException('Clube não pertence a esta liga.');
            }

            $entry = LigaClubeElenco::query()
                ->where('liga_id', $ligaId)
                ->where('elencopadrao_id', $elencopadraoId)
                ->lockForUpdate()
                ->first();

            if (! $entry || ! $entry->ativo) {
                throw new \DomainException('Jogador não está em nenhum clube desta liga.');
            }

            $clubeOrigemId = (int) $entry->liga_clube_id;

            $multa = (int) round(((int) $entry->value_eur) * (float) $liga->multa_multiplicador);

            $this->assertRosterLimit($liga, $compradorClubeId);
            $this->assertClubCanSpend($liga, $compradorClubeId, $multa);

            $this->finance->debit($ligaId, $compradorClubeId, $multa, 'Pagamento de multa');
            $this->finance->credit($ligaId, $clubeOrigemId, $multa, 'Recebimento de multa');

            $entry->liga_clube_id = $compradorClubeId;
            $entry->save();

            LigaTransferencia::create([
                'liga_id' => $ligaId,
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

            if ((int) $clubeA->liga_id !== (int) $liga->id || (int) $clubeB->liga_id !== (int) $liga->id) {
                throw new \DomainException('Um dos clubes não pertence a esta liga.');
            }

            $entryA = LigaClubeElenco::query()
                ->where('liga_id', $ligaId)
                ->where('elencopadrao_id', $jogadorAId)
                ->lockForUpdate()
                ->first();

            $entryB = LigaClubeElenco::query()
                ->where('liga_id', $ligaId)
                ->where('elencopadrao_id', $jogadorBId)
                ->lockForUpdate()
                ->first();

            if (! $entryA || ! $entryB || ! $entryA->ativo || ! $entryB->ativo) {
                throw new \DomainException('Ambos os jogadores precisam estar ativos para a troca.');
            }

            if ((int) $entryA->liga_clube_id !== (int) $clubeAId || (int) $entryB->liga_clube_id !== (int) $clubeBId) {
                throw new \DomainException('Os jogadores informados não pertencem aos clubes selecionados.');
            }

            if ($ajusteValor !== 0) {
                if ($ajusteValor > 0) {
                    $this->assertClubCanSpend($liga, $clubeAId, $ajusteValor);
                    $this->finance->debit($ligaId, $clubeAId, $ajusteValor, 'Ajuste de troca');
                    $this->finance->credit($ligaId, $clubeBId, $ajusteValor, 'Ajuste de troca');
                } else {
                    $valor = abs($ajusteValor);
                    $this->assertClubCanSpend($liga, $clubeBId, $valor);
                    $this->finance->debit($ligaId, $clubeBId, $valor, 'Ajuste de troca');
                    $this->finance->credit($ligaId, $clubeAId, $valor, 'Ajuste de troca');
                }
            }

            $entryA->liga_clube_id = $clubeBId;
            $entryA->save();

            $entryB->liga_clube_id = $clubeAId;
            $entryB->save();

            $observacao = sprintf(
                'Troca: clubeA (%d) ↔ clubeB (%d) | Ajuste: %d (positivo = clubeA paga clubeB).',
                $clubeAId,
                $clubeBId,
                $ajusteValor,
            );

            LigaTransferencia::create([
                'liga_id' => $ligaId,
                'elencopadrao_id' => $jogadorAId,
                'clube_origem_id' => $clubeAId,
                'clube_destino_id' => $clubeBId,
                'tipo' => 'troca',
                'valor' => abs($ajusteValor),
                'observacao' => $observacao,
            ]);

            LigaTransferencia::create([
                'liga_id' => $ligaId,
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
