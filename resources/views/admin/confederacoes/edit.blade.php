<x-app-layout title="Confederacoes">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Editar confederacao</h2>
                <p class="text-sm text-slate-500">Atualize nome, descricao e imagem da confederacao.</p>
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
                'action' => route('admin.confederacoes.update', $confederacao),
                'method' => 'PUT',
                'confederacao' => $confederacao,
                'jogos' => $jogos,
                'geracoes' => $geracoes,
                'plataformas' => $plataformas,
                'lockSelections' => $lockSelections ?? false,
                'submitLabel' => 'Salvar alteracoes',
            ])

            @if($confederacao->ligas_count === 0)
                <form
                    action="{{ route('admin.confederacoes.destroy', $confederacao) }}"
                    method="POST"
                    class="mt-6 rounded-xl border border-red-100 bg-red-50/40 p-4 text-sm text-red-700"
                >
                    @csrf
                    @method('DELETE')
                    <p class="font-semibold text-red-600">Excluir confederacao</p>
                    <p class="mb-4 text-xs text-red-600/80">A confederacao nao pode ser removida se estiver associada a ligas.</p>
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-xl border border-red-200 px-3 py-1 text-xs font-semibold text-red-700 transition hover:bg-red-50"
                    >
                        Confirmar exclusao
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
