<x-app-layout title="Temporadas">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Temporadas</h2>
                <p class="text-sm text-slate-500">Controle períodos oficiais por confederação e mantenha os registros atualizados.</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
                <p class="font-semibold">Corrija os campos destacados no formulário de temporada.</p>
                <ul class="mt-2 list-disc pl-5 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Lista completa</h3>
                        <p class="text-sm text-slate-500">Crie e edite temporadas vinculadas a cada confederação.</p>
                    </div>
                    <button
                        type="button"
                        data-open-temporada-modal
                        class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                    >
                        Nova temporada
                    </button>
                </div>
            </div>
            <div class="px-6 py-4">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Confederação</th>
                                <th class="px-4 py-3 font-semibold">Nome</th>
                                <th class="px-4 py-3 font-semibold">Período</th>
                                <th class="px-4 py-3 font-semibold">Criado em</th>
                                <th class="px-4 py-3 font-semibold text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($temporadas as $temporada)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-4">{{ $temporada->confederacao?->nome ?? '—' }}</td>
                                    <td class="px-4 py-4 font-semibold text-slate-900">{{ $temporada->name }}</td>
                                    <td class="px-4 py-4 text-slate-600">
                                        {{ $temporada->data_inicio?->format('d/m/Y') ?? '—' }} – {{ $temporada->data_fim?->format('d/m/Y') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-4 text-slate-600">{{ $temporada->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="px-4 py-4 text-right">
                                        @php
                                            $temporadaPayload = [
                                                'id' => $temporada->id,
                                                'confederacao_id' => $temporada->confederacao_id,
                                                'name' => $temporada->name,
                                                'descricao' => $temporada->descricao,
                                                'data_inicio' => optional($temporada->data_inicio)->format('Y-m-d'),
                                                'data_fim' => optional($temporada->data_fim)->format('Y-m-d'),
                                            ];
                                        @endphp
                                        <button
                                            type="button"
                                            data-temporada-edit
                                            data-temporada-id="{{ $temporada->id }}"
                                            data-temporada='{!! json_encode($temporadaPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) !!}'
                                            data-update-action="{{ route('admin.temporadas.update', $temporada) }}"
                                            class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:border-slate-300 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                        >
                                            Editar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">
                                        Nenhuma temporada cadastrada ainda.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($temporadas->hasPages())
                    <div class="mt-4">
                        {{ $temporadas->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div
        id="temporada-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="temporada-modal-title"
        data-store-action="{{ route('admin.temporadas.store') }}"
        data-update-url-template="{{ route('admin.temporadas.update', ['temporada' => 'TEMPORADA_ID']) }}"
        class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/40 p-4"
    >
        <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 id="temporada-modal-title" class="text-lg font-semibold text-slate-900">Nova temporada</h3>
                    <p class="text-sm text-slate-500">Defina o período e vincule a confederação correspondente.</p>
                </div>
                <button
                    type="button"
                    data-close-temporada-modal
                    class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500 transition hover:border-slate-300 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-100"
                >
                    Fechar
                </button>
            </div>
            <form id="temporada-form" action="{{ route('admin.temporadas.store') }}" method="POST" class="mt-6 space-y-4 text-sm">
                @csrf
                <input id="temporada-form-method" type="hidden" name="_method" value="{{ old('_method', 'POST') }}">
                <input id="temporada-form-id" type="hidden" name="temporada_id" value="{{ old('temporada_id', '') }}">

                <div>
                    <label for="temporada-confederacao" class="text-xs font-semibold text-slate-700">Confederação</label>
                    <select
                        id="temporada-confederacao"
                        name="confederacao_id"
                        required
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                        <option value="">Selecione uma confederação</option>
                        @foreach($confederacoes as $confederacao)
                            <option value="{{ $confederacao->id }}" @selected((string) $confederacao->id === (string) old('confederacao_id'))>
                                {{ $confederacao->nome }}
                            </option>
                        @endforeach
                    </select>
                    @error('confederacao_id')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="temporada-name" class="text-xs font-semibold text-slate-700">Nome</label>
                    <input
                        id="temporada-name"
                        name="name"
                        value="{{ old('name', '') }}"
                        maxlength="150"
                        required
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                    @error('name')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="temporada-descricao" class="text-xs font-semibold text-slate-700">Descrição</label>
                    <textarea
                        id="temporada-descricao"
                        name="descricao"
                        rows="3"
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >{{ old('descricao', '') }}</textarea>
                    @error('descricao')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="temporada-data-inicio" class="text-xs font-semibold text-slate-700">Data de início</label>
                        <input
                            id="temporada-data-inicio"
                            type="date"
                            name="data_inicio"
                            value="{{ old('data_inicio', '') }}"
                            required
                            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                        @error('data_inicio')
                            <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="temporada-data-fim" class="text-xs font-semibold text-slate-700">Data de fim</label>
                        <input
                            id="temporada-data-fim"
                            type="date"
                            name="data_fim"
                            value="{{ old('data_fim', '') }}"
                            required
                            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                        @error('data_fim')
                            <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 pt-4">
                    <button
                        type="button"
                        data-close-temporada-modal
                        class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:border-slate-300 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                    >
                        Salvar temporada
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('temporada-modal');
            if (! modal) {
                return;
            }

            const form = document.getElementById('temporada-form');
            const methodInput = document.getElementById('temporada-form-method');
            const idInput = document.getElementById('temporada-form-id');
            const confederacaoSelect = document.getElementById('temporada-confederacao');
            const nameInput = document.getElementById('temporada-name');
            const descricaoInput = document.getElementById('temporada-descricao');
            const inicioInput = document.getElementById('temporada-data-inicio');
            const fimInput = document.getElementById('temporada-data-fim');
            const storeAction = modal.dataset.storeAction;
            const updateUrlTemplate = modal.dataset.updateUrlTemplate;
            const openButtons = document.querySelectorAll('[data-open-temporada-modal]');
            const editButtons = document.querySelectorAll('[data-temporada-edit]');
            const closeButtons = modal.querySelectorAll('[data-close-temporada-modal]');

            const openModal = () => modal.classList.remove('hidden');
            const closeModal = () => modal.classList.add('hidden');

            const resetForm = () => {
                form.reset();
                confederacaoSelect.value = '';
                nameInput.value = '';
                descricaoInput.value = '';
                inicioInput.value = '';
                fimInput.value = '';
            };

            const fillFields = (data) => {
                confederacaoSelect.value = data.confederacao_id ?? '';
                nameInput.value = data.name ?? '';
                descricaoInput.value = data.descricao ?? '';
                inicioInput.value = data.data_inicio ?? '';
                fimInput.value = data.data_fim ?? '';
            };

            const prepareForCreate = () => {
                resetForm();
                methodInput.value = 'POST';
                idInput.value = '';
                form.action = storeAction;
                openModal();
            };

            const prepareForEdit = (data, action) => {
                methodInput.value = 'PATCH';
                idInput.value = data.id ?? '';
                form.action = action ?? storeAction;
                fillFields(data);
                openModal();
            };

            openButtons.forEach((button) => {
                button.addEventListener('click', prepareForCreate);
            });

            editButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    let payload = {};
                    try {
                        payload = button.dataset.temporada ? JSON.parse(button.dataset.temporada) : {};
                    } catch (error) {
                        console.error('Erro ao ler os dados da temporada:', error);
                    }
                    const action = button.dataset.updateAction;
                    prepareForEdit(payload, action);
                });
            });

            closeButtons.forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            const shouldReopen = {{ $errors->any() ? 'true' : 'false' }};
            const editingId = {{ json_encode(old('temporada_id', '')) }};

            if (shouldReopen) {
                let action = storeAction;
                if (editingId) {
                    const match = Array.from(editButtons).find((button) => button.dataset.temporadaId === editingId);
                    if (match?.dataset.updateAction) {
                        action = match.dataset.updateAction;
                    } else if (updateUrlTemplate) {
                        action = updateUrlTemplate.replace('TEMPORADA_ID', editingId);
                    }
                    methodInput.value = 'PATCH';
                    idInput.value = editingId;
                } else {
                    methodInput.value = 'POST';
                    idInput.value = '';
                }
                form.action = action;
                openModal();
            }
        });
    </script>
</x-app-layout>
