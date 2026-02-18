<x-app-layout title="Usuários">
    <x-slot name="header">
        <div
            x-data="{
                openFilters: @js($selectedConfederacoes->isNotEmpty()),
                selectedOption: '',
                selectedConfederacoes: @js($selectedConfederacoes->values()->map(fn ($id) => (int) $id)->all()),
                confederacoes: @js($confederacoes->map(fn ($confederacao) => ['id' => (int) $confederacao->id, 'nome' => $confederacao->nome])->values()->all()),
                addSelectedConfederacao() {
                    const id = Number(this.selectedOption);
                    if (!id || this.selectedConfederacoes.includes(id)) {
                        return;
                    }

                    this.selectedConfederacoes.push(id);
                    this.selectedOption = '';
                },
                removeSelectedConfederacao(id) {
                    this.selectedConfederacoes = this.selectedConfederacoes.filter((currentId) => currentId !== id);
                },
                confederacaoNome(id) {
                    const confederacao = this.confederacoes.find((item) => item.id === id);
                    return confederacao ? confederacao.nome : '#' + id;
                },
            }"
            class="space-y-4"
        >
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Usuários</h2>
                    <p class="text-sm text-slate-500">Liste, busque e edite usuários rapidamente.</p>
                </div>
                <div class="flex items-center gap-3">
                    <form id="users-filter-form" method="GET" action="{{ route('admin.users.index') }}" class="flex gap-2">
                        <input
                            type="search"
                            name="q"
                            value="{{ $search }}"
                            placeholder="Buscar nome ou email"
                            class="h-10 w-60 rounded-xl border border-slate-200 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                        <template x-for="confId in selectedConfederacoes" :key="'hidden-' + confId">
                            <input type="hidden" name="confederacoes[]" :value="confId">
                        </template>
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-300"
                        >
                            Buscar
                        </button>
                        <button
                            type="button"
                            @click="openFilters = !openFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-700 transition hover:border-slate-300 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-300"
                        >
                            Filtro
                            <span
                                x-cloak
                                x-show="selectedConfederacoes.length > 0"
                                class="inline-flex min-w-5 items-center justify-center rounded-full bg-blue-600 px-1.5 py-0.5 text-[10px] font-bold text-white"
                                x-text="selectedConfederacoes.length"
                            ></span>
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

            <div
                x-cloak
                x-show="openFilters"
                x-transition.opacity
                class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <select
                        x-model="selectedOption"
                        @change="addSelectedConfederacao()"
                        class="h-10 min-w-56 rounded-xl border border-slate-200 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                        <option value="">Confederação</option>
                        @foreach ($confederacoes as $confederacao)
                            <option value="{{ $confederacao->id }}">{{ $confederacao->nome }}</option>
                        @endforeach
                    </select>
                    <button
                        type="submit"
                        form="users-filter-form"
                        class="inline-flex items-center rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-slate-800"
                    >
                        Aplicar
                    </button>
                    <a
                        href="{{ route('admin.users.index', $search !== '' ? ['q' => $search] : []) }}"
                        class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                    >
                        Limpar filtros
                    </a>
                </div>

                <div class="mt-3">
                    <p
                        x-cloak
                        x-show="selectedConfederacoes.length === 0"
                        class="text-xs text-slate-500"
                    >
                        Nenhuma confederação selecionada.
                    </p>
                    <div class="flex flex-wrap gap-2" x-show="selectedConfederacoes.length > 0">
                        <template x-for="confId in selectedConfederacoes" :key="'pill-' + confId">
                            <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                <span x-text="confederacaoNome(confId)"></span>
                                <button
                                    type="button"
                                    @click="removeSelectedConfederacao(confId)"
                                    class="text-blue-700 transition hover:text-blue-900"
                                    aria-label="Remover filtro"
                                >
                                    &times;
                                </button>
                            </span>
                        </template>
                    </div>
                </div>
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
