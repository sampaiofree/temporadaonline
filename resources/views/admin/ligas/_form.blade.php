@props([
    'action',
    'method' => 'POST',
    'liga' => null,
    'confederacoes' => [],
    'statusOptions',
    'submitLabel' => 'Salvar liga',
    'whatsappGroups' => [],
])

@php
    $currentStatus = old('status', $liga->status ?? array_key_first($statusOptions));
    $currentConfederacaoId = old('confederacao_id', $liga->confederacao_id ?? '');
    $currentMax = old('max_times', $liga->max_times ?? 20);
    $currentSaldoInicial = old('saldo_inicial', $liga->saldo_inicial ?? 0);
    $currentUsuarioPontuacao = old('usuario_pontuacao', $liga->usuario_pontuacao ?? '');
    $currentWhatsappLink = old('whatsapp_grupo_link', $liga->whatsapp_grupo_link ?? '');
    $currentWhatsappGroupJid = old('whatsapp_grupo_jid', $liga->whatsapp_grupo_jid ?? '');
    $currentDescricao = old('descricao', $liga->descricao ?? '');
    $currentRegras = old('regras', $liga->regras ?? '');
    $currentNome = old('nome', $liga->nome ?? '');
    $isEditing = (bool) $liga;
    $selectedConfederacao = collect($confederacoes)->firstWhere('id', (int) $currentConfederacaoId);
    $selectedJogo = $selectedConfederacao?->jogo?->nome;
    $selectedGeracao = $selectedConfederacao?->geracao?->nome;
    $selectedPlataforma = $selectedConfederacao?->plataforma?->nome;
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
    $leiloes = old('leiloes');
    if ($leiloes === null) {
        $leiloes = $liga?->leiloes
            ? $liga->leiloes->map(fn ($leilao) => [
                'inicio' => $leilao->inicio?->toDateString(),
                'fim' => $leilao->fim?->toDateString(),
            ])->toArray()
            : [];
    }
    $leilaoKeys = collect($leiloes)->keys();
    $nextLeilaoIndex = $leilaoKeys->isNotEmpty()
        ? ((int) $leilaoKeys->max() + 1)
        : count($leiloes);
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

    <div>
        <label for="confederacao_id" class="block text-sm font-semibold text-slate-700">Confederacao</label>
        @if($isEditing)
            <p class="mt-2 text-sm font-semibold text-slate-900">
                {{ $liga->confederacao?->nome ?? '-' }}
            </p>
        @else
            <select
                id="confederacao_id"
                name="confederacao_id"
                required
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
                <option value="">Selecione</option>
                @foreach($confederacoes as $confederacao)
                    <option
                        value="{{ $confederacao->id }}"
                        data-jogo="{{ $confederacao->jogo?->nome ?? '' }}"
                        data-geracao="{{ $confederacao->geracao?->nome ?? '' }}"
                        data-plataforma="{{ $confederacao->plataforma?->nome ?? '' }}"
                        @selected($currentConfederacaoId == $confederacao->id)
                    >
                        {{ $confederacao->nome }}
                    </option>
                @endforeach
            </select>
        @endif
        @error('confederacao_id')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
        <p class="text-sm font-semibold text-slate-700">Dados herdados da confederacao</p>
        <div class="mt-2 grid gap-2 md:grid-cols-3">
            <div>
                <span class="text-xs uppercase tracking-wide text-slate-400">Jogo</span>
                <p id="confederacao-jogo" class="text-sm font-semibold text-slate-800">
                    {{ $isEditing ? ($liga->confederacao?->jogo?->nome ?? $liga->jogo?->nome ?? '-') : ($selectedJogo ?: '-') }}
                </p>
            </div>
            <div>
                <span class="text-xs uppercase tracking-wide text-slate-400">Geracao</span>
                <p id="confederacao-geracao" class="text-sm font-semibold text-slate-800">
                    {{ $isEditing ? ($liga->confederacao?->geracao?->nome ?? $liga->geracao?->nome ?? '-') : ($selectedGeracao ?: '-') }}
                </p>
            </div>
            <div>
                <span class="text-xs uppercase tracking-wide text-slate-400">Plataforma</span>
                <p id="confederacao-plataforma" class="text-sm font-semibold text-slate-800">
                    {{ $isEditing ? ($liga->confederacao?->plataforma?->nome ?? $liga->plataforma?->nome ?? '-') : ($selectedPlataforma ?: '-') }}
                </p>
            </div>
        </div>
    </div>


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

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="usuario_pontuacao" class="block text-sm font-semibold text-slate-700">Pontuacao do usuario (0 a 5)</label>
            <input
                type="number"
                id="usuario_pontuacao"
                name="usuario_pontuacao"
                min="0"
                max="5"
                step="0.1"
                value="{{ $currentUsuarioPontuacao }}"
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
            @error('usuario_pontuacao')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="whatsapp_grupo_select" class="block text-sm font-semibold text-slate-700">Grupo WhatsApp</label>
            <select
                id="whatsapp_grupo_select"
                name="whatsapp_grupo_jid"
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
                <option value="">Selecione um grupo</option>
                @foreach($whatsappGroups as $group)
                    @php
                        $groupId = is_array($group) ? ($group['id'] ?? '') : '';
                        $groupName = is_array($group) ? ($group['subject'] ?? $groupId) : '';
                        $groupLabel = $groupName ?: $groupId;
                    @endphp
                    @if($groupId)
                        <option value="{{ $groupId }}" @selected($currentWhatsappGroupJid === $groupId)>
                            {{ $groupLabel }} ({{ $groupId }})
                        </option>
                    @endif
                @endforeach
            </select>
            @if(empty($whatsappGroups))
                <p class="mt-1 text-xs text-amber-600">Nenhum grupo carregado. Confirme a conexao em Admin &gt; WhatsApp.</p>
            @endif
            @error('whatsapp_grupo_jid')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <span class="block text-sm font-semibold text-slate-700">JID do grupo WhatsApp</span>
            <p class="mt-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                {{ $currentWhatsappGroupJid ?: '-' }}
            </p>
            <p class="mt-1 text-xs text-slate-500">Preenchido automaticamente apos salvar.</p>
        </div>
        <div>
            <span class="block text-sm font-semibold text-slate-700">Link do grupo WhatsApp</span>
            <div class="mt-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                @if($currentWhatsappLink)
                    <a href="{{ $currentWhatsappLink }}" class="text-blue-600 hover:text-blue-700" target="_blank" rel="noopener noreferrer">
                        {{ $currentWhatsappLink }}
                    </a>
                @else
                    -
                @endif
            </div>
            <p class="mt-1 text-xs text-slate-500">Preenchido automaticamente apos salvar.</p>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="descricao" class="block text-sm font-semibold text-slate-700">Descricao</label>
            <textarea
                id="descricao"
                name="descricao"
                rows="5"
                maxlength="2000"
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >{{ $currentDescricao }}</textarea>
            @error('descricao')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="regras" class="block text-sm font-semibold text-slate-700">Regras</label>
            <textarea
                id="regras"
                name="regras"
                rows="5"
                maxlength="2000"
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >{{ $currentRegras }}</textarea>
            @error('regras')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
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

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-slate-700">Períodos de leilão</p>
                <p class="text-xs text-slate-500">Defina janelas para leilões relacionados à liga.</p>
            </div>
            <button
                type="button"
                id="liga-leiloes-add"
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
                    <tbody id="liga-leiloes-table-body" data-next-index="{{ $nextLeilaoIndex }}">
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
                            id="liga-leiloes-empty"
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

<div
    id="liga-leiloes-modal"
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

        <form id="liga-leiloes-modal-form" class="mt-6 space-y-4">
            <div>
                <label for="liga-leilao-modal-inicio" class="text-sm font-semibold text-slate-700">Data de início</label>
                <input
                    id="liga-leilao-modal-inicio"
                    type="date"
                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    required
                >
            </div>
            <div>
                <label for="liga-leilao-modal-fim" class="text-sm font-semibold text-slate-700">Data de término</label>
                <input
                    id="liga-leilao-modal-fim"
                    type="date"
                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    required
                >
            </div>
            <p id="liga-leiloes-modal-error" class="text-xs text-rose-600 hidden"></p>
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
        const confederacaoSelect = document.getElementById('confederacao_id');
        const confederacaoJogo = document.getElementById('confederacao-jogo');
        const confederacaoGeracao = document.getElementById('confederacao-geracao');
        const confederacaoPlataforma = document.getElementById('confederacao-plataforma');

        const updateConfederacaoInfo = () => {
            if (! confederacaoSelect || ! confederacaoJogo || ! confederacaoGeracao || ! confederacaoPlataforma) {
                return;
            }

            const option = confederacaoSelect.selectedOptions[0];
            if (! option) {
                return;
            }

            confederacaoJogo.textContent = option.dataset.jogo || '-';
            confederacaoGeracao.textContent = option.dataset.geracao || '-';
            confederacaoPlataforma.textContent = option.dataset.plataforma || '-';
        };

        if (confederacaoSelect) {
            updateConfederacaoInfo();
            confederacaoSelect.addEventListener('change', updateConfederacaoInfo);
        }

        const periodoAddButton = document.getElementById('liga-periodos-add');
        const periodoModal = document.getElementById('liga-periodos-modal');
        const periodoModalForm = document.getElementById('liga-periodos-modal-form');
        const periodoTableBody = document.getElementById('liga-periodos-table-body');
        const periodoEmptyRow = document.getElementById('liga-periodos-empty');
        const periodoInicioInput = document.getElementById('liga-periodo-modal-inicio');
        const periodoFimInput = document.getElementById('liga-periodo-modal-fim');
        const periodoErrorMessage = document.getElementById('liga-periodos-modal-error');

        if (periodoAddButton && periodoModal && periodoModalForm && periodoTableBody && periodoInicioInput && periodoFimInput) {
            let nextIndex = parseInt(periodoTableBody.getAttribute('data-next-index') ?? '0', 10);

            const toggleEmptyState = () => {
                const hasRows = periodoTableBody.querySelectorAll('[data-periodo-row]').length > 0;
                if (periodoEmptyRow) {
                    periodoEmptyRow.classList.toggle('hidden', hasRows);
                }
            };

            const closeModal = () => {
                periodoModal.classList.add('hidden');
                periodoModalForm.reset();
                if (periodoErrorMessage) {
                    periodoErrorMessage.classList.add('hidden');
                    periodoErrorMessage.textContent = '';
                }
            };

            const openModal = () => {
                periodoModal.classList.remove('hidden');
                periodoInicioInput.focus();
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

                if (periodoEmptyRow && periodoEmptyRow.parentNode === periodoTableBody) {
                    periodoTableBody.insertBefore(row, periodoEmptyRow);
                } else {
                    periodoTableBody.appendChild(row);
                }

                nextIndex += 1;
                periodoTableBody.setAttribute('data-next-index', String(nextIndex));
                toggleEmptyState();
            };

            periodoAddButton.addEventListener('click', openModal);

            periodoModal.addEventListener('click', (event) => {
                if (event.target === periodoModal) {
                    closeModal();
                }
            });

            document.querySelectorAll('[data-close-periodos-modal]').forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            periodoModalForm.addEventListener('submit', (event) => {
                event.preventDefault();
                const inicio = periodoInicioInput.value;
                const fim = periodoFimInput.value;

                if (! inicio || ! fim) {
                    return;
                }

                if (inicio > fim) {
                    if (periodoErrorMessage) {
                        periodoErrorMessage.textContent = 'A data de início precisa ser anterior ou igual à data de término.';
                        periodoErrorMessage.classList.remove('hidden');
                    }
                    return;
                }

                createRow(inicio, fim);
                closeModal();
            });

            periodoTableBody.addEventListener('click', (event) => {
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
        }

        const leilaoAddButton = document.getElementById('liga-leiloes-add');
        const leilaoModal = document.getElementById('liga-leiloes-modal');
        const leilaoModalForm = document.getElementById('liga-leiloes-modal-form');
        const leilaoTableBody = document.getElementById('liga-leiloes-table-body');
        const leilaoEmptyRow = document.getElementById('liga-leiloes-empty');
        const leilaoInicioInput = document.getElementById('liga-leilao-modal-inicio');
        const leilaoFimInput = document.getElementById('liga-leilao-modal-fim');
        const leilaoErrorMessage = document.getElementById('liga-leiloes-modal-error');

        if (leilaoAddButton && leilaoModal && leilaoModalForm && leilaoTableBody && leilaoInicioInput && leilaoFimInput) {
            let nextLeilaoIndex = parseInt(leilaoTableBody.getAttribute('data-next-index') ?? '0', 10);

            const toggleLeilaoEmptyState = () => {
                const hasRows = leilaoTableBody.querySelectorAll('[data-leilao-row]').length > 0;
                if (leilaoEmptyRow) {
                    leilaoEmptyRow.classList.toggle('hidden', hasRows);
                }
            };

            const closeLeilaoModal = () => {
                leilaoModal.classList.add('hidden');
                leilaoModalForm.reset();
                if (leilaoErrorMessage) {
                    leilaoErrorMessage.classList.add('hidden');
                    leilaoErrorMessage.textContent = '';
                }
            };

            const openLeilaoModal = () => {
                leilaoModal.classList.remove('hidden');
                leilaoInicioInput.focus();
            };

            const hasOverlapWithPeriodos = (novoInicio, novoFim) => {
                const periodoInputs = periodoTableBody?.querySelectorAll('[data-periodo-row]') ?? [];
                for (const row of periodoInputs) {
                    const pInicio = row.querySelector('input[name*=\"[inicio]\"]')?.value;
                    const pFim = row.querySelector('input[name*=\"[fim]\"]')?.value;
                    if (! pInicio || ! pFim) continue;
                    if (novoInicio <= pFim && novoFim >= pInicio) {
                        return { pInicio, pFim };
                    }
                }
                return null;
            };

            const createLeilaoRow = (inicio, fim) => {
                const row = document.createElement('tr');
                row.className = 'border-b border-slate-100 bg-white';
                row.setAttribute('data-leilao-row', '');
                const formattedInicio = inicio || '—';
                const formattedFim = fim || '—';

                row.innerHTML = `
                    <td class="px-4 py-3 text-sm text-slate-900">
                        <div class="font-semibold">${formattedInicio}</div>
                        <input type="hidden" name="leiloes[${nextLeilaoIndex}][inicio]" value="${inicio}">
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-900">
                        <div class="font-semibold">${formattedFim}</div>
                        <input type="hidden" name="leiloes[${nextLeilaoIndex}][fim]" value="${fim}">
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
                `;

                if (leilaoEmptyRow && leilaoEmptyRow.parentNode === leilaoTableBody) {
                    leilaoTableBody.insertBefore(row, leilaoEmptyRow);
                } else {
                    leilaoTableBody.appendChild(row);
                }

                nextLeilaoIndex += 1;
                leilaoTableBody.setAttribute('data-next-index', String(nextLeilaoIndex));
                toggleLeilaoEmptyState();
            };

            leilaoAddButton.addEventListener('click', openLeilaoModal);

            leilaoModal.addEventListener('click', (event) => {
                if (event.target === leilaoModal) {
                    closeLeilaoModal();
                }
            });

            document.querySelectorAll('[data-close-leiloes-modal]').forEach((button) => {
                button.addEventListener('click', closeLeilaoModal);
            });

            leilaoModalForm.addEventListener('submit', (event) => {
                event.preventDefault();
                const inicio = leilaoInicioInput.value;
                const fim = leilaoFimInput.value;

                if (! inicio || ! fim) {
                    return;
                }

                if (inicio > fim) {
                    if (leilaoErrorMessage) {
                        leilaoErrorMessage.textContent = 'A data de início precisa ser anterior ou igual à data de término.';
                        leilaoErrorMessage.classList.remove('hidden');
                    }
                    return;
                }

                const overlapPeriodo = hasOverlapWithPeriodos(inicio, fim);
                if (overlapPeriodo) {
                    if (leilaoErrorMessage) {
                        leilaoErrorMessage.textContent = `Conflito com período de partidas ${overlapPeriodo.pInicio} - ${overlapPeriodo.pFim}. Ajuste as datas.`;
                        leilaoErrorMessage.classList.remove('hidden');
                    }
                    return;
                }

                createLeilaoRow(inicio, fim);
                closeLeilaoModal();
            });

            leilaoTableBody.addEventListener('click', (event) => {
                const button = event.target.closest('[data-remove-leilao]');
                if (! button) {
                    return;
                }

                const row = button.closest('[data-leilao-row]');
                if (row) {
                    row.remove();
                    toggleLeilaoEmptyState();
                }
            });

            toggleLeilaoEmptyState();
        }
    });
</script>
