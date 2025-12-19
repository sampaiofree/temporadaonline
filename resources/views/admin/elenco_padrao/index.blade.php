<x-app-layout title="Importar Elenco Padrão">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Importar Elenco Padrão</h2>
                <p class="text-sm text-slate-500">Selecione o jogo, envie um CSV e atualize o elenco padronizado.</p>
            </div>
            <div class="text-right text-xs uppercase tracking-wide text-slate-400">CSV → UpdateOrCreate</div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="flex flex-col gap-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div class="text-sm text-slate-500">
                    Importe CSV com nome;posicao;overall;clube e mantenha o elenco atualizado.
                </div>
                <a
                    href="{{ route('admin.elenco-padrao.jogadores') }}"
                    class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                >
                    Ver jogadores
                </a>
            </div>
            @if($jogos->isEmpty())
                <div class="rounded-lg border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Cadastre um jogo antes de importar o elenco.
                </div>
            @endif

            <form
                action="{{ route('admin.elenco-padrao.importar') }}"
                method="POST"
                enctype="multipart/form-data"
                class="space-y-6"
            >
                @csrf

                <div>
                    <label for="jogo_id" class="block text-sm font-semibold text-slate-700">Jogo</label>
                    <select
                        id="jogo_id"
                        name="jogo_id"
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        {{ $jogos->isEmpty() ? 'disabled' : '' }}
                    >
                        <option value="">Selecione</option>
                        @foreach($jogos as $jogo)
                            <option value="{{ $jogo->id }}" @selected(old('jogo_id') == $jogo->id)>{{ $jogo->nome }}</option>
                        @endforeach
                    </select>
                    @error('jogo_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="csv" class="block text-sm font-semibold text-slate-700">Arquivo CSV</label>
                    <input
                        id="csv"
                        name="csv"
                        type="file"
                        accept=".csv"
                        class="mt-2 w-full rounded-xl border border-dashed border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        {{ $jogos->isEmpty() ? 'disabled' : '' }}
                    >
                    <p class="mt-1 text-xs text-slate-500">
                        UTF-8, separador ponto-e-vírgula, header obrigatória (nome;posicao;overall;clube). Máximo 20 MB.
                    </p>
                    @error('csv')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                        {{ $jogos->isEmpty() ? 'disabled' : '' }}
                    >
                        Importar CSV
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
