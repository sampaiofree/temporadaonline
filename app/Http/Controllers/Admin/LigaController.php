<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Confederacao;
use App\Models\Liga;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LigaController extends Controller
{
    private const STATUS_OPTIONS = [
        'ativa' => 'Ativa',
        'aguardando' => 'Inativa',
    ];

    public function index(): View
    {
        $ligas = Liga::with(['jogo', 'geracao', 'plataforma', 'confederacao'])
            ->withCount(['clubes', 'users'])
            ->orderByDesc('created_at')
            ->get();

        return view('admin.ligas.index', [
            'ligas' => $ligas,
        ]);
    }

    public function create(): View
    {
        return view('admin.ligas.create', [
            'confederacoes' => Confederacao::with(['jogo', 'geracao', 'plataforma'])->orderBy('nome')->get(),
            'statusOptions' => self::STATUS_OPTIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'confederacao_id' => 'required|exists:confederacoes,id',
            'max_times' => 'required|integer|min:1',
            'saldo_inicial' => 'required|integer|min:0',
            'usuario_pontuacao' => 'nullable|numeric|min:0|max:5',
            'whatsapp_grupo_link' => 'nullable|url|max:255',
            'status' => 'required|in:ativa,aguardando',
            'imagem' => 'nullable|image:allow_svg|max:2048',
            'periodos' => 'array',
            'periodos.*.inicio' => 'nullable|date',
            'periodos.*.fim' => 'nullable|date',
        ]);

        $confederacao = Confederacao::with(['jogo', 'geracao', 'plataforma'])
            ->findOrFail((int) $data['confederacao_id']);

        if (! $confederacao->jogo_id || ! $confederacao->geracao_id || ! $confederacao->plataforma_id) {
            throw ValidationException::withMessages([
                'confederacao_id' => ['Confederacao precisa ter jogo, geracao e plataforma configurados.'],
            ]);
        }

        $data = array_merge($data, [
            'descricao' => '',
            'regras' => '',
            'tipo' => 'publica',
            'jogo_id' => $confederacao->jogo_id,
            'geracao_id' => $confederacao->geracao_id,
            'plataforma_id' => $confederacao->plataforma_id,
        ]);

        unset($data['periodos']);

        $periodos = $this->normalizePeriodos($request->input('periodos', []));
        if ($request->hasFile('imagem')) {
            $data['imagem'] = $request->file('imagem')->store('ligas', 'public');
        }

        $liga = Liga::create($data);
        if ($periodos) {
            $liga->periodos()->createMany($periodos);
        }

        return redirect()->route('admin.ligas.index')->with('success', 'Liga criada com sucesso.');
    }

    public function edit(Liga $liga): View
    {
        return view('admin.ligas.edit', [
            'liga' => $liga->loadMissing(['jogo', 'geracao', 'plataforma', 'confederacao.jogo', 'confederacao.geracao', 'confederacao.plataforma', 'periodos']),
            'statusOptions' => self::STATUS_OPTIONS,
            'hasClubes' => $liga->clubes()->exists(),
            'hasUsers' => $liga->users()->exists(),
        ]);
    }

    public function update(Request $request, Liga $liga): RedirectResponse
    {
        $rules = [
            'nome' => 'required|string|max:255',
            'max_times' => 'required|integer|min:1',
            'saldo_inicial' => 'required|integer|min:0',
            'usuario_pontuacao' => 'nullable|numeric|min:0|max:5',
            'whatsapp_grupo_link' => 'nullable|url|max:255',
            'status' => 'required|in:ativa,aguardando',
            'imagem' => 'nullable|image:allow_svg|max:2048',
            'periodos' => 'array',
            'periodos.*.inicio' => 'nullable|date',
            'periodos.*.fim' => 'nullable|date',
        ];

        $data = $request->validate($rules);

        if ($request->hasFile('imagem')) {
            $oldImage = $liga->imagem;
            $data['imagem'] = $request->file('imagem')->store('ligas', 'public');
            if ($oldImage) {
                Storage::disk('public')->delete($oldImage);
            }
        }

        unset($data['periodos']);

        $periodos = $this->normalizePeriodos($request->input('periodos', []));

        $liga->loadMissing('confederacao');
        if (! $liga->confederacao) {
            throw ValidationException::withMessages([
                'confederacao_id' => ['Confederacao nao encontrada.'],
            ]);
        }

        $data['jogo_id'] = $liga->confederacao->jogo_id;
        $data['geracao_id'] = $liga->confederacao->geracao_id;
        $data['plataforma_id'] = $liga->confederacao->plataforma_id;

        $liga->update($data);
        $liga->periodos()->delete();
        if ($periodos) {
            $liga->periodos()->createMany($periodos);
        }

        return redirect()->route('admin.ligas.index')->with('success', 'Liga atualizada com sucesso.');
    }

    private function normalizePeriodos(array $periodos): array
    {
        $normalized = [];

        foreach ($periodos as $index => $periodo) {
            $inicio = $periodo['inicio'] ?? null;
            $fim = $periodo['fim'] ?? null;

            if (! $inicio && ! $fim) {
                continue;
            }

            if (! $inicio || ! $fim) {
                throw ValidationException::withMessages([
                    'periodos' => ['Informe data inicial e final para todos os periodos.'],
                ]);
            }

            $inicioDate = Carbon::parse($inicio)->startOfDay();
            $fimDate = Carbon::parse($fim)->startOfDay();

            if ($inicioDate->gt($fimDate)) {
                throw ValidationException::withMessages([
                    'periodos' => ['A data inicial precisa ser menor ou igual a data final.'],
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
                    'periodos' => ['Existe sobreposicao entre periodos cadastrados.'],
                ]);
            }
        }

        return $normalized;
    }

    public function destroy(Liga $liga): RedirectResponse
    {
        if ($liga->clubes()->exists() || $liga->users()->exists()) {
            abort(403);
        }

        if ($liga->imagem) {
            Storage::disk('public')->delete($liga->imagem);
        }

        $liga->delete();

        return redirect()->route('admin.ligas.index')->with('success', 'Liga removida com sucesso.');
    }
}
