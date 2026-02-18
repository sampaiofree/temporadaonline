<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Confederacao;
use App\Models\Idioma;
use App\Models\Plataforma;
use App\Models\Profile;
use App\Models\Regiao;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $confederacoes = Confederacao::query()
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $allowedConfederacoes = $confederacoes->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $selectedConfederacoes = collect((array) $request->query('confederacoes', []))
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => in_array($id, $allowedConfederacoes, true))
            ->unique()
            ->values();

        $users = User::with('profile.plataformaRegistro')
            ->withCount('disponibilidades')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($selectedConfederacoes->isNotEmpty(), function ($query) use ($selectedConfederacoes) {
                $ids = $selectedConfederacoes->all();

                $query->where(function ($query) use ($ids) {
                    $query->whereHas('ligas', function ($ligasQuery) use ($ids) {
                        $ligasQuery->whereIn('ligas.confederacao_id', $ids);
                    })->orWhereHas('clubesLiga', function ($clubesQuery) use ($ids) {
                        $clubesQuery->whereIn('liga_clubes.confederacao_id', $ids);
                    });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'confederacoes' => $confederacoes,
            'selectedConfederacoes' => $selectedConfederacoes,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'plataformas' => Plataforma::orderBy('nome')->get(['id', 'nome']),
            'regioes' => Regiao::orderBy('nome')->get(['id', 'nome']),
            'idiomas' => Idioma::orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'nickname' => 'nullable|string|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'plataforma_id' => 'nullable|integer|exists:plataformas,id',
            'regiao_id' => 'nullable|integer|exists:regioes,id',
            'idioma_id' => 'nullable|integer|exists:idiomas,id',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_admin' => $request->boolean('is_admin'),
        ]);

        $this->syncProfile($user, $request);

        return redirect()->route('admin.users.index')->with('success', 'Usuário salvo com sucesso');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'plataformas' => Plataforma::orderBy('nome')->get(['id', 'nome']),
            'regioes' => Regiao::orderBy('nome')->get(['id', 'nome']),
            'idiomas' => Idioma::orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'nickname' => 'nullable|string|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'plataforma_id' => 'nullable|integer|exists:plataformas,id',
            'regiao_id' => 'nullable|integer|exists:regioes,id',
            'idioma_id' => 'nullable|integer|exists:idiomas,id',
        ]);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_admin' => $request->boolean('is_admin'),
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        $this->syncProfile($user, $request);

        return redirect()->route('admin.users.index')->with('success', 'Usuário salvo com sucesso');
    }

    private function syncProfile(User $user, Request $request): void
    {
        $profile = $user->profile ?? new Profile(['user_id' => $user->id]);
        $regiaoId = $request->input('regiao_id') ?: null;
        $idiomaId = $request->input('idioma_id') ?: null;

        $profile->fill([
            'nickname' => $this->normalizeNullable($request, 'nickname', $profile->nickname),
            'whatsapp' => $this->normalizeNullable($request, 'whatsapp', $profile->whatsapp),
            'plataforma_id' => $request->input('plataforma_id') ?: null,
            'regiao_id' => $regiaoId,
            'idioma_id' => $idiomaId,
            'regiao' => $regiaoId ? Regiao::query()->find($regiaoId)?->nome : null,
            'idioma' => $idiomaId ? Idioma::query()->find($idiomaId)?->nome : null,
        ]);

        $profile->save();
    }

    private function normalizeNullable(Request $request, string $field, mixed $current): ?string
    {
        if (! $request->has($field)) {
            return $current;
        }

        $value = trim((string) $request->input($field, ''));

        return $value === '' ? null : $value;
    }
}
