<x-app-layout title="Criar usuário">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Criar usuário</h2>
                <p class="text-sm text-slate-500">Informe os dados obrigatórios e opcionalmente os dados de perfil.</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Voltar para a lista</a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @include('admin.users._form', [
                'action' => route('admin.users.store'),
                'method' => 'POST',
                'plataformas' => $plataformas,
                'submitLabel' => 'Criar usuário',
            ])
        </div>
    </div>
</x-app-layout>
