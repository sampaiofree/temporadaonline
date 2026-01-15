<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Confederacao;
use App\Models\Liga;
use App\Models\LigaLeilao;
use App\Models\WhatsappConnection;
use App\Services\EvolutionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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

    public function create(EvolutionService $evolutionService): View
    {
        return view('admin.ligas.create', [
            'confederacoes' => Confederacao::with(['jogo', 'geracao', 'plataforma'])->orderBy('nome')->get(),
            'statusOptions' => self::STATUS_OPTIONS,
            'whatsappGroups' => $this->resolveWhatsappGroups($evolutionService),
        ]);
    }

    public function store(Request $request, EvolutionService $evolutionService): RedirectResponse
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'confederacao_id' => 'required|exists:confederacoes,id',
            'max_times' => 'required|integer|min:1',
            'saldo_inicial' => 'required|integer|min:0',
            'usuario_pontuacao' => 'nullable|numeric|min:0|max:5',
            'whatsapp_grupo_link' => 'nullable|url|max:255',
            'whatsapp_grupo_jid' => 'nullable|string|max:255',
            'descricao' => 'nullable|string|max:2000',
            'regras' => 'nullable|string|max:2000',
            'status' => 'required|in:ativa,aguardando',
            'imagem' => 'nullable|image:allow_svg|max:2048',
            'periodos' => 'array',
            'periodos.*.inicio' => 'nullable|date',
            'periodos.*.fim' => 'nullable|date',
            'leiloes' => 'array',
            'leiloes.*.inicio' => 'nullable|date',
            'leiloes.*.fim' => 'nullable|date',
        ]);

        $confederacao = Confederacao::with(['jogo', 'geracao', 'plataforma'])
            ->findOrFail((int) $data['confederacao_id']);

        if (! $confederacao->jogo_id || ! $confederacao->geracao_id) {
            throw ValidationException::withMessages([
                'confederacao_id' => ['Confederacao precisa ter jogo e geracao configurados.'],
            ]);
        }

        $data = array_merge($data, [
            'tipo' => 'publica',
            'jogo_id' => $confederacao->jogo_id,
            'geracao_id' => $confederacao->geracao_id,
            'plataforma_id' => $confederacao->plataforma_id,
        ]);

        unset($data['periodos'], $data['leiloes']);

        if (! empty($data['whatsapp_grupo_jid'])) {
            $data['whatsapp_grupo_link'] = $this->resolveWhatsappInviteLink(
                $data['whatsapp_grupo_jid'],
                $evolutionService,
            );
        } else {
            $data['whatsapp_grupo_link'] = null;
        }

        $periodos = $this->normalizePeriodos($request->input('periodos', []));
        $leiloes = $this->normalizePeriodos($request->input('leiloes', []), 'leiloes');
        $this->assertLeilaoNaoConflitaComPeriodos($periodos, $leiloes);
        if ($request->hasFile('imagem')) {
            $data['imagem'] = $request->file('imagem')->store('ligas', 'public');
        }

        $liga = Liga::create($data);
        if ($periodos) {
            $liga->periodos()->createMany($periodos);
        }
        if ($leiloes) {
            $liga->leiloes()->createMany($leiloes);
        }

        return redirect()->route('admin.ligas.index')->with('success', 'Liga criada com sucesso.');
    }

    public function edit(Liga $liga, EvolutionService $evolutionService): View
    {
        return view('admin.ligas.edit', [
            'liga' => $liga->loadMissing(['jogo', 'geracao', 'plataforma', 'confederacao.jogo', 'confederacao.geracao', 'confederacao.plataforma', 'periodos', 'leiloes']),
            'statusOptions' => self::STATUS_OPTIONS,
            'hasClubes' => $liga->clubes()->exists(),
            'hasUsers' => $liga->users()->exists(),
            'whatsappGroups' => $this->resolveWhatsappGroups($evolutionService),
        ]);
    }

    public function update(Request $request, Liga $liga, EvolutionService $evolutionService): RedirectResponse
    {
        $rules = [
            'nome' => 'required|string|max:255',
            'max_times' => 'required|integer|min:1',
            'saldo_inicial' => 'required|integer|min:0',
            'usuario_pontuacao' => 'nullable|numeric|min:0|max:5',
            'whatsapp_grupo_link' => 'nullable|url|max:255',
            'whatsapp_grupo_jid' => 'nullable|string|max:255',
            'descricao' => 'nullable|string|max:2000',
            'regras' => 'nullable|string|max:2000',
            'status' => 'required|in:ativa,aguardando',
            'imagem' => 'nullable|image:allow_svg|max:2048',
            'periodos' => 'array',
            'periodos.*.inicio' => 'nullable|date',
            'periodos.*.fim' => 'nullable|date',
            'leiloes' => 'array',
            'leiloes.*.inicio' => 'nullable|date',
            'leiloes.*.fim' => 'nullable|date',
        ];

        $data = $request->validate($rules);

        if (! empty($data['whatsapp_grupo_jid'])) {
            $currentJid = $liga->whatsapp_grupo_jid;
            $currentLink = $liga->whatsapp_grupo_link;
            if ($currentLink && $currentJid === $data['whatsapp_grupo_jid']) {
                $data['whatsapp_grupo_link'] = $currentLink;
            } else {
                $data['whatsapp_grupo_link'] = $this->resolveWhatsappInviteLink(
                    $data['whatsapp_grupo_jid'],
                    $evolutionService,
                );
            }
        } else {
            $data['whatsapp_grupo_link'] = null;
        }

        if ($request->hasFile('imagem')) {
            $oldImage = $liga->imagem;
            $data['imagem'] = $request->file('imagem')->store('ligas', 'public');
            if ($oldImage) {
                Storage::disk('public')->delete($oldImage);
            }
        }

        unset($data['periodos'], $data['leiloes']);

        $periodos = $this->normalizePeriodos($request->input('periodos', []));
        $leiloes = $this->normalizePeriodos($request->input('leiloes', []), 'leiloes');
        $this->assertLeilaoNaoConflitaComPeriodos($periodos, $leiloes);

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
        $liga->leiloes()->delete();
        if ($leiloes) {
            $liga->leiloes()->createMany($leiloes);
        }

        return redirect()->route('admin.ligas.index')->with('success', 'Liga atualizada com sucesso.');
    }

    private function normalizePeriodos(array $periodos, string $field = 'periodos'): array
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
                        'leiloes' => ["Período de leilão {$leilaoInicio->toDateString()} - {$leilaoFim->toDateString()} conflita com período de partidas {$periodoInicio->toDateString()} - {$periodoFim->toDateString()}."],
                    ]);
                }
            }
        }
    }

    private function resolveWhatsappGroups(EvolutionService $evolutionService): array
    {
        $connection = WhatsappConnection::first();
        if (! $connection || ! config('services.evolution.url') || ! config('services.evolution.key')) {
            return [];
        }

        return $evolutionService->listGroups($connection->instance_name);
    }

    private function resolveWhatsappInviteLink(string $groupJid, EvolutionService $evolutionService): string
    {
        $connection = WhatsappConnection::first();
        if (! $connection || ! config('services.evolution.url') || ! config('services.evolution.key')) {
            throw ValidationException::withMessages([
                'whatsapp_grupo_jid' => ['Conecte o WhatsApp do administrador antes de selecionar o grupo.'],
            ]);
        }

        $result = $evolutionService->fetchInviteCode($connection->instance_name, $groupJid);
        if (is_string($result)) {
            throw ValidationException::withMessages([
                'whatsapp_grupo_jid' => ['Nao foi possivel obter o link do grupo. Confirme se o WhatsApp conectado e admin.'],
            ]);
        }

        $code = $result['inviteCode']
            ?? $result['code']
            ?? $result['invite_code']
            ?? Arr::get($result, 'response.inviteCode')
            ?? Arr::get($result, 'response.code')
            ?? Arr::get($result, 'data.inviteCode');

        if (! $code || ! is_string($code)) {
            throw ValidationException::withMessages([
                'whatsapp_grupo_jid' => ['Nao foi possivel obter o link do grupo. Confirme se o WhatsApp conectado e admin.'],
            ]);
        }

        return 'https://chat.whatsapp.com/' . trim($code);
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
