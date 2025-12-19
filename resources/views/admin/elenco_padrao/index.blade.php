<x-app-layout title="Importar Elenco Padrão">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Importar Elenco Padrão</h2>
                <p class="text-sm text-slate-500">Faça o upload, mapeie as colunas e confirme a importação.</p>
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

        @if($errors->has('mapping'))
            <div class="rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
                {{ $errors->first('mapping') }}
            </div>
        @endif

        <div class="flex flex-col gap-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div class="text-sm text-slate-500">
                    @if($step === 1)
                        Selecione o jogo e envie o CSV para iniciar o mapeamento.
                    @else
                        Ajuste o mapeamento e confira o preview antes de importar.
                    @endif
                </div>
                <a
                    href="{{ route('admin.elenco-padrao.jogadores') }}"
                    class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                >
                    Ver jogadores
                </a>
            </div>

            @if($step === 1)
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
                    <input type="hidden" name="step" value="upload">

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
                            UTF-8, separador ponto-e-vírgula, header obrigatória. Máximo 20 MB.
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
                            Enviar CSV
                        </button>
                    </div>
                </form>
            @endif

            @if($step === 2)
                <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    Jogo selecionado: <strong>{{ $jogoSelecionado?->nome ?? 'Não definido' }}</strong>
                </div>

                <form action="{{ route('admin.elenco-padrao.importar') }}" method="POST" class="space-y-6">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach($fields as $field)
                            @php
                                $label = $labels[$field] ?? $field;
                                $required = in_array($field, ['long_name', 'player_positions', 'overall'], true);
                            @endphp
                            <div>
                                <label class="block text-sm font-semibold text-slate-700">
                                    {{ $label }} @if($required)<span class="text-rose-500">*</span>@endif
                                </label>
                                <select
                                    name="mapping[{{ $field }}]"
                                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                >
                                    <option value="">Não mapear</option>
                                    @foreach($columns as $column)
                                        <option value="{{ $column }}" @selected(($mapping[$field] ?? '') === $column)>{{ $column }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-700">
                            Preview (5 linhas)
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        @foreach($previewFields as $field)
                                            <th class="px-4 py-3 font-semibold">{{ $labels[$field] ?? $field }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($previewRows as $row)
                                        <tr>
                                            @foreach($previewFields as $field)
                                                <td class="px-4 py-3 text-slate-600">{{ $row[$field] ?? '—' }}</td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ max(count($previewFields), 1) }}" class="px-4 py-6 text-center text-sm text-slate-500">
                                                Nenhum dado disponível para preview.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <button
                            type="submit"
                            name="step"
                            value="cancel"
                            class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-rose-700 transition hover:border-rose-300 hover:text-rose-800"
                        >
                            Cancelar importação
                        </button>
                        <button
                            type="submit"
                            name="step"
                            value="preview"
                            class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                        >
                            Atualizar preview
                        </button>
                        <button
                            type="submit"
                            name="step"
                            value="confirm"
                            class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                        >
                            Confirmar importação
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
