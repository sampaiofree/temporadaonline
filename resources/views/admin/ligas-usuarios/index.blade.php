@php
    $filters = array_merge([
        'search' => '',
        'liga_id' => '',
    ], $filters ?? []);
    $filtersActive = collect($filters)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
    $queryString = request()->getQueryString() ?? '';
    $listingQuery = $queryString ? '?'.$queryString : '';
@endphp

<x-app-layout title="Usuários por liga">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Usuários inscritos em ligas</h2>
                <p class="text-sm text-slate-500">Revise associações e remova dados relacionados quando necessário.</p>
            </div>
        </div>
    </x-slot>

    <div
        id="liga-jogadores-filters-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/40 p-4"
        role="dialog"
        aria-modal="true"
    >
        <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Filtros avançados</h3>
                    <p class="text-sm text-slate-500">Filtre por liga.</p>
                </div>
                <button
                    type="button"
                    data-close-liga-jogadores-filters
                    class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500 transition hover:border-slate-300 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-100"
                >
                    Fechar
                </button>
            </div>

            <form
                id="liga-jogadores-filters-form"
                action="{{ route('admin.ligas-usuarios.index') }}"
                method="GET"
                class="mt-6 space-y-4"
            >
                <input type="hidden" name="search" value="{{ $filters['search'] }}">

                <div>
                    <label class="text-sm font-semibold text-slate-700" for="liga-jogadores-filter-liga">Liga</label>
                    <select
                        id="liga-jogadores-filter-liga"
                        name="liga_id"
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                        <option value="">Todas as ligas</option>
                        @foreach ($ligas as $liga)
                            <option value="{{ $liga->id }}" @selected($liga->id === (int) $filters['liga_id'])>
                                {{ $liga->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-4">
                    <a href="{{ route('admin.ligas-usuarios.index') }}" class="text-sm font-semibold text-slate-500 transition hover:text-slate-700">
                        Limpar filtros
                    </a>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            data-close-liga-jogadores-filters
                            class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:border-slate-300 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                        >
                            Aplicar filtros
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Associações de usuário</h3>
                        <p class="text-sm text-slate-500">Confira quem está em cada liga e remova os dados relacionados quando necessário.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <form
                            id="liga-jogadores-search-form"
                            action="{{ route('admin.ligas-usuarios.index') }}"
                            method="GET"
                            class="flex flex-1 min-w-[260px] items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm"
                        >
                            <label class="sr-only" for="liga-jogadores-search-input">Buscar por usuário</label>
                            <input
                                id="liga-jogadores-search-input"
                                type="search"
                                name="search"
                                value="{{ $filters['search'] }}"
                                placeholder="Buscar usuário ou email"
                                class="flex-1 rounded-xl border border-transparent bg-slate-50 px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            >
                            <button
                                type="submit"
                                class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                            >
                                Buscar
                            </button>
                            <input type="hidden" name="liga_id" value="{{ $filters['liga_id'] }}">
                        </form>
                        <button
                            type="button"
                            data-open-liga-jogadores-filters
                            class="inline-flex items-center rounded-xl border px-4 py-2 text-xs font-semibold uppercase tracking-wide transition focus:outline-none focus:ring-2 focus:ring-blue-100 {{ $filtersActive ? 'border-blue-500 text-blue-600 shadow-sm' : 'border-slate-200 text-slate-600' }}"
                        >
                            Filtros avançados
                        </button>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4">
                <div class="mb-4">
                    {{ $ligaJogadores->links() }}
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Usuário</th>
                                <th class="px-4 py-3 font-semibold">Liga</th>
                                <th class="px-4 py-3 font-semibold">Registrado em</th>
                                <th class="px-4 py-3 font-semibold text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($ligaJogadores as $entry)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-slate-900">{{ $entry->user->nickname ?? $entry->user->name }}</div>
                                        <div class="text-xs text-slate-500">ID {{ $entry->user->id }} · {{ $entry->user->email }}</div>
                                    </td>
                                    <td class="px-4 py-4">{{ $entry->liga?->nome ?? '—' }}</td>
                                    <td class="px-4 py-4 text-slate-600">{{ $entry->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-4 text-right">
                                        <form action="{{ route('admin.ligas-usuarios.destroy', $entry) }}{{ $listingQuery }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-xl border border-rose-200 px-3 py-1 text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
                                            >
                                                Excluir
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">
                                        Nenhum usuário registrado em ligas no momento.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $ligaJogadores->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('liga-jogadores-filters-modal');
            if (! modal) {
                return;
            }

            const openButton = document.querySelector('[data-open-liga-jogadores-filters]');
            const closeButtons = modal.querySelectorAll('[data-close-liga-jogadores-filters]');

            const openModal = () => modal.classList.remove('hidden');
            const closeModal = () => modal.classList.add('hidden');

            openButton?.addEventListener('click', openModal);
            closeButtons.forEach((button) => button.addEventListener('click', closeModal));

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        })();
    </script>
</x-app-layout>
