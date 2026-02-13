@php
    use Illuminate\Support\Facades\Storage;
@endphp

@props([
    'action',
    'method' => 'POST',
    'confederacao' => null,
    'jogos',
    'geracoes',
    'submitLabel' => 'Salvar',
    'lockSelections' => false,
])

@php
    $nomeAtual = old('nome', $confederacao->nome ?? '');
    $descricaoAtual = old('descricao', $confederacao->descricao ?? '');
    $currentJogoId = old('jogo_id', $confederacao->jogo_id ?? '');
    $currentGeracaoId = old('geracao_id', $confederacao->geracao_id ?? '');
    $timezoneAtual = old('timezone', $confederacao->timezone ?? 'America/Sao_Paulo');

    $periodos = old('periodos');
    if ($periodos === null) {
        $periodos = $confederacao?->periodos
            ? $confederacao->periodos
                ->sortBy('inicio')
                ->map(fn ($periodo) => [
                    'inicio' => $periodo->inicio?->toDateString(),
                    'fim' => $periodo->fim?->toDateString(),
                ])
                ->values()
                ->toArray()
            : [];
    }
    $periodoKeys = collect($periodos)->keys();
    $nextPeriodoIndex = $periodoKeys->isNotEmpty()
        ? ((int) $periodoKeys->max() + 1)
        : count($periodos);

    $leiloes = old('leiloes');
    if ($leiloes === null) {
        $leiloes = $confederacao?->leiloes
            ? $confederacao->leiloes
                ->sortBy('inicio')
                ->map(fn ($leilao) => [
                    'inicio' => $leilao->inicio?->toDateString(),
                    'fim' => $leilao->fim?->toDateString(),
                ])
                ->values()
                ->toArray()
            : [];
    }
    $leilaoKeys = collect($leiloes)->keys();
    $nextLeilaoIndex = $leilaoKeys->isNotEmpty()
        ? ((int) $leilaoKeys->max() + 1)
        : count($leiloes);
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if (! in_array(strtoupper($method), ['POST'], true))
        @method($method)
    @endif

    <div>
        <label for="nome" class="block text-sm font-semibold text-slate-700">Nome</label>
        <input
            type="text"
            id="nome"
            name="nome"
            value="{{ $nomeAtual }}"
            required
            class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
        >
        @error('nome')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="descricao" class="block text-sm font-semibold text-slate-700">Descrição</label>
        <textarea
            id="descricao"
            name="descricao"
            rows="4"
            class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
        >{{ $descricaoAtual }}</textarea>
        @error('descricao')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="jogo_id" class="block text-sm font-semibold text-slate-700">Jogo</label>
            <select
                id="jogo_id"
                name="jogo_id"
                {!! $lockSelections ? 'disabled' : '' !!}
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
                <option value="">Selecione</option>
                @foreach($jogos as $jogo)
                    <option value="{{ $jogo->id }}" @selected($currentJogoId == $jogo->id)>{{ $jogo->nome }}</option>
                @endforeach
            </select>
            @error('jogo_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="geracao_id" class="block text-sm font-semibold text-slate-700">Geração</label>
            <select
                id="geracao_id"
                name="geracao_id"
                {!! $lockSelections ? 'disabled' : '' !!}
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
                <option value="">Selecione</option>
                @foreach($geracoes as $geracao)
                    <option value="{{ $geracao->id }}" @selected($currentGeracaoId == $geracao->id)>{{ $geracao->nome }}</option>
                @endforeach
            </select>
            @error('geracao_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="timezone" class="block text-sm font-semibold text-slate-700">Timezone</label>
        <input
            type="text"
            id="timezone"
            name="timezone"
            value="{{ $timezoneAtual }}"
            required
            placeholder="America/Sao_Paulo"
            class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
        >
        <p class="mt-1 text-xs text-slate-500">Exemplo: America/Sao_Paulo, Europe/Lisbon.</p>
        @error('timezone')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @if($lockSelections)
        <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            A confederação já possui ligas cadastradas, então Jogo e Geração não podem ser alterados.
        </p>
    @endif

    <div class="space-y-2">
        <label class="block text-sm font-semibold text-slate-700">Imagem</label>
        @if($confederacao?->imagem)
            <div class="mb-2 max-w-xs overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                <img
                    src="{{ Storage::disk('public')->url($confederacao->imagem) }}"
                    alt="{{ $confederacao->nome }}"
                    class="h-32 w-full object-cover"
                >
            </div>
        @endif
        <input
            type="file"
            name="imagem"
            accept="image/*"
            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
        >
        <p class="text-xs text-slate-400">Enviar substitui a imagem atual.</p>
        @error('imagem')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-slate-700">Períodos de partidas</p>
                <p class="text-xs text-slate-500">Todas as ligas desta confederação usarão os mesmos períodos.</p>
            </div>
            <button
                type="button"
                id="confederacao-periodos-add"
                class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
            >
                Adicionar período
            </button>
        </div>

        @error('periodos')
            <p class="mt-3 text-xs text-red-600">{{ $message }}</p>
        @enderror

        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Início</th>
                            <th class="px-4 py-3 font-semibold">Fim</th>
                            <th class="px-4 py-3 font-semibold text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="confederacao-periodos-table-body" data-next-index="{{ $nextPeriodoIndex }}">
                        @foreach ($periodos as $index => $periodo)
                            @php
                                $inicio = $periodo['inicio'] ?? '';
                                $fim = $periodo['fim'] ?? '';
                            @endphp
                            <tr class="border-b border-slate-100 bg-white" data-periodo-row>
                                <td class="px-4 py-3 text-sm text-slate-900">
                                    <div class="font-semibold">{{ $inicio ?: '—' }}</div>
                                    <input type="hidden" name="periodos[{{ $index }}][inicio]" value="{{ $inicio }}">
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-900">
                                    <div class="font-semibold">{{ $fim ?: '—' }}</div>
                                    <input type="hidden" name="periodos[{{ $index }}][fim]" value="{{ $fim }}">
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        type="button"
                                        data-remove-periodo
                                        class="rounded-xl border border-rose-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-rose-600 transition hover:bg-rose-50"
                                    >
                                        Excluir
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        <tr
                            id="confederacao-periodos-empty"
                            class="border-b border-slate-100 bg-white {{ count($periodos) ? 'hidden' : '' }}"
                        >
                            <td colspan="3" class="px-4 py-6 text-center text-sm text-slate-500">
                                Ainda não há períodos cadastrados.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-slate-700">Períodos de leilão</p>
                <p class="text-xs text-slate-500">Todas as ligas desta confederação usarão os mesmos períodos.</p>
            </div>
            <button
                type="button"
                id="confederacao-leiloes-add"
                class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
            >
                Adicionar período de leilão
            </button>
        </div>

        @error('leiloes')
            <p class="mt-3 text-xs text-red-600">{{ $message }}</p>
        @enderror

        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Início</th>
                            <th class="px-4 py-3 font-semibold">Fim</th>
                            <th class="px-4 py-3 font-semibold text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="confederacao-leiloes-table-body" data-next-index="{{ $nextLeilaoIndex }}">
                        @foreach ($leiloes as $index => $leilao)
                            @php
                                $inicio = $leilao['inicio'] ?? '';
                                $fim = $leilao['fim'] ?? '';
                            @endphp
                            <tr class="border-b border-slate-100 bg-white" data-leilao-row>
                                <td class="px-4 py-3 text-sm text-slate-900">
                                    <div class="font-semibold">{{ $inicio ?: '—' }}</div>
                                    <input type="hidden" name="leiloes[{{ $index }}][inicio]" value="{{ $inicio }}">
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-900">
                                    <div class="font-semibold">{{ $fim ?: '—' }}</div>
                                    <input type="hidden" name="leiloes[{{ $index }}][fim]" value="{{ $fim }}">
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        type="button"
                                        data-remove-leilao
                                        class="rounded-xl border border-rose-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-rose-600 transition hover:bg-rose-50"
                                    >
                                        Excluir
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        <tr
                            id="confederacao-leiloes-empty"
                            class="border-b border-slate-100 bg-white {{ count($leiloes) ? 'hidden' : '' }}"
                        >
                            <td colspan="3" class="px-4 py-6 text-center text-sm text-slate-500">
                                Ainda não há períodos de leilão cadastrados.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between gap-3 pt-6">
        <a href="{{ route('admin.confederacoes.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Voltar para a lista</a>
        <button
            type="submit"
            class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
        >
            {{ $submitLabel }}
        </button>
    </div>
</form>

<div
    id="confederacao-periodos-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/40 p-4"
    role="dialog"
    aria-modal="true"
>
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Adicionar período</h3>
                <p class="text-sm text-slate-500">Configure as datas de início e término.</p>
            </div>
            <button
                type="button"
                data-close-periodos-modal
                class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500 transition hover:border-slate-300 hover:text-slate-900"
            >
                Fechar
            </button>
        </div>

        <form id="confederacao-periodos-modal-form" class="mt-6 space-y-4">
            <div>
                <label for="confederacao-periodo-modal-inicio" class="text-sm font-semibold text-slate-700">Data de início</label>
                <input
                    id="confederacao-periodo-modal-inicio"
                    type="date"
                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    required
                >
            </div>
            <div>
                <label for="confederacao-periodo-modal-fim" class="text-sm font-semibold text-slate-700">Data de término</label>
                <input
                    id="confederacao-periodo-modal-fim"
                    type="date"
                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    required
                >
            </div>
            <p id="confederacao-periodos-modal-error" class="hidden text-xs text-rose-600"></p>
            <div class="flex items-center justify-end gap-3 pt-2">
                <button
                    type="button"
                    data-close-periodos-modal
                    class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                >
                    Salvar período
                </button>
            </div>
        </form>
    </div>
</div>

<div
    id="confederacao-leiloes-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/40 p-4"
    role="dialog"
    aria-modal="true"
>
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Adicionar período de leilão</h3>
                <p class="text-sm text-slate-500">Configure as datas de início e término.</p>
            </div>
            <button
                type="button"
                data-close-leiloes-modal
                class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500 transition hover:border-slate-300 hover:text-slate-900"
            >
                Fechar
            </button>
        </div>

        <form id="confederacao-leiloes-modal-form" class="mt-6 space-y-4">
            <div>
                <label for="confederacao-leilao-modal-inicio" class="text-sm font-semibold text-slate-700">Data de início</label>
                <input
                    id="confederacao-leilao-modal-inicio"
                    type="date"
                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    required
                >
            </div>
            <div>
                <label for="confederacao-leilao-modal-fim" class="text-sm font-semibold text-slate-700">Data de término</label>
                <input
                    id="confederacao-leilao-modal-fim"
                    type="date"
                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    required
                >
            </div>
            <p id="confederacao-leiloes-modal-error" class="hidden text-xs text-rose-600"></p>
            <div class="flex items-center justify-end gap-3 pt-2">
                <button
                    type="button"
                    data-close-leiloes-modal
                    class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                >
                    Salvar período
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const initRangeManager = (config) => {
            const addButton = document.getElementById(config.addButtonId);
            const modal = document.getElementById(config.modalId);
            const modalForm = document.getElementById(config.modalFormId);
            const tableBody = document.getElementById(config.tableBodyId);
            const emptyRow = document.getElementById(config.emptyRowId);
            const inicioInput = document.getElementById(config.inicioInputId);
            const fimInput = document.getElementById(config.fimInputId);
            const errorMessage = document.getElementById(config.errorMessageId);
            const closeButtons = document.querySelectorAll(config.closeSelector);

            if (! addButton || ! modal || ! modalForm || ! tableBody || ! inicioInput || ! fimInput) {
                return {
                    rows: () => [],
                };
            }

            let nextIndex = parseInt(tableBody.getAttribute('data-next-index') ?? '0', 10);

            const rowSelector = `[${config.rowAttribute}]`;
            const removeSelector = `[${config.removeAttribute}]`;

            const getRows = () => Array.from(tableBody.querySelectorAll(rowSelector));

            const toggleEmptyState = () => {
                const hasRows = getRows().length > 0;
                if (emptyRow) {
                    emptyRow.classList.toggle('hidden', hasRows);
                }
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modalForm.reset();
                if (errorMessage) {
                    errorMessage.classList.add('hidden');
                    errorMessage.textContent = '';
                }
            };

            const openModal = () => {
                modal.classList.remove('hidden');
                inicioInput.focus();
            };

            const showError = (message) => {
                if (! errorMessage) {
                    return;
                }
                errorMessage.textContent = message;
                errorMessage.classList.remove('hidden');
            };

            const createRow = (inicio, fim) => {
                const row = document.createElement('tr');
                row.className = 'border-b border-slate-100 bg-white';
                row.setAttribute(config.rowAttribute, '');
                row.innerHTML = `
                    <td class="px-4 py-3 text-sm text-slate-900">
                        <div class="font-semibold">${inicio || '—'}</div>
                        <input type="hidden" name="${config.fieldName}[${nextIndex}][inicio]" value="${inicio}">
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-900">
                        <div class="font-semibold">${fim || '—'}</div>
                        <input type="hidden" name="${config.fieldName}[${nextIndex}][fim]" value="${fim}">
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button
                            type="button"
                            ${config.removeAttribute}
                            class="rounded-xl border border-rose-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-rose-600 transition hover:bg-rose-50"
                        >
                            Excluir
                        </button>
                    </td>
                `;

                if (emptyRow && emptyRow.parentNode === tableBody) {
                    tableBody.insertBefore(row, emptyRow);
                } else {
                    tableBody.appendChild(row);
                }

                nextIndex += 1;
                tableBody.setAttribute('data-next-index', String(nextIndex));
                toggleEmptyState();
            };

            const findOverlap = (inicio, fim) => {
                for (const row of getRows()) {
                    const rowInicio = row.querySelector('input[name$="[inicio]"]')?.value;
                    const rowFim = row.querySelector('input[name$="[fim]"]')?.value;
                    if (! rowInicio || ! rowFim) {
                        continue;
                    }
                    if (inicio <= rowFim && fim >= rowInicio) {
                        return { inicio: rowInicio, fim: rowFim };
                    }
                }

                return null;
            };

            addButton.addEventListener('click', openModal);

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            closeButtons.forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            modalForm.addEventListener('submit', (event) => {
                event.preventDefault();

                const inicio = inicioInput.value;
                const fim = fimInput.value;

                if (! inicio || ! fim) {
                    return;
                }

                if (inicio > fim) {
                    showError('A data de início precisa ser anterior ou igual à data de término.');
                    return;
                }

                const overlap = findOverlap(inicio, fim);
                if (overlap) {
                    showError(`Conflito com período ${overlap.inicio} - ${overlap.fim}.`);
                    return;
                }

                if (typeof config.validateExtra === 'function') {
                    const message = config.validateExtra(inicio, fim);
                    if (message) {
                        showError(message);
                        return;
                    }
                }

                createRow(inicio, fim);
                closeModal();
            });

            tableBody.addEventListener('click', (event) => {
                const button = event.target.closest(removeSelector);
                if (! button) {
                    return;
                }

                const row = button.closest(rowSelector);
                if (! row) {
                    return;
                }

                row.remove();
                toggleEmptyState();
            });

            toggleEmptyState();

            return {
                rows: getRows,
            };
        };

        const periodosManager = initRangeManager({
            addButtonId: 'confederacao-periodos-add',
            modalId: 'confederacao-periodos-modal',
            modalFormId: 'confederacao-periodos-modal-form',
            tableBodyId: 'confederacao-periodos-table-body',
            emptyRowId: 'confederacao-periodos-empty',
            inicioInputId: 'confederacao-periodo-modal-inicio',
            fimInputId: 'confederacao-periodo-modal-fim',
            errorMessageId: 'confederacao-periodos-modal-error',
            closeSelector: '[data-close-periodos-modal]',
            fieldName: 'periodos',
            rowAttribute: 'data-periodo-row',
            removeAttribute: 'data-remove-periodo',
        });

        initRangeManager({
            addButtonId: 'confederacao-leiloes-add',
            modalId: 'confederacao-leiloes-modal',
            modalFormId: 'confederacao-leiloes-modal-form',
            tableBodyId: 'confederacao-leiloes-table-body',
            emptyRowId: 'confederacao-leiloes-empty',
            inicioInputId: 'confederacao-leilao-modal-inicio',
            fimInputId: 'confederacao-leilao-modal-fim',
            errorMessageId: 'confederacao-leiloes-modal-error',
            closeSelector: '[data-close-leiloes-modal]',
            fieldName: 'leiloes',
            rowAttribute: 'data-leilao-row',
            removeAttribute: 'data-remove-leilao',
            validateExtra: (inicio, fim) => {
                for (const row of periodosManager.rows()) {
                    const pInicio = row.querySelector('input[name$="[inicio]"]')?.value;
                    const pFim = row.querySelector('input[name$="[fim]"]')?.value;
                    if (! pInicio || ! pFim) {
                        continue;
                    }
                    if (inicio <= pFim && fim >= pInicio) {
                        return `Conflito com período de partidas ${pInicio} - ${pFim}.`;
                    }
                }

                return null;
            },
        });
    });
</script>
