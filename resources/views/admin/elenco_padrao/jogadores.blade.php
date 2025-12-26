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
        @if(session('success'))
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        @include('admin.elenco_padrao._jogadores_list', [
            'players' => $players,
            'jogos' => $jogos,
            'totalPlayers' => $totalPlayers,
        ])
    </div>
</x-app-layout>
