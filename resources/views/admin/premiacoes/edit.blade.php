<x-app-layout title="Premiações">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Editar premiação</h2>
                <p class="text-sm text-slate-500">Atualize a recompensa de posição.</p>
            </div>
            <a
                href="{{ route('admin.premiacoes.index') }}"
                class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
            >
                Voltar
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <form action="{{ route('admin.premiacoes.update', $premiacao) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')
                @include('admin.premiacoes._form', ['premiacao' => $premiacao])
                <div class="flex justify-end">
                    <button type="submit" class="btn-primary">
                        Atualizar premiação
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
