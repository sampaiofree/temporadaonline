<x-app-layout title="Patrocinios">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Novo patrocinio</h2>
                <p class="text-sm text-slate-500">Cadastre um novo patrocinio para o clube.</p>
            </div>
            <a
                href="{{ route('admin.patrocinios.index') }}"
                class="text-sm font-semibold text-slate-600 hover:text-slate-900"
            >
                Voltar para a lista
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @include('admin.patrocinios._form', [
                'action' => route('admin.patrocinios.store'),
                'method' => 'POST',
                'submitLabel' => 'Criar patrocinio',
            ])
        </div>
    </div>
</x-app-layout>
