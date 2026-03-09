@php
    use Illuminate\Support\Str;

    $filters = array_merge([
        'partida_id' => '',
    ], $filters ?? []);
@endphp

<x-app-layout title="Reclamacoes de partidas">
    <x-slot name="header">
        <div class="space-y-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Reclamacoes de partidas</h2>
                <p class="text-sm text-slate-500">Acompanhe os relatos enviados pelos usuarios.</p>
            </div>

            <form method="GET" action="{{ route('admin.partidas-reclamacoes.index') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="partida_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">ID da partida</label>
                    <input
                        id="partida_id"
                        type="number"
                        min="1"
                        name="partida_id"
                        value="{{ $filters['partida_id'] }}"
                        placeholder="Ex: 123"
                        class="mt-1 h-10 w-44 rounded-xl border border-slate-200 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                </div>
                <button
                    type="submit"
                    class="inline-flex h-10 items-center rounded-xl bg-slate-900 px-4 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-slate-800"
                >
                    Filtrar
                </button>
                <a
                    href="{{ route('admin.partidas-reclamacoes.index') }}"
                    class="inline-flex h-10 items-center rounded-xl border border-slate-200 px-4 text-xs font-semibold uppercase tracking-wide text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                >
                    Limpar
                </a>
            </form>
        </div>
    </x-slot>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-5">
            <h3 class="text-lg font-semibold text-slate-900">Lista de reclamacoes</h3>
            <p class="text-sm text-slate-500">Total: {{ $reclamacoes->total() }}</p>
        </div>
        <div class="px-6 py-4">
            @if($reclamacoes->hasPages())
                <div class="mb-4">
                    {{ $reclamacoes->links() }}
                </div>
            @endif
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Partida</th>
                            <th class="px-4 py-3 font-semibold">Liga</th>
                            <th class="px-4 py-3 font-semibold">Usuario</th>
                            <th class="px-4 py-3 font-semibold">Motivo</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Texto</th>
                            <th class="px-4 py-3 font-semibold">Data</th>
                            <th class="px-4 py-3 font-semibold">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($reclamacoes as $reclamacao)
                            @php
                                $partida = $reclamacao->partida;
                                $mandante = $partida?->mandante?->nome ?? 'Mandante';
                                $visitante = $partida?->visitante?->nome ?? 'Visitante';
                            @endphp
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-4 align-top text-slate-700">
                                    <div class="font-semibold">{{ $mandante }} x {{ $visitante }}</div>
                                    <div class="text-xs text-slate-400">ID: {{ $partida?->id ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">
                                    {{ $partida?->liga?->nome ?? '-' }}
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="font-semibold text-slate-700">{{ $reclamacao->user?->name ?? '-' }}</div>
                                    <div class="text-xs text-slate-400">{{ $reclamacao->user?->email ?? '' }}</div>
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">
                                    {{ strtoupper((string) ($reclamacao->motivo ?? '-')) }}
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold uppercase text-slate-700">
                                        {{ strtoupper((string) ($reclamacao->status ?? 'aberta')) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">
                                    {{ Str::limit($reclamacao->descricao ?? '', 140) }}
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">
                                    {{ $reclamacao->created_at?->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">
                                    <div class="flex flex-wrap gap-2">
                                        @if($partida)
                                            <a
                                                href="{{ route('admin.partidas.edit', $partida) }}"
                                                class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                                            >
                                                Editar partida
                                            </a>
                                        @endif
                                        <a
                                            href="{{ route('admin.partidas-reclamacoes.index', ['partida_id' => $partida?->id]) }}"
                                            class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                                        >
                                            Filtrar partida
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-sm text-slate-500">
                                    Nenhuma reclamacao registrada ate o momento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($reclamacoes->hasPages())
                <div class="mt-4">
                    {{ $reclamacoes->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
