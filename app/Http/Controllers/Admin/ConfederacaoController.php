<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Confederacao;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\LigaJogador;
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

        $usuariosPorConfederacao = LigaJogador::query()
            ->join('ligas', 'liga_jogador.liga_id', '=', 'ligas.id')
            ->selectRaw('ligas.confederacao_id, COUNT(DISTINCT liga_jogador.user_id) as usuarios_count')
            ->groupBy('ligas.confederacao_id')
            ->pluck('usuarios_count', 'ligas.confederacao_id');

        $confederacoes->each(function (Confederacao $confederacao) use ($usuariosPorConfederacao): void {
            $confederacao->usuarios_count = (int) ($usuariosPorConfederacao[$confederacao->id] ?? 0);
        });

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
            'ganho_vitoria_partida' => 'required|integer|min:0',
            'ganho_empate_partida' => 'required|integer|min:0',
            'ganho_derrota_partida' => 'required|integer|min:0',
            'jogo_id' => 'required|exists:jogos,id',
            'geracao_id' => 'required|exists:geracoes,id',
            'periodos' => 'array',
            'periodos.*.inicio' => 'nullable|date_format:Y-m-d\TH:i',
            'periodos.*.fim' => 'nullable|date_format:Y-m-d\TH:i',
            'leiloes' => 'array',
            'leiloes.*.inicio' => 'nullable|date_format:Y-m-d\TH:i',
            'leiloes.*.fim' => 'nullable|date_format:Y-m-d\TH:i',
            'roubos_multa' => 'array',
            'roubos_multa.*.inicio' => 'nullable|date_format:Y-m-d\TH:i',
            'roubos_multa.*.fim' => 'nullable|date_format:Y-m-d\TH:i',
        ]);

        $data['nome'] = trim($data['nome']);
        if (array_key_exists('descricao', $data)) {
            $data['descricao'] = $data['descricao'] !== null ? trim($data['descricao']) : null;
        }

        if ($request->hasFile('imagem')) {
            $data['imagem'] = $request->file('imagem')->store('confederacoes', 'public');
        }

        $timezone = (string) ($data['timezone'] ?? 'America/Sao_Paulo');
        $periodos = $this->normalizePeriodos($request->input('periodos', []), 'periodos', $timezone, true);
        $leiloes = $this->normalizePeriodos($request->input('leiloes', []), 'leiloes', $timezone, true);
        $roubosMulta = $this->normalizePeriodos($request->input('roubos_multa', []), 'roubos_multa', $timezone, true);
        unset($data['periodos'], $data['leiloes'], $data['roubos_multa']);

        $confederacao = Confederacao::create($data);

        if ($periodos) {
            $confederacao->periodos()->createMany($periodos);
        }

        if ($leiloes) {
            $confederacao->leiloes()->createMany($leiloes);
        }

        if ($roubosMulta) {
            $confederacao->roubosMulta()->createMany($roubosMulta);
        }

        return redirect()->route('admin.confederacoes.index')->with('success', 'Confederacao criada com sucesso.');
    }

    public function edit(Confederacao $confederacao): View
    {
        $confederacao->loadCount('ligas');
        $confederacao->loadMissing(['periodos', 'leiloes', 'roubosMulta']);

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
            'ganho_vitoria_partida' => 'required|integer|min:0',
            'ganho_empate_partida' => 'required|integer|min:0',
            'ganho_derrota_partida' => 'required|integer|min:0',
            'jogo_id' => 'required|exists:jogos,id',
            'geracao_id' => 'required|exists:geracoes,id',
            'periodos' => 'array',
            'periodos.*.inicio' => 'nullable|date_format:Y-m-d\TH:i',
            'periodos.*.fim' => 'nullable|date_format:Y-m-d\TH:i',
            'leiloes' => 'array',
            'leiloes.*.inicio' => 'nullable|date_format:Y-m-d\TH:i',
            'leiloes.*.fim' => 'nullable|date_format:Y-m-d\TH:i',
            'roubos_multa' => 'array',
            'roubos_multa.*.inicio' => 'nullable|date_format:Y-m-d\TH:i',
            'roubos_multa.*.fim' => 'nullable|date_format:Y-m-d\TH:i',
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

        $timezone = (string) ($data['timezone'] ?? $confederacao->timezone ?? 'America/Sao_Paulo');
        $periodos = $this->normalizePeriodos($request->input('periodos', []), 'periodos', $timezone, true);
        $leiloes = $this->normalizePeriodos($request->input('leiloes', []), 'leiloes', $timezone, true);
        $roubosMulta = $this->normalizePeriodos($request->input('roubos_multa', []), 'roubos_multa', $timezone, true);
        unset($data['periodos'], $data['leiloes'], $data['roubos_multa']);

        $confederacao->update($data);

        $confederacao->periodos()->delete();
        if ($periodos) {
            $confederacao->periodos()->createMany($periodos);
        }

        $confederacao->leiloes()->delete();
        if ($leiloes) {
            $confederacao->leiloes()->createMany($leiloes);
        }

        $confederacao->roubosMulta()->delete();
        if ($roubosMulta) {
            $confederacao->roubosMulta()->createMany($roubosMulta);
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

    private function normalizePeriodos(
        array $periodos,
        string $field = 'periodos',
        ?string $timezone = null,
        bool $withTime = false,
    ): array {
        $normalized = [];

        foreach ($periodos as $periodo) {
            $inicio = $periodo['inicio'] ?? null;
            $fim = $periodo['fim'] ?? null;

            if (! $inicio && ! $fim) {
                continue;
            }

            if (! $inicio || ! $fim) {
                throw ValidationException::withMessages([
                    $field => ['Informe inicio e fim para todos os registros.'],
                ]);
            }

            $inicioDate = Carbon::parse($inicio, $timezone ?? config('app.timezone'));
            $fimDate = Carbon::parse($fim, $timezone ?? config('app.timezone'));

            if ($withTime) {
                $inicioDate = $inicioDate->second(0);
                $fimDate = $fimDate->second(0);
            } else {
                $inicioDate = $inicioDate->startOfDay();
                $fimDate = $fimDate->startOfDay();
            }

            if ($inicioDate->gt($fimDate)) {
                throw ValidationException::withMessages([
                    $field => ['O inicio precisa ser menor ou igual ao fim.'],
                ]);
            }

            $normalized[] = [
                'inicio' => $withTime
                    ? $inicioDate->format('Y-m-d H:i:s')
                    : $inicioDate->toDateString(),
                'fim' => $withTime
                    ? $fimDate->format('Y-m-d H:i:s')
                    : $fimDate->toDateString(),
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
}
