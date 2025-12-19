<x-app-layout title="Ligas">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Criar nova liga</h2>
                <p class="text-sm text-slate-500">Defina o nome, jogo, geração, plataforma, teto de clubes e status.</p>
            </div>
            <a
                href="{{ route('admin.ligas.index') }}"
                class="text-sm font-semibold text-slate-600 hover:text-slate-900"
            >
                Voltar para a lista
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @include('admin.ligas._form', [
                'action' => route('admin.ligas.store'),
                'method' => 'POST',
                'jogos' => $jogos,
                'geracoes' => $geracoes,
                'plataformas' => $plataformas,
                'statusOptions' => $statusOptions,
                'submitLabel' => 'Criar liga',
            ])
        </div>
    </div>
</x-app-layout>
