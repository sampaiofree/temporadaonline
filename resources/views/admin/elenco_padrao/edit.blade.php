<x-app-layout title="Editar jogador">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Editar jogador</h2>
                <p class="text-sm text-slate-500">Atualize os dados do elenco padrao.</p>
            </div>
            <a
                href="{{ $redirectTo }}"
                class="text-sm font-semibold text-slate-600 hover:text-slate-900"
            >
                Voltar para a lista
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6 flex flex-wrap gap-6 text-sm text-slate-600">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Jogador</p>
                    <p class="mt-1 font-semibold text-slate-800">
                        {{ $player->long_name ?: ($player->short_name ?: 'Sem nome') }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Jogo</p>
                    <p class="mt-1 font-semibold text-slate-800">{{ $player->jogo?->nome ?? '-' }}</p>
                </div>
            </div>

            <form action="{{ route('admin.elenco-padrao.jogadores.update', $player) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">

                <div class="grid gap-4 md:grid-cols-3">
                    @foreach($fields as $field)
                        @php
                            $cast = $casts[$field] ?? null;
                            $textareaFields = ['player_tags', 'player_traits'];
                            $isTextarea = in_array($field, $textareaFields, true);
                            $rawValue = old($field, $player->{$field});
                            $value = $rawValue;
                            if ($cast === 'date' && $rawValue instanceof \Carbon\CarbonInterface) {
                                $value = $rawValue->format('Y-m-d');
                            }
                        @endphp

                        <div class="space-y-2 {{ $isTextarea ? 'md:col-span-3' : '' }}">
                            <label for="field-{{ $field }}" class="block text-sm font-semibold text-slate-700">
                                {{ $labels[$field] ?? $field }}
                            </label>

                            @if($cast === 'boolean')
                                <input type="hidden" name="{{ $field }}" value="0">
                                <label class="inline-flex items-center gap-2">
                                    <input
                                        id="field-{{ $field }}"
                                        name="{{ $field }}"
                                        type="checkbox"
                                        value="1"
                                        class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        @checked(old($field, (bool) $player->{$field}))
                                    >
                                    <span class="text-sm text-slate-600">Sim</span>
                                </label>
                            @elseif($isTextarea)
                                <textarea
                                    id="field-{{ $field }}"
                                    name="{{ $field }}"
                                    rows="3"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                >{{ old($field, $player->{$field}) }}</textarea>
                            @else
                                <input
                                    id="field-{{ $field }}"
                                    name="{{ $field }}"
                                    type="{{ $cast === 'integer' ? 'number' : ($cast === 'date' ? 'date' : 'text') }}"
                                    value="{{ $cast === 'date' ? $value : old($field, $player->{$field}) }}"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                >
                            @endif

                            @error($field)
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-between gap-3 pt-4">
                    <a href="{{ $redirectTo }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">
                        Voltar para listagem
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                    >
                        Salvar alteracoes
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
