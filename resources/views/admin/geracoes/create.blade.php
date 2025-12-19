<x-app-layout title="Gerações">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Nova geração</h2>
                <p class="text-sm text-slate-500">Defina o nome que será usado dentro do sistema.</p>
            </div>
            <a
                href="{{ route('admin.geracoes.index') }}"
                class="text-sm font-semibold text-slate-600 hover:text-slate-900"
            >
                Voltar para a lista
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @include('admin.geracoes._form', [
                'action' => route('admin.geracoes.store'),
                'method' => 'POST',
                'submitLabel' => 'Criar geração',
            ])
        </div>
    </div>
</x-app-layout>
