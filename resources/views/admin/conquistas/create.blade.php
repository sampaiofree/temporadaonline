<x-app-layout title="Conquistas">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Nova conquista</h2>
                <p class="text-sm text-slate-500">Cadastre um novo selo de conquista para o clube.</p>
            </div>
            <a
                href="{{ route('admin.conquistas.index') }}"
                class="text-sm font-semibold text-slate-600 hover:text-slate-900"
            >
                Voltar para a lista
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @include('admin.conquistas._form', [
                'action' => route('admin.conquistas.store'),
                'method' => 'POST',
                'tipos' => $tipos,
                'submitLabel' => 'Criar conquista',
            ])
        </div>
    </div>
</x-app-layout>
