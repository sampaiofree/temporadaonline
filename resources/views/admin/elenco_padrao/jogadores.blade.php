<x-app-layout title="Jogadores importados">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Jogadores importados</h2>
                <p class="text-sm text-slate-500">Veja todos os jogadores do elenco padrão, com totais por jogo.</p>
            </div>
            <a
                href="{{ route('admin.elenco-padrao.index') }}"
                class="text-sm font-semibold text-slate-600 hover:text-slate-900"
            >
                Voltar para importação
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Total geral</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($totalPlayers) }}</p>
            </div>
            @foreach($jogos as $jogo)
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">{{ $jogo->nome }}</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ $jogo->elenco_padrao_count }}</p>
                </div>
            @endforeach
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Foto</th>
                            <th class="px-4 py-3 font-semibold">Nome</th>
                            <th class="px-4 py-3 font-semibold">Posição</th>
                            <th class="px-4 py-3 font-semibold">Overall</th>
                            <th class="px-4 py-3 font-semibold">Clube</th>
                            <th class="px-4 py-3 font-semibold">Jogo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($players as $player)
                            @php
                                $displayName = $player->long_name ?: ($player->short_name ?: 'Sem nome');
                                $initials = strtoupper(substr($displayName, 0, 2));
                            @endphp
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-4 align-middle">
                                    @if($player->player_face_url)
                                        <img
                                            src="{{ $player->player_face_url }}"
                                            alt="{{ $displayName }}"
                                            class="h-10 w-10 rounded-full object-cover"
                                        >
                                    @else
                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500">
                                            {{ $initials }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 align-middle font-semibold text-slate-900">{{ $displayName }}</td>
                                <td class="px-4 py-4 align-middle text-slate-600">{{ $player->player_positions ?? '—' }}</td>
                                <td class="px-4 py-4 align-middle text-slate-600">{{ $player->overall ?? '—' }}</td>
                                <td class="px-4 py-4 align-middle text-slate-600">{{ $player->club_name ?? '—' }}</td>
                                <td class="px-4 py-4 align-middle text-slate-600">{{ $player->jogo?->nome ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">
                                    Nenhum jogador importado ainda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            {{ $players->links() }}
        </div>
    </div>
</x-app-layout>
