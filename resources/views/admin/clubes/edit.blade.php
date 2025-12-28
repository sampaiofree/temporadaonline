<x-app-layout title="Editar clube">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Editar clube</h2>
                <p class="text-sm text-slate-500">Atualize nome, escudo e saldo financeiro.</p>
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
            @php
                $returnQueryString = $returnQuery ? '?'.$returnQuery : '';
            @endphp
            @include('admin.clubes._form', [
                'action' => route('admin.clubes.update', $clube).$returnQueryString,
                'method' => 'PATCH',
                'clube' => $clube,
                'escudos' => $escudos,
                'selectedEscudoId' => $selectedEscudoId,
                'usedEscudos' => $usedEscudos ?? [],
                'saldoAtual' => $saldoAtual,
                'submitLabel' => 'Salvar alterações',
                'queryString' => $returnQuery ?? '',
            ])
        </div>
    </div>
</x-app-layout>
