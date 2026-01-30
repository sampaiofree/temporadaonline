<x-app-layout title="Editar imagem de premiação">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Editar imagem de premiação</h2>
                <p class="text-sm text-slate-500">Atualize o nome e o arquivo da imagem.</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @include('admin.imagens._form', [
                'action' => route('admin.premiacoes-imagens.update', $imagem),
                'method' => 'PATCH',
                'model' => $imagem,
                'submitLabel' => 'Salvar alterações',
                'backRoute' => route('admin.premiacoes-imagens.index'),
            ])
        </div>
    </div>
</x-app-layout>
