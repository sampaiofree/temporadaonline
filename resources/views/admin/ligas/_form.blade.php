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
            <label for="max_times" class="block text-sm font-semibold text-slate-700">Quantidade maxima de clubes</label>
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
        <p class="mt-1 text-xs text-slate-500">Aceita JPG, PNG e WEBP de ate 2 MB.</p>
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
    });
</script>
