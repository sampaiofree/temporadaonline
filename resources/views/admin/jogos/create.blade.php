<x-app-layout title="Jogos">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Novo jogo</h2>
                <p class="text-sm text-slate-500">Cadastre o nome que será usado em ligas.</p>
            </div>
            <a
                href="{{ route('admin.jogos.index') }}"
                class="text-sm font-semibold text-slate-600 hover:text-slate-900"
            >
                Voltar para a lista
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @include('admin.jogos._form', [
                'action' => route('admin.jogos.store'),
                'method' => 'POST',
                'submitLabel' => 'Criar jogo',
            ])
        </div>
    </div>
</x-app-layout>
