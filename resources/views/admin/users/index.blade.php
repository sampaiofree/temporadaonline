<x-app-layout title="Usuários">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Usuários</h2>
                <p class="text-sm text-slate-500">Liste, busque e edite usuários rapidamente.</p>
            </div>
            <div class="flex items-center gap-3">
                <form method="GET" action="{{ route('admin.users.index') }}" class="flex gap-2">
                    <input
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Buscar nome ou email"
                        class="h-10 w-60 rounded-xl border border-slate-200 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-300"
                    >
                        Buscar
                    </button>
                </form>
                <a
                    href="{{ route('admin.users.create') }}"
                    class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                >
                    Criar usuário
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">ID</th>
                            <th class="px-4 py-3 font-semibold">Nome</th>
                            <th class="px-4 py-3 font-semibold">Email</th>
                            <th class="px-4 py-3 font-semibold">Tipo</th>
                            <th class="px-4 py-3 font-semibold">Plataforma</th>
                            <th class="px-4 py-3 font-semibold">Horário</th>
                            <th class="px-4 py-3 font-semibold">Criado em</th>
                            <th class="px-4 py-3 font-semibold">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($users as $user)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-4 text-slate-600">{{ $user->id }}</td>
                                <td class="px-4 py-4 font-semibold text-slate-900">{{ $user->name }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $user->email }}</td>
                                <td class="px-4 py-4 text-slate-600">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $user->is_admin ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $user->is_admin ? 'Admin' : 'Gamer' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-slate-600">{{ $user->profile?->plataforma_nome ?? '—' }}</td>
                                <td class="px-4 py-4 text-slate-600">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a
                                            href="{{ route('admin.users.horarios.index', $user) }}"
                                            class="inline-flex items-center rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                                        >
                                            Gerenciar horários
                                        </a>
                                        @if ($user->disponibilidades_count)
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">
                                                {{ $user->disponibilidades_count }}
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">
                                                0
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-slate-600">{{ $user->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-4 text-slate-600">
                                    <a
                                        href="{{ route('admin.users.edit', $user) }}"
                                        class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                                    >
                                        Editar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-sm text-slate-500">
                                    Nenhum usuário encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end">
            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
