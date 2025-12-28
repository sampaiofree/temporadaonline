@props([
    'action',
    'method' => 'POST',
    'liga' => null,
    'jogos',
    'geracoes',
    'plataformas',
    'statusOptions',
    'submitLabel' => 'Salvar liga',
    'lockSelections' => false,
])

@php
    $currentStatus = old('status', $liga->status ?? array_key_first($statusOptions));
    $currentJogoId = old('jogo_id', $liga->jogo_id ?? '');
    $currentGeracaoId = old('geracao_id', $liga->geracao_id ?? '');
    $currentPlataformaId = old('plataforma_id', $liga->plataforma_id ?? '');
    $currentMax = old('max_times', $liga->max_times ?? 20);
    $currentSaldoInicial = old('saldo_inicial', $liga->saldo_inicial ?? 0);
    $currentNome = old('nome', $liga->nome ?? '');
    $periodos = old('periodos');
    if ($periodos === null) {
        $periodos = $liga?->periodos
            ? $liga->periodos->map(fn ($periodo) => [
                'inicio' => $periodo->inicio?->toDateString(),
                'fim' => $periodo->fim?->toDateString(),
            ])->toArray()
            : [];
    }
    $periodoKeys = collect($periodos)->keys();
    $nextPeriodoIndex = $periodoKeys->isNotEmpty()
        ? ((int) $periodoKeys->max() + 1)
        : count($periodos);
@endphp

<form action="{{ $action }}" method="POST" class="space-y-6" enctype="multipart/form-data">
    @csrf
    @if (! in_array(strtoupper($method), ['POST'], true))
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="nome" class="block text-sm font-semibold text-slate-700">Nome da liga</label>
            <input
                type="text"
                id="nome"
                name="nome"
                value="{{ $currentNome }}"
                required
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
            @error('nome')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="max_times" class="block text-sm font-semibold text-slate-700">Quantidade máxima de clubes</label>
            <input
                type="number"
                id="max_times"
                name="max_times"
                min="1"
                value="{{ $currentMax }}"
                required
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
            @error('max_times')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="saldo_inicial" class="block text-sm font-semibold text-slate-700">Saldo inicial</label>
        <input
            type="number"
            id="saldo_inicial"
            name="saldo_inicial"
            min="0"
            value="{{ $currentSaldoInicial }}"
            required
            class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
        >
        @error('saldo_inicial')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="imagem" class="block text-sm font-semibold text-slate-700">Imagem da liga (opcional)</label>
        <input
            type="file"
            id="imagem"
            name="imagem"
            accept="image/*"
            class="mt-2 w-full rounded-xl border border-dashed border-slate-200 bg-white px-3 py-2 text-sm text-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
        >
        <p class="mt-1 text-xs text-slate-500">Aceita JPG, PNG e WEBP de até 2 MB.</p>
        @error('imagem')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-6 md:grid-cols-3">
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

        <div>
            <label for="plataforma_id" class="block text-sm font-semibold text-slate-700">Plataforma</label>
            <select
                id="plataforma_id"
                name="plataforma_id"
                {!! $lockSelections ? 'disabled' : '' !!}
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
                <option value="">Selecione</option>
                @foreach($plataformas as $plataforma)
                    <option value="{{ $plataforma->id }}" @selected($currentPlataformaId == $plataforma->id)>{{ $plataforma->nome }}</option>
                @endforeach
            </select>
            @error('plataforma_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    @if($lockSelections)
        <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            A liga já possui clubes cadastrados, então Jogo, Geração e Plataforma não podem ser alterados.
        </p>
    @endif

    <div>
        <label for="status" class="block text-sm font-semibold text-slate-700">Status</label>
        <select
            id="status"
            name="status"
            class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
        >
            @foreach($statusOptions as $value => $label)
                <option value="{{ $value }}" @selected($currentStatus === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-slate-700">Períodos de partidas</p>
                <p class="text-xs text-slate-500">Somente datas dentro desses períodos poderão receber partidas.</p>
            </div>
            <button
                type="button"
                id="liga-periodos-add"
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
            <tbody id="liga-periodos-table-body" data-next-index="{{ $nextPeriodoIndex }}">
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
                            id="liga-periodos-empty"
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

    <div class="flex items-center justify-between gap-3 pt-6">
        <a href="{{ route('admin.ligas.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Voltar para listagem</a>
        <button
            type="submit"
            class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
        >
            {{ $submitLabel }}
        </button>
    </div>
</form>

<div
    id="liga-periodos-modal"
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

        <form id="liga-periodos-modal-form" class="mt-6 space-y-4">
            <div>
                <label for="liga-periodo-modal-inicio" class="text-sm font-semibold text-slate-700">Data de início</label>
                <input
                    id="liga-periodo-modal-inicio"
                    type="date"
                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    required
                >
            </div>
            <div>
                <label for="liga-periodo-modal-fim" class="text-sm font-semibold text-slate-700">Data de término</label>
                <input
                    id="liga-periodo-modal-fim"
                    type="date"
                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    required
                >
            </div>
            <p id="liga-periodos-modal-error" class="text-xs text-rose-600 hidden"></p>
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

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const addButton = document.getElementById('liga-periodos-add');
        const modal = document.getElementById('liga-periodos-modal');
        const modalForm = document.getElementById('liga-periodos-modal-form');
        const tableBody = document.getElementById('liga-periodos-table-body');
        const emptyRow = document.getElementById('liga-periodos-empty');
        const inicioInput = document.getElementById('liga-periodo-modal-inicio');
        const fimInput = document.getElementById('liga-periodo-modal-fim');
        const errorMessage = document.getElementById('liga-periodos-modal-error');

        if (! addButton || ! modal || ! modalForm || ! tableBody || ! inicioInput || ! fimInput) {
            return;
        }

        let nextIndex = parseInt(tableBody.getAttribute('data-next-index') ?? '0', 10);

        const toggleEmptyState = () => {
            const hasRows = tableBody.querySelectorAll('[data-periodo-row]').length > 0;
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

        const createRow = (inicio, fim) => {
            const row = document.createElement('tr');
            row.className = 'border-b border-slate-100 bg-white';
            row.setAttribute('data-periodo-row', '');
            const formattedInicio = inicio || '—';
            const formattedFim = fim || '—';

            row.innerHTML = `
                <td class="px-4 py-3 text-sm text-slate-900">
                    <div class="font-semibold">${formattedInicio}</div>
                    <input type="hidden" name="periodos[${nextIndex}][inicio]" value="${inicio}">
                </td>
                <td class="px-4 py-3 text-sm text-slate-900">
                    <div class="font-semibold">${formattedFim}</div>
                    <input type="hidden" name="periodos[${nextIndex}][fim]" value="${fim}">
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

        addButton.addEventListener('click', openModal);

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.querySelectorAll('[data-close-periodos-modal]').forEach((button) => {
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
                if (errorMessage) {
                    errorMessage.textContent = 'A data de início precisa ser anterior ou igual à data de término.';
                    errorMessage.classList.remove('hidden');
                }
                return;
            }

            createRow(inicio, fim);
            closeModal();
        });

        tableBody.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove-periodo]');
            if (! button) {
                return;
            }

            const row = button.closest('[data-periodo-row]');
            if (row) {
                row.remove();
                toggleEmptyState();
            }
        });

        toggleEmptyState();
    });
</script>
