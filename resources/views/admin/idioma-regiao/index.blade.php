<x-app-layout title="Idiomas e Regiões">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Idiomas e Regiões</h2>
                <p class="text-sm text-slate-500">Gerencie os registros de idioma e região usados no perfil.</p>
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
                <p class="font-semibold">Corrija o campo destacado e tente novamente.</p>
                <ul class="mt-2 list-disc pl-5 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Idiomas</h3>
                        <p class="text-sm text-slate-500">Lista de idiomas disponíveis.</p>
                    </div>
                    <button
                        type="button"
                        data-open-modal="idioma"
                        class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                    >
                        Criar idioma
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Nome</th>
                                <th class="px-4 py-3 font-semibold">Slug</th>
                                <th class="px-4 py-3 font-semibold">Perfis</th>
                                <th class="px-4 py-3 font-semibold">Criado em</th>
                                <th class="px-4 py-3 font-semibold text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($idiomas as $idioma)
                                @php
                                    $idiomaPayload = [
                                        'id' => $idioma->id,
                                        'nome' => $idioma->nome,
                                    ];
                                @endphp
                                <tr class="transition-colors hover:bg-slate-50">
                                    <td class="px-4 py-4 font-semibold text-slate-900">{{ $idioma->nome }}</td>
                                    <td class="px-4 py-4 text-slate-600">{{ $idioma->slug }}</td>
                                    <td class="px-4 py-4 text-slate-600">{{ $idioma->profiles_count }}</td>
                                    <td class="px-4 py-4 text-slate-600">{{ $idioma->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="px-4 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button
                                                type="button"
                                                data-edit-entity="idioma"
                                                data-entity-id="{{ $idioma->id }}"
                                                data-payload='{!! json_encode($idiomaPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) !!}'
                                                data-update-action="{{ route('admin.idioma-regiao.idiomas.update', $idioma) }}"
                                                class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                                            >
                                                Editar
                                            </button>
                                            <form action="{{ route('admin.idioma-regiao.idiomas.destroy', $idioma) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    onclick="return confirm('Deseja realmente excluir este idioma?')"
                                                    class="inline-flex items-center rounded-xl border border-red-200 px-3 py-1 text-xs font-semibold text-red-700 transition hover:bg-red-50"
                                                >
                                                    Excluir
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">
                                        Nenhum idioma cadastrado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Regiões</h3>
                        <p class="text-sm text-slate-500">Lista de regiões disponíveis.</p>
                    </div>
                    <button
                        type="button"
                        data-open-modal="regiao"
                        class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                    >
                        Criar região
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Nome</th>
                                <th class="px-4 py-3 font-semibold">Slug</th>
                                <th class="px-4 py-3 font-semibold">Perfis</th>
                                <th class="px-4 py-3 font-semibold">Criado em</th>
                                <th class="px-4 py-3 font-semibold text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($regioes as $regiao)
                                @php
                                    $regiaoPayload = [
                                        'id' => $regiao->id,
                                        'nome' => $regiao->nome,
                                    ];
                                @endphp
                                <tr class="transition-colors hover:bg-slate-50">
                                    <td class="px-4 py-4 font-semibold text-slate-900">{{ $regiao->nome }}</td>
                                    <td class="px-4 py-4 text-slate-600">{{ $regiao->slug }}</td>
                                    <td class="px-4 py-4 text-slate-600">{{ $regiao->profiles_count }}</td>
                                    <td class="px-4 py-4 text-slate-600">{{ $regiao->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="px-4 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button
                                                type="button"
                                                data-edit-entity="regiao"
                                                data-entity-id="{{ $regiao->id }}"
                                                data-payload='{!! json_encode($regiaoPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) !!}'
                                                data-update-action="{{ route('admin.idioma-regiao.regioes.update', $regiao) }}"
                                                class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                                            >
                                                Editar
                                            </button>
                                            <form action="{{ route('admin.idioma-regiao.regioes.destroy', $regiao) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    onclick="return confirm('Deseja realmente excluir esta região?')"
                                                    class="inline-flex items-center rounded-xl border border-red-200 px-3 py-1 text-xs font-semibold text-red-700 transition hover:bg-red-50"
                                                >
                                                    Excluir
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">
                                        Nenhuma região cadastrada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <div
        id="idioma-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="idioma-modal-title"
        data-store-action="{{ route('admin.idioma-regiao.idiomas.store') }}"
        data-update-url-template="{{ route('admin.idioma-regiao.idiomas.update', ['idioma' => 'ENTITY_ID']) }}"
        class="fixed inset-0 z-50 hidden flex items-center justify-center overflow-y-auto bg-black/40 p-4"
    >
        <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between gap-4">
                <h3 id="idioma-modal-title" class="text-lg font-semibold text-slate-900">Criar idioma</h3>
                <button
                    type="button"
                    data-close-modal="idioma"
                    class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500 transition hover:border-slate-300 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-100"
                >
                    Fechar
                </button>
            </div>
            <form id="idioma-form" action="{{ route('admin.idioma-regiao.idiomas.store') }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <input id="idioma-form-method" type="hidden" name="_method" value="POST">
                <input id="idioma-entity-id" type="hidden" name="entity_id" value="">
                <input type="hidden" name="form_context" value="idioma">

                <div>
                    <label for="idioma-nome" class="text-xs font-semibold text-slate-700">Nome do idioma</label>
                    <input
                        id="idioma-nome"
                        name="nome"
                        value="{{ old('form_context') === 'idioma' ? old('nome') : '' }}"
                        maxlength="255"
                        required
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                    @if(old('form_context') === 'idioma')
                        @error('nome')
                            <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="button"
                        data-close-modal="idioma"
                        class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:border-slate-300 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                    >
                        Salvar idioma
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div
        id="regiao-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="regiao-modal-title"
        data-store-action="{{ route('admin.idioma-regiao.regioes.store') }}"
        data-update-url-template="{{ route('admin.idioma-regiao.regioes.update', ['regiao' => 'ENTITY_ID']) }}"
        class="fixed inset-0 z-50 hidden flex items-center justify-center overflow-y-auto bg-black/40 p-4"
    >
        <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between gap-4">
                <h3 id="regiao-modal-title" class="text-lg font-semibold text-slate-900">Criar região</h3>
                <button
                    type="button"
                    data-close-modal="regiao"
                    class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500 transition hover:border-slate-300 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-100"
                >
                    Fechar
                </button>
            </div>
            <form id="regiao-form" action="{{ route('admin.idioma-regiao.regioes.store') }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <input id="regiao-form-method" type="hidden" name="_method" value="POST">
                <input id="regiao-entity-id" type="hidden" name="entity_id" value="">
                <input type="hidden" name="form_context" value="regiao">

                <div>
                    <label for="regiao-nome" class="text-xs font-semibold text-slate-700">Nome da região</label>
                    <input
                        id="regiao-nome"
                        name="nome"
                        value="{{ old('form_context') === 'regiao' ? old('nome') : '' }}"
                        maxlength="255"
                        required
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                    @if(old('form_context') === 'regiao')
                        @error('nome')
                            <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="button"
                        data-close-modal="regiao"
                        class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:border-slate-300 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                    >
                        Salvar região
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            function parsePayload(rawValue) {
                if (!rawValue) {
                    return {};
                }

                try {
                    return JSON.parse(rawValue);
                } catch (error) {
                    return {};
                }
            }

            function setModalVisibility(modal, isVisible) {
                if (!modal) {
                    return;
                }

                if (isVisible) {
                    modal.classList.remove('hidden');
                } else {
                    modal.classList.add('hidden');
                }
            }

            function setupEntityModal(entity, singularLabel) {
                var modal = document.getElementById(entity + '-modal');
                if (!modal) {
                    return null;
                }

                var form = document.getElementById(entity + '-form');
                var methodInput = document.getElementById(entity + '-form-method');
                var entityIdInput = document.getElementById(entity + '-entity-id');
                var nomeInput = document.getElementById(entity + '-nome');
                var title = document.getElementById(entity + '-modal-title');

                if (!form || !methodInput || !entityIdInput || !nomeInput) {
                    return null;
                }

                var storeAction = modal.getAttribute('data-store-action') || '';
                var updateUrlTemplate = modal.getAttribute('data-update-url-template') || '';
                var openButtons = document.querySelectorAll('[data-open-modal="' + entity + '"]');
                var editButtons = document.querySelectorAll('[data-edit-entity="' + entity + '"]');
                var closeButtons = modal.querySelectorAll('[data-close-modal="' + entity + '"]');

                function resolveUpdateAction(id, actionFromButton) {
                    if (actionFromButton) {
                        return actionFromButton;
                    }

                    if (updateUrlTemplate && id !== '') {
                        return updateUrlTemplate.replace('ENTITY_ID', id);
                    }

                    return storeAction;
                }

                function openCreate() {
                    form.reset();
                    form.action = storeAction;
                    methodInput.value = 'POST';
                    entityIdInput.value = '';
                    if (title) {
                        title.textContent = 'Criar ' + singularLabel;
                    }
                    setModalVisibility(modal, true);
                }

                function openEdit(payload, actionFromButton) {
                    var id = '';
                    if (payload && payload.id !== undefined && payload.id !== null) {
                        id = String(payload.id);
                    }

                    form.action = resolveUpdateAction(id, actionFromButton || '');
                    methodInput.value = 'PATCH';
                    entityIdInput.value = id;
                    nomeInput.value = payload && payload.nome ? payload.nome : '';
                    if (title) {
                        title.textContent = 'Editar ' + singularLabel;
                    }
                    setModalVisibility(modal, true);
                }

                function close() {
                    setModalVisibility(modal, false);
                }

                openButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        openCreate();
                    });
                });

                editButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        var payload = parsePayload(button.getAttribute('data-payload'));

                        if (payload.id === undefined || payload.id === null || payload.id === '') {
                            var fallbackId = button.getAttribute('data-entity-id');
                            if (fallbackId) {
                                payload.id = fallbackId;
                            }
                        }

                        openEdit(payload, button.getAttribute('data-update-action') || '');
                    });
                });

                closeButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        close();
                    });
                });

                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        close();
                    }
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                        close();
                    }
                });

                return {
                    openCreate: openCreate,
                    openEdit: openEdit,
                };
            }

            function init() {
                var handlers = {
                    idioma: setupEntityModal('idioma', 'idioma'),
                    regiao: setupEntityModal('regiao', 'região'),
                };

                var shouldReopen = {{ $errors->any() ? 'true' : 'false' }};
                var context = @json(old('form_context', ''));
                var editingId = @json(old('entity_id', ''));
                var oldNome = @json(old('nome', ''));

                if (!shouldReopen || !handlers[context]) {
                    return;
                }

                if (editingId) {
                    var selector = '[data-edit-entity="' + context + '"][data-entity-id="' + String(editingId) + '"]';
                    var editButton = document.querySelector(selector);
                    var payload = { id: editingId, nome: oldNome };

                    if (editButton) {
                        var buttonPayload = parsePayload(editButton.getAttribute('data-payload'));
                        if (buttonPayload && typeof buttonPayload === 'object') {
                            if (buttonPayload.id !== undefined && buttonPayload.id !== null && buttonPayload.id !== '') {
                                payload.id = buttonPayload.id;
                            }
                            if (buttonPayload.nome !== undefined && buttonPayload.nome !== null) {
                                payload.nome = buttonPayload.nome;
                            }
                        }
                    }

                    payload.nome = oldNome;
                    handlers[context].openEdit(payload, editButton ? (editButton.getAttribute('data-update-action') || '') : '');
                    return;
                }

                handlers[context].openCreate();
                var nomeInput = document.getElementById(context + '-nome');
                if (nomeInput) {
                    nomeInput.value = oldNome;
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
    </script>
</x-app-layout>
