<x-app-layout title="Editar partida">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Editar partida #{{ $partida->id }}</h2>
                <p class="text-sm text-slate-500">Atualize os campos operacionais permitidos.</p>
            </div>
            @php
                $returnQueryString = $returnQuery ? '?'.$returnQuery : '';
            @endphp
            <a
                href="{{ route('admin.partidas.index') }}{{ $returnQueryString }}"
                class="text-sm font-semibold text-slate-600 hover:text-slate-900"
            >
                Voltar para a lista
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @include('admin.partidas._form', [
                'action' => route('admin.partidas.update', $partida),
                'method' => 'PUT',
                'partida' => $partida,
                'estadoOptions' => $estadoOptions,
                'woMotivoOptions' => $woMotivoOptions,
                'submitLabel' => 'Salvar alteracoes',
                'queryString' => $returnQuery,
            ])
        </div>
    </div>
</x-app-layout>
