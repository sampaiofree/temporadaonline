<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Confederacao;
use App\Models\Geracao;
use App\Models\Jogo;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConfederacaoController extends Controller
{
    public function index(): View
    {
        $confederacoes = Confederacao::orderByDesc('created_at')
            ->withCount('ligas')
            ->get();

        return view('admin.confederacoes.index', [
            'confederacoes' => $confederacoes,
        ]);
    }

    public function create(): View
    {
        return view('admin.confederacoes.create', [
            'jogos' => Jogo::orderBy('nome')->get(),
            'geracoes' => Geracao::orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => 'required|string|max:150|unique:confederacoes,nome',
            'descricao' => 'nullable|string',
            'imagem' => 'nullable|image:allow_svg|max:2048',
            'timezone' => 'required|timezone',
            'jogo_id' => 'required|exists:jogos,id',
            'geracao_id' => 'required|exists:geracoes,id',
            'periodos' => 'array',
            'periodos.*.inicio' => 'nullable|date',
            'periodos.*.fim' => 'nullable|date',
            'leiloes' => 'array',
            'leiloes.*.inicio' => 'nullable|date',
            'leiloes.*.fim' => 'nullable|date',
        ]);

        $data['nome'] = trim($data['nome']);
        if (array_key_exists('descricao', $data)) {
            $data['descricao'] = $data['descricao'] !== null ? trim($data['descricao']) : null;
        }

        if ($request->hasFile('imagem')) {
            $data['imagem'] = $request->file('imagem')->store('confederacoes', 'public');
        }

        $periodos = $this->normalizePeriodos($request->input('periodos', []));
        $leiloes = $this->normalizePeriodos($request->input('leiloes', []), 'leiloes');
        $this->assertLeilaoNaoConflitaComPeriodos($periodos, $leiloes);
        unset($data['periodos'], $data['leiloes']);

        $confederacao = Confederacao::create($data);

        if ($periodos) {
            $confederacao->periodos()->createMany($periodos);
        }

        if ($leiloes) {
            $confederacao->leiloes()->createMany($leiloes);
        }

        return redirect()->route('admin.confederacoes.index')->with('success', 'Confederacao criada com sucesso.');
    }

    public function edit(Confederacao $confederacao): View
    {
        $confederacao->loadCount('ligas');
        $confederacao->loadMissing(['periodos', 'leiloes']);

        return view('admin.confederacoes.edit', [
            'confederacao' => $confederacao,
            'jogos' => Jogo::orderBy('nome')->get(),
            'geracoes' => Geracao::orderBy('nome')->get(),
            'lockSelections' => $confederacao->ligas_count > 0,
        ]);
    }

    public function update(Request $request, Confederacao $confederacao): RedirectResponse
    {
        $hasLigas = $confederacao->ligas()->exists();

        $rules = [
            'nome' => 'required|string|max:150|unique:confederacoes,nome,'.$confederacao->id,
            'descricao' => 'nullable|string',
            'imagem' => 'nullable|image:allow_svg|max:2048',
            'timezone' => 'required|timezone',
            'jogo_id' => 'required|exists:jogos,id',
            'geracao_id' => 'required|exists:geracoes,id',
            'periodos' => 'array',
            'periodos.*.inicio' => 'nullable|date',
            'periodos.*.fim' => 'nullable|date',
            'leiloes' => 'array',
            'leiloes.*.inicio' => 'nullable|date',
            'leiloes.*.fim' => 'nullable|date',
        ];

        if ($hasLigas) {
            unset($rules['jogo_id'], $rules['geracao_id']);
        }

        $data = $request->validate($rules);

        $data['nome'] = trim($data['nome']);
        if (array_key_exists('descricao', $data)) {
            $data['descricao'] = $data['descricao'] !== null ? trim($data['descricao']) : null;
        }

        if ($request->hasFile('imagem')) {
            $path = $request->file('imagem')->store('confederacoes', 'public');
            if ($confederacao->imagem) {
                Storage::disk('public')->delete($confederacao->imagem);
            }
            $data['imagem'] = $path;
        }

        if ($hasLigas) {
            unset($data['jogo_id'], $data['geracao_id']);
        }

        $periodos = $this->normalizePeriodos($request->input('periodos', []));
        $leiloes = $this->normalizePeriodos($request->input('leiloes', []), 'leiloes');
        $this->assertLeilaoNaoConflitaComPeriodos($periodos, $leiloes);
        unset($data['periodos'], $data['leiloes']);

        $confederacao->update($data);
        $confederacao->periodos()->delete();
        if ($periodos) {
            $confederacao->periodos()->createMany($periodos);
        }
        $confederacao->leiloes()->delete();
        if ($leiloes) {
            $confederacao->leiloes()->createMany($leiloes);
        }

        return redirect()->route('admin.confederacoes.index')->with('success', 'Confederacao atualizada com sucesso.');
    }

    public function destroy(Confederacao $confederacao): RedirectResponse
    {
        if ($confederacao->ligas()->exists()) {
            abort(403);
        }

        if ($confederacao->imagem) {
            Storage::disk('public')->delete($confederacao->imagem);
        }

        $confederacao->delete();

        return redirect()->route('admin.confederacoes.index')->with('success', 'Confederacao removida com sucesso.');
    }

    private function normalizePeriodos(array $periodos, string $field = 'periodos'): array
    {
        $normalized = [];

        foreach ($periodos as $periodo) {
            $inicio = $periodo['inicio'] ?? null;
            $fim = $periodo['fim'] ?? null;

            if (! $inicio && ! $fim) {
                continue;
            }

            if (! $inicio || ! $fim) {
                throw ValidationException::withMessages([
                    $field => ['Informe data inicial e final para todos os registros.'],
                ]);
            }

            $inicioDate = Carbon::parse($inicio)->startOfDay();
            $fimDate = Carbon::parse($fim)->startOfDay();

            if ($inicioDate->gt($fimDate)) {
                throw ValidationException::withMessages([
                    $field => ['A data inicial precisa ser menor ou igual a data final.'],
                ]);
            }

            $normalized[] = [
                'inicio' => $inicioDate->toDateString(),
                'fim' => $fimDate->toDateString(),
            ];
        }

        usort($normalized, fn ($a, $b) => strcmp($a['inicio'], $b['inicio']));

        for ($i = 1; $i < count($normalized); $i++) {
            $prev = $normalized[$i - 1];
            $current = $normalized[$i];

            if ($current['inicio'] <= $prev['fim']) {
                throw ValidationException::withMessages([
                    $field => ['Existe sobreposicao entre registros cadastrados.'],
                ]);
            }
        }

        return $normalized;
    }

    private function assertLeilaoNaoConflitaComPeriodos(array $periodos, array $leiloes): void
    {
        foreach ($leiloes as $leilao) {
            $leilaoInicio = Carbon::parse($leilao['inicio'])->startOfDay();
            $leilaoFim = Carbon::parse($leilao['fim'])->startOfDay();

            foreach ($periodos as $periodo) {
                $periodoInicio = Carbon::parse($periodo['inicio'])->startOfDay();
                $periodoFim = Carbon::parse($periodo['fim'])->startOfDay();

                $overlap = $leilaoInicio->lessThanOrEqualTo($periodoFim)
                    && $leilaoFim->greaterThanOrEqualTo($periodoInicio);

                if ($overlap) {
                    throw ValidationException::withMessages([
                        'leiloes' => ["Periodo de leilao {$leilaoInicio->toDateString()} - {$leilaoFim->toDateString()} conflita com periodo de partidas {$periodoInicio->toDateString()} - {$periodoFim->toDateString()}."],
                    ]);
                }
            }
        }
    }
}
