<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Geracao;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\Plataforma;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LigaController extends Controller
{
    private const STATUS_OPTIONS = [
        'ativa' => 'Ativa',
        'aguardando' => 'Inativa',
    ];

    public function index(): View
    {
        $ligas = Liga::with(['jogo', 'geracao', 'plataforma'])
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
            'jogos' => Jogo::orderBy('nome')->get(),
            'geracoes' => Geracao::orderBy('nome')->get(),
            'plataformas' => Plataforma::orderBy('nome')->get(),
            'statusOptions' => self::STATUS_OPTIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'jogo_id' => 'required|exists:jogos,id',
            'geracao_id' => 'required|exists:geracoes,id',
            'plataforma_id' => 'required|exists:plataformas,id',
            'max_times' => 'required|integer|min:1',
            'status' => 'required|in:ativa,aguardando',
            'imagem' => 'nullable|image|max:2048',
        ]);

        $data = array_merge($data, [
            'descricao' => '',
            'regras' => '',
            'tipo' => 'publica',
        ]);

        if ($request->hasFile('imagem')) {
            $data['imagem'] = $request->file('imagem')->store('ligas', 'public');
        }

        Liga::create($data);

        return redirect()->route('admin.ligas.index')->with('success', 'Liga criada com sucesso.');
    }

    public function edit(Liga $liga): View
    {
        return view('admin.ligas.edit', [
            'liga' => $liga->loadMissing(['jogo', 'geracao', 'plataforma']),
            'jogos' => Jogo::orderBy('nome')->get(),
            'geracoes' => Geracao::orderBy('nome')->get(),
            'plataformas' => Plataforma::orderBy('nome')->get(),
            'statusOptions' => self::STATUS_OPTIONS,
            'hasClubes' => $liga->clubes()->exists(),
            'hasUsers' => $liga->users()->exists(),
        ]);
    }

    public function update(Request $request, Liga $liga): RedirectResponse
    {
        $hasClubes = $liga->clubes()->exists();

        $rules = [
            'nome' => 'required|string|max:255',
            'max_times' => 'required|integer|min:1',
            'status' => 'required|in:ativa,aguardando',
            'imagem' => 'nullable|image|max:2048',
        ];

        if (! $hasClubes) {
            $rules = array_merge($rules, [
                'jogo_id' => 'required|exists:jogos,id',
                'geracao_id' => 'required|exists:geracoes,id',
                'plataforma_id' => 'required|exists:plataformas,id',
            ]);
        }

        $data = $request->validate($rules);

        if ($request->hasFile('imagem')) {
            $oldImage = $liga->imagem;
            $data['imagem'] = $request->file('imagem')->store('ligas', 'public');
            if ($oldImage) {
                Storage::disk('public')->delete($oldImage);
            }
        }

        if ($hasClubes) {
            unset($data['jogo_id'], $data['geracao_id'], $data['plataforma_id']);
        }

        $liga->update($data);

        return redirect()->route('admin.ligas.index')->with('success', 'Liga atualizada com sucesso.');
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
