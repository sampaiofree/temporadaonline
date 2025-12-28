<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plataforma;
use App\Models\Profile;
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

        $users = User::with('profile.plataformaRegistro')
            ->withCount('disponibilidades')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'plataformas' => Plataforma::orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
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
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
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

        $profile->fill([
            'nickname' => $this->normalizeNullable($request, 'nickname', $profile->nickname),
            'whatsapp' => $this->normalizeNullable($request, 'whatsapp', $profile->whatsapp),
            'plataforma_id' => $request->input('plataforma_id') ?: null,
        ]);

        if ($profile->plataforma_id) {
            $profile->plataforma = Plataforma::find($profile->plataforma_id)?->nome;
        } else {
            $profile->plataforma = null;
        }

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
