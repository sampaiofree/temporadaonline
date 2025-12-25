<x-app-layout title="Editar escudo de clube">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Editar escudo de clube</h2>
                <p class="text-sm text-slate-500">Altere nome, país, liga ou imagem.</p>
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
            @include('admin.escudos-clubes._form', [
                'action' => route('admin.escudos-clubes.update', $escudoClube),
                'method' => 'PATCH',
                'escudoClube' => $escudoClube,
                'paises' => $paises,
                'ligas' => $ligas,
                'submitLabel' => 'Salvar alterações',
            ])
        </div>
    </div>
</x-app-layout>
