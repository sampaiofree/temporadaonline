<x-app-layout title="Editar escudo">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Editar escudo</h2>
                <p class="text-sm text-slate-500">Atualize nome, país ou imagem do escudo.</p>
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
            @include('admin.ligas-escudos._form', [
                'action' => route('admin.ligas-escudos.update', $ligaEscudo),
                'method' => 'PATCH',
                'ligaEscudo' => $ligaEscudo,
                'paises' => $paises,
                'submitLabel' => 'Salvar alterações',
            ])
        </div>
    </div>
</x-app-layout>
