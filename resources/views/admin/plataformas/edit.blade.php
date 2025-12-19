<x-app-layout title="Plataformas">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Editar plataforma</h2>
                <p class="text-sm text-slate-500">Atualize o nome conforme necessário.</p>
            </div>
            <a
                href="{{ route('admin.plataformas.index') }}"
                class="text-sm font-semibold text-slate-600 hover:text-slate-900"
            >
                Voltar para a lista
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @include('admin.plataformas._form', [
                'action' => route('admin.plataformas.update', $plataforma),
                'method' => 'PUT',
                'plataforma' => $plataforma,
                'submitLabel' => 'Salvar alterações',
            ])

            @if($plataforma->ligas_count === 0)
                <form
                    action="{{ route('admin.plataformas.destroy', $plataforma) }}"
                    method="POST"
                    class="mt-6 rounded-xl border border-red-100 bg-red-50/40 p-4 text-sm text-red-700"
                >
                    @csrf
                    @method('DELETE')
                    <p class="font-semibold text-red-600">Excluir plataforma</p>
                    <p class="mb-4 text-xs text-red-600/80">A plataforma não pode ser removida se estiver vinculada a ligas.</p>
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
