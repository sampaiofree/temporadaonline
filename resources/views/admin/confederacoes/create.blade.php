<x-app-layout title="Confederacoes">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Nova confederacao</h2>
                <p class="text-sm text-slate-500">Cadastre nome, descricao e imagem da confederacao.</p>
            </div>
            <a
                href="{{ route('admin.confederacoes.index') }}"
                class="text-sm font-semibold text-slate-600 hover:text-slate-900"
            >
                Voltar para a lista
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @include('admin.confederacoes._form', [
                'action' => route('admin.confederacoes.store'),
                'method' => 'POST',
                'jogos' => $jogos,
                'geracoes' => $geracoes,
                'plataformas' => $plataformas,
                'submitLabel' => 'Criar confederacao',
            ])
        </div>
    </div>
</x-app-layout>
