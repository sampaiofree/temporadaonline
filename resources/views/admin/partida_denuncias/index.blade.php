@php
    use Illuminate\Support\Str;
@endphp

<x-app-layout title="Denuncias de partidas">
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Denuncias de partidas</h2>
            <p class="text-sm text-slate-500">Acompanhe os relatos enviados pelos usuarios.</p>
        </div>
    </x-slot>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-5">
            <h3 class="text-lg font-semibold text-slate-900">Lista de denuncias</h3>
            <p class="text-sm text-slate-500">Total: {{ $denuncias->total() }}</p>
        </div>
        <div class="px-6 py-4">
            @if($denuncias->hasPages())
                <div class="mb-4">
                    {{ $denuncias->links() }}
                </div>
            @endif
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Partida</th>
                            <th class="px-4 py-3 font-semibold">Liga</th>
                            <th class="px-4 py-3 font-semibold">Denunciante</th>
                            <th class="px-4 py-3 font-semibold">Texto</th>
                            <th class="px-4 py-3 font-semibold">Data</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($denuncias as $denuncia)
                            @php
                                $partida = $denuncia->partida;
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
                                    <div class="font-semibold text-slate-700">{{ $denuncia->user?->name ?? '-' }}</div>
                                    <div class="text-xs text-slate-400">{{ $denuncia->user?->email ?? '' }}</div>
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">
                                    {{ Str::limit($denuncia->descricao ?? '', 120) }}
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">
                                    {{ $denuncia->created_at?->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">
                                    Nenhuma denuncia registrada ate o momento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($denuncias->hasPages())
                <div class="mt-4">
                    {{ $denuncias->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
