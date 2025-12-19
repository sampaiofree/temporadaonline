@props([
    'action',
    'method' => 'POST',
    'user' => null,
    'plataformas',
    'submitLabel' => 'Salvar usuário',
    'isEdit' => false,
])

@php
    $profile = $user?->profile;
    $currentName = old('name', $user->name ?? '');
    $currentEmail = old('email', $user->email ?? '');
    $currentWhatsapp = old('whatsapp', $profile?->whatsapp ?? '');
    $currentNickname = old('nickname', $profile?->nickname ?? '');
    $currentPlataforma = old('plataforma_id', $profile?->plataforma_id ?? '');
    $isAdmin = old('is_admin', $user?->is_admin ?? false);
@endphp

<form action="{{ $action }}" method="POST" class="space-y-6">
    @csrf
    @if (! in_array(strtoupper($method), ['POST'], true))
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="name" class="block text-sm font-semibold text-slate-700">Nome</label>
            <input
                type="text"
                id="name"
                name="name"
                value="{{ $currentName }}"
                required
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
            @error('name')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-semibold text-slate-700">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ $currentEmail }}"
                required
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
            @error('email')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="password" class="block text-sm font-semibold text-slate-700">Senha</label>
            <input
                type="password"
                id="password"
                name="password"
                {{ $isEdit ? '' : 'required' }}
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
            <p class="mt-1 text-xs text-slate-500">
                {{ $isEdit ? 'Preencha para atualizar o password, deixe em branco para manter a atual.' : 'Senha obrigatória ao criar um novo usuário.' }}
            </p>
            @error('password')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-3">
            <input
                type="checkbox"
                id="is_admin"
                name="is_admin"
                value="1"
                @checked($isAdmin)
                class="h-4 w-4 rounded border-slate-200 text-blue-600 focus:ring-blue-500"
            >
            <label for="is_admin" class="text-sm font-semibold text-slate-700">Usuário administrador</label>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-3">
        <div>
            <label for="whatsapp" class="block text-sm font-semibold text-slate-700">WhatsApp</label>
            <input
                type="text"
                id="whatsapp"
                name="whatsapp"
                value="{{ $currentWhatsapp }}"
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
            @error('whatsapp')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="nickname" class="block text-sm font-semibold text-slate-700">Nickname</label>
            <input
                type="text"
                id="nickname"
                name="nickname"
                value="{{ $currentNickname }}"
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
            @error('nickname')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="plataforma_id" class="block text-sm font-semibold text-slate-700">Plataforma</label>
            <select
                id="plataforma_id"
                name="plataforma_id"
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
                <option value="">Sem plataforma</option>
                @foreach($plataformas as $plataforma)
                    <option value="{{ $plataforma->id }}" @selected($currentPlataforma == $plataforma->id)>{{ $plataforma->nome }}</option>
                @endforeach
            </select>
            @error('plataforma_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex items-center justify-between gap-3 pt-4">
        <a href="{{ route('admin.users.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Voltar para a lista</a>
        <button
            type="submit"
            class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
        >
            {{ $submitLabel }}
        </button>
    </div>
</form>
