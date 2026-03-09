<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Liga;
use App\Models\LigaClube;
use App\Models\Partida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PartidaController extends Controller
{
    private const ESTADO_OPTIONS = [
        'confirmacao_necessaria' => 'Confirmacao necessaria',
        'agendada' => 'Agendada',
        'confirmada' => 'Confirmada',
        'placar_registrado' => 'Placar registrado',
        'placar_confirmado' => 'Placar confirmado',
        'em_reclamacao' => 'Em reclamacao',
        'finalizada' => 'Finalizada',
        'wo' => 'W.O.',
        'cancelada' => 'Cancelada',
    ];

    private const WO_MOTIVO_OPTIONS = [
        'nao_compareceu' => 'Nao compareceu',
        'escala_irregular' => 'Escalacao irregular',
        'conexao' => 'Conexao',
        'outro' => 'Outro',
    ];

    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'estado' => trim((string) $request->query('estado', '')),
            'liga_id' => trim((string) $request->query('liga_id', '')),
            'clube_id' => trim((string) $request->query('clube_id', '')),
            'data_inicio' => trim((string) $request->query('data_inicio', '')),
            'data_fim' => trim((string) $request->query('data_fim', '')),
        ];

        if (! array_key_exists($filters['estado'], self::ESTADO_OPTIONS)) {
            $filters['estado'] = '';
        }

        $partidasQuery = Partida::query()
            ->with([
                'liga:id,nome',
                'mandante:id,nome,user_id',
                'mandante.user:id,name',
                'visitante:id,nome,user_id',
                'visitante.user:id,name',
                'woParaUser:id,name',
                'placarRegistradoPorUser:id,name',
            ])
            ->withCount('reclamacoes');

        if ($filters['q'] !== '') {
            if (ctype_digit($filters['q'])) {
                $partidasQuery->where('id', (int) $filters['q']);
            } else {
                $partidasQuery->whereRaw('1 = 0');
            }
        }

        if ($filters['estado'] !== '') {
            $partidasQuery->where('estado', $filters['estado']);
        }

        if ($filters['liga_id'] !== '' && ctype_digit($filters['liga_id'])) {
            $partidasQuery->where('liga_id', (int) $filters['liga_id']);
        }

        if ($filters['clube_id'] !== '' && ctype_digit($filters['clube_id'])) {
            $clubeId = (int) $filters['clube_id'];
            $partidasQuery->where(function ($query) use ($clubeId): void {
                $query->where('mandante_id', $clubeId)
                    ->orWhere('visitante_id', $clubeId);
            });
        }

        if ($filters['data_inicio'] !== '') {
            $partidasQuery->whereDate('scheduled_at', '>=', $filters['data_inicio']);
        }

        if ($filters['data_fim'] !== '') {
            $partidasQuery->whereDate('scheduled_at', '<=', $filters['data_fim']);
        }

        $partidas = $partidasQuery
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $ligas = Liga::query()
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $clubes = LigaClube::query()
            ->with('liga:id,nome')
            ->orderBy('nome')
            ->get(['id', 'nome', 'liga_id']);

        return view('admin.partidas.index', [
            'partidas' => $partidas,
            'filters' => $filters,
            'estadoOptions' => self::ESTADO_OPTIONS,
            'ligas' => $ligas,
            'clubes' => $clubes,
        ]);
    }

    public function edit(Request $request, Partida $partida): View
    {
        $partida->loadMissing([
            'liga:id,nome',
            'mandante:id,nome,user_id',
            'mandante.user:id,name',
            'visitante:id,nome,user_id',
            'visitante.user:id,name',
            'woParaUser:id,name',
            'placarRegistradoPorUser:id,name',
        ]);

        return view('admin.partidas.edit', [
            'partida' => $partida,
            'estadoOptions' => self::ESTADO_OPTIONS,
            'woMotivoOptions' => self::WO_MOTIVO_OPTIONS,
            'returnQuery' => $request->getQueryString(),
        ]);
    }

    public function update(Request $request, Partida $partida): RedirectResponse
    {
        $validated = $request->validate([
            'estado' => ['required', Rule::in(array_keys(self::ESTADO_OPTIONS))],
            'placar_mandante' => ['nullable', 'integer', 'min:0'],
            'placar_visitante' => ['nullable', 'integer', 'min:0'],
            'wo_para_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'wo_motivo' => ['nullable', Rule::in(array_keys(self::WO_MOTIVO_OPTIONS))],
        ]);

        $partida->update([
            'estado' => $validated['estado'],
            'placar_mandante' => $this->nullableInt($request->input('placar_mandante')),
            'placar_visitante' => $this->nullableInt($request->input('placar_visitante')),
            'wo_para_user_id' => $this->nullableInt($request->input('wo_para_user_id')),
            'wo_motivo' => $this->nullableString($request->input('wo_motivo')),
        ]);

        return redirect()
            ->route('admin.partidas.index', $request->query())
            ->with('success', 'Partida atualizada com sucesso.');
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }
}
