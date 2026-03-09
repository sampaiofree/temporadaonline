@php
    $filters = array_merge([
        'q' => '',
        'estado' => '',
        'liga_id' => '',
        'clube_id' => '',
        'data_inicio' => '',
        'data_fim' => '',
    ], $filters ?? []);

    $queryString = request()->getQueryString() ?? '';
    $listingSuffix = $queryString ? '?'.$queryString : '';
@endphp

<x-app-layout title="Partidas">
    <x-slot name="header">
        <div class="space-y-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Partidas</h2>
                <p class="text-sm text-slate-500">Visualize e edite os dados operacionais das partidas.</p>
            </div>

            <form method="GET" action="{{ route('admin.partidas.index') }}" class="grid gap-3 md:grid-cols-3 lg:grid-cols-6">
                <div>
                    <label for="q" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">ID</label>
                    <input
                        id="q"
                        type="search"
                        name="q"
                        value="{{ $filters['q'] }}"
                        placeholder="Ex: 120"
                        class="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                </div>

                <div>
                    <label for="estado" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Estado</label>
                    <select
                        id="estado"
                        name="estado"
                        class="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                        <option value="">Todos</option>
                        @foreach($estadoOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['estado'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="liga_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Liga</label>
                    <select
                        id="liga_id"
                        name="liga_id"
                        class="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                        <option value="">Todas</option>
                        @foreach($ligas as $liga)
                            <option value="{{ $liga->id }}" @selected((string) $liga->id === (string) $filters['liga_id'])>{{ $liga->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="clube_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Clube</label>
                    <select
                        id="clube_id"
                        name="clube_id"
                        class="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                        <option value="">Todos</option>
                        @foreach($clubes as $clube)
                            <option value="{{ $clube->id }}" @selected((string) $clube->id === (string) $filters['clube_id'])>
                                {{ $clube->nome }}{{ $clube->liga?->nome ? ' ('.$clube->liga->nome.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="data_inicio" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Data inicio</label>
                    <input
                        id="data_inicio"
                        type="date"
                        name="data_inicio"
                        value="{{ $filters['data_inicio'] }}"
                        class="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                </div>

                <div>
                    <label for="data_fim" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Data fim</label>
                    <input
                        id="data_fim"
                        type="date"
                        name="data_fim"
                        value="{{ $filters['data_fim'] }}"
                        class="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                </div>

                <div class="md:col-span-3 lg:col-span-6 flex flex-wrap gap-2 pt-1">
                    <button
                        type="submit"
                        class="inline-flex h-10 items-center rounded-xl bg-slate-900 px-4 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-slate-800"
                    >
                        Filtrar
                    </button>
                    <a
                        href="{{ route('admin.partidas.index') }}"
                        class="inline-flex h-10 items-center rounded-xl border border-slate-200 px-4 text-xs font-semibold uppercase tracking-wide text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                    >
                        Limpar
                    </a>
                </div>
            </form>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-lg font-semibold text-slate-900">Lista de partidas</h3>
                <p class="text-sm text-slate-500">Total: {{ $partidas->total() }}</p>
            </div>
            <div class="px-6 py-4">
                @if($partidas->hasPages())
                    <div class="mb-4">{{ $partidas->links() }}</div>
                @endif

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">ID</th>
                                <th class="px-4 py-3 font-semibold">Estado</th>
                                <th class="px-4 py-3 font-semibold">Liga</th>
                                <th class="px-4 py-3 font-semibold">Confronto</th>
                                <th class="px-4 py-3 font-semibold">Placar</th>
                                <th class="px-4 py-3 font-semibold">W.O.</th>
                                <th class="px-4 py-3 font-semibold">Agendada em</th>
                                <th class="px-4 py-3 font-semibold">Registro placar</th>
                                <th class="px-4 py-3 font-semibold">Criada em</th>
                                <th class="px-4 py-3 font-semibold">Acoes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($partidas as $partida)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-4 align-top text-slate-700 font-semibold">{{ $partida->id }}</td>
                                    <td class="px-4 py-4 align-top text-slate-600">
                                        {{ $estadoOptions[$partida->estado] ?? strtoupper((string) $partida->estado) }}
                                    </td>
                                    <td class="px-4 py-4 align-top text-slate-600">
                                        @if($partida->liga)
                                            <a href="{{ route('admin.ligas.edit', $partida->liga) }}" class="text-blue-600 hover:text-blue-700">
                                                {{ $partida->liga->nome }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 align-top text-slate-600">
                                        <div class="space-y-1">
                                            @if($partida->mandante)
                                                <a href="{{ route('admin.clubes.edit', $partida->mandante) }}" class="block text-blue-600 hover:text-blue-700">
                                                    {{ $partida->mandante->nome }}
                                                </a>
                                            @else
                                                <span class="block">Mandante indefinido</span>
                                            @endif
                                            <span class="block text-xs text-slate-400">x</span>
                                            @if($partida->visitante)
                                                <a href="{{ route('admin.clubes.edit', $partida->visitante) }}" class="block text-blue-600 hover:text-blue-700">
                                                    {{ $partida->visitante->nome }}
                                                </a>
                                            @else
                                                <span class="block">Visitante indefinido</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 align-top text-slate-600">
                                        {{ $partida->placar_mandante ?? '-' }} x {{ $partida->placar_visitante ?? '-' }}
                                    </td>
                                    <td class="px-4 py-4 align-top text-slate-600">
                                        @if($partida->wo_para_user_id)
                                            <div class="font-semibold">{{ $partida->woParaUser?->name ?? ('User #'.$partida->wo_para_user_id) }}</div>
                                            <div class="text-xs text-slate-400">{{ $partida->wo_motivo ?: '-' }}</div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 align-top text-slate-600">
                                        {{ $partida->scheduled_at?->format('d/m/Y H:i') ?? '-' }}
                                    </td>
                                    <td class="px-4 py-4 align-top text-slate-600">
                                        @if($partida->placar_registrado_em)
                                            <div>{{ $partida->placar_registrado_em->format('d/m/Y H:i') }}</div>
                                            <div class="text-xs text-slate-400">{{ $partida->placarRegistradoPorUser?->name ?? ('User #'.$partida->placar_registrado_por) }}</div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 align-top text-slate-600">
                                        {{ $partida->created_at?->format('d/m/Y H:i') ?? '-' }}
                                    </td>
                                    <td class="px-4 py-4 align-top text-slate-600">
                                        <div class="flex flex-wrap gap-2">
                                            <a
                                                href="{{ route('admin.partidas.edit', $partida) }}{{ $listingSuffix }}"
                                                class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                                            >
                                                Editar
                                            </a>
                                            <a
                                                href="{{ route('admin.partidas-reclamacoes.index', ['partida_id' => $partida->id]) }}"
                                                class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                                            >
                                                Reclamacoes ({{ $partida->reclamacoes_count }})
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-4 py-6 text-center text-sm text-slate-500">Nenhuma partida encontrada.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($partidas->hasPages())
                    <div class="mt-4">{{ $partidas->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
