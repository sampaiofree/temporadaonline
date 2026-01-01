<x-app-layout title="Ligas">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Editar liga</h2>
                <p class="text-sm text-slate-500">Ajuste os campos permitidos conforme a regra vigente.</p>
            </div>
            <a
                href="{{ route('admin.ligas.index') }}"
                class="text-sm font-semibold text-slate-600 hover:text-slate-900"
            >
                Voltar para a lista
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @include('admin.ligas._form', [
                'action' => route('admin.ligas.update', $liga),
                'method' => 'PUT',
                'liga' => $liga,
                'statusOptions' => $statusOptions,
                'submitLabel' => 'Salvar alterações',
            ])

            @if(! $hasClubes && ! $hasUsers)
                <form
                    action="{{ route('admin.ligas.destroy', $liga) }}"
                    method="POST"
                    class="mt-6 rounded-xl border border-red-100 bg-red-50/40 p-4 text-sm text-red-700"
                >
                    @csrf
                    @method('DELETE')
                    <p class="font-semibold text-red-600">Excluir liga</p>
                    <p class="mb-4 text-xs text-red-600/80">A liga só pode ser removida se estiver vazia.</p>
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-xl border border-red-200 px-3 py-1 text-xs font-semibold text-red-700 transition hover:bg-red-50"
                    >
                        Confirmar exclusão
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
