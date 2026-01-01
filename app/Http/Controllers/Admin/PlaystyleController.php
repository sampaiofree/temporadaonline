<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Playstyle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PlaystyleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
        ];

        $playstyles = $this->applyFilters(Playstyle::orderBy('nome'), $request)
            ->paginate()
            ->withQueryString();

        return view('admin.playstyles.index', [
            'playstyles' => $playstyles,
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'uploads' => 'required|array|min:1',
            'uploads.*.nome' => 'required|string|max:150',
            'uploads.*.imagem' => 'required|image:allow_svg|max:4096',
        ]);

        $created = 0;
        $updated = 0;

        foreach ($validated['uploads'] as $index => $upload) {
            $file = $request->file("uploads.{$index}.imagem");

            if (! $file) {
                continue;
            }

            $nome = trim($upload['nome']);
            $path = $file->store('playstyles', 'public');

            $playstyle = Playstyle::where('nome', $nome)->first();

            if ($playstyle) {
                if ($playstyle->imagem) {
                    Storage::disk('public')->delete($playstyle->imagem);
                }
                $playstyle->update(['imagem' => $path]);
                $updated++;
                continue;
            }

            Playstyle::create([
                'nome' => $nome,
                'imagem' => $path,
            ]);
            $created++;
        }

        $messageParts = [];
        if ($created > 0) {
            $messageParts[] = "{$created} playstyle(s) cadastrado(s)";
        }
        if ($updated > 0) {
            $messageParts[] = "{$updated} playstyle(s) atualizado(s)";
        }

        $message = $messageParts
            ? implode(', ', $messageParts).'.'
            : 'Nenhum playstyle processado.';

        $request->session()->flash('success', $message);

        if ($request->wantsJson()) {
            return response()->json(['created' => $created, 'updated' => $updated], 201);
        }

        return redirect()->route('admin.playstyles.index');
    }

    public function destroy(Request $request, Playstyle $playstyle): RedirectResponse
    {
        if ($playstyle->imagem) {
            Storage::disk('public')->delete($playstyle->imagem);
        }

        $playstyle->delete();

        return redirect()
            ->route('admin.playstyles.index', $request->query())
            ->with('success', 'Playstyle removido com sucesso.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $deleted = 0;
        $this->applyFilters(Playstyle::query()->orderBy('id'), $request)
            ->chunkById(100, function ($playstyles) use (&$deleted) {
                foreach ($playstyles as $playstyle) {
                    if ($playstyle->imagem) {
                        Storage::disk('public')->delete($playstyle->imagem);
                    }

                    $playstyle->delete();
                    $deleted++;
                }
            });

        $message = $deleted
            ? "{$deleted} playstyle(s) removido(s)."
            : 'Nenhum playstyle encontrado para exclusao.';

        return redirect()->route('admin.playstyles.index', $request->query())->with('success', $message);
    }

    private function applyFilters(Builder $query, Request $request): Builder
    {
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $like = "%{$search}%";
            $query->where('nome', 'like', $like);
        }

        return $query;
    }
}
