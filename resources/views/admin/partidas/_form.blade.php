@props([
    'action',
    'method' => 'PUT',
    'partida',
    'estadoOptions' => [],
    'woMotivoOptions' => [],
    'submitLabel' => 'Salvar alteracoes',
    'queryString' => '',
])

@php
    $querySuffix = trim((string) $queryString) !== '' ? '?'.trim((string) $queryString) : '';

    $currentEstado = old('estado', $partida->estado);
    $currentPlacarMandante = old('placar_mandante', $partida->placar_mandante);
    $currentPlacarVisitante = old('placar_visitante', $partida->placar_visitante);
    $currentWoParaUserId = old('wo_para_user_id', $partida->wo_para_user_id);
    $currentWoMotivo = old('wo_motivo', $partida->wo_motivo);

    $userOptions = collect([
        [
            'id' => $partida->mandante?->user_id,
            'label' => $partida->mandante?->nome ? ($partida->mandante->nome.' (mandante)') : null,
        ],
        [
            'id' => $partida->visitante?->user_id,
            'label' => $partida->visitante?->nome ? ($partida->visitante->nome.' (visitante)') : null,
        ],
    ])
        ->filter(fn (array $item): bool => ! empty($item['id']) && ! empty($item['label']))
        ->unique('id')
        ->values();

    $currentWoOptionExists = $userOptions->contains(fn (array $item): bool => (int) $item['id'] === (int) $currentWoParaUserId);

    if (! $currentWoOptionExists && ! empty($currentWoParaUserId)) {
        $userOptions->push([
            'id' => (int) $currentWoParaUserId,
            'label' => 'Usuario #'.(int) $currentWoParaUserId,
        ]);
    }
@endphp

<form action="{{ $action }}{{ $querySuffix }}" method="POST" class="space-y-6">
    @csrf
    @if (! in_array(strtoupper($method), ['POST'], true))
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="estado" class="block text-sm font-semibold text-slate-700">Estado</label>
            <select
                id="estado"
                name="estado"
                required
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
                @foreach($estadoOptions as $value => $label)
                    <option value="{{ $value }}" @selected($currentEstado === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('estado')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="wo_motivo" class="block text-sm font-semibold text-slate-700">Motivo do W.O.</label>
            <select
                id="wo_motivo"
                name="wo_motivo"
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
                <option value="">Sem motivo</option>
                @foreach($woMotivoOptions as $value => $label)
                    <option value="{{ $value }}" @selected($currentWoMotivo === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('wo_motivo')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-3">
        <div>
            <label for="placar_mandante" class="block text-sm font-semibold text-slate-700">Placar mandante</label>
            <input
                type="number"
                id="placar_mandante"
                name="placar_mandante"
                min="0"
                value="{{ $currentPlacarMandante }}"
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
            <p class="mt-1 text-xs text-slate-500">Deixe vazio para limpar.</p>
            @error('placar_mandante')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="placar_visitante" class="block text-sm font-semibold text-slate-700">Placar visitante</label>
            <input
                type="number"
                id="placar_visitante"
                name="placar_visitante"
                min="0"
                value="{{ $currentPlacarVisitante }}"
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
            <p class="mt-1 text-xs text-slate-500">Deixe vazio para limpar.</p>
            @error('placar_visitante')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="wo_para_user_id" class="block text-sm font-semibold text-slate-700">W.O. para usuario</label>
            <select
                id="wo_para_user_id"
                name="wo_para_user_id"
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
                <option value="">Sem vencedor de W.O.</option>
                @foreach($userOptions as $option)
                    <option value="{{ $option['id'] }}" @selected((string) $currentWoParaUserId === (string) $option['id'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
            @error('wo_para_user_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <p class="text-sm font-semibold text-slate-700">Metadados (somente leitura)</p>
        <div class="mt-3 grid gap-3 md:grid-cols-2 lg:grid-cols-3 text-sm text-slate-600">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Liga</p>
                <p>{{ $partida->liga?->nome ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Confronto</p>
                <p>{{ $partida->mandante?->nome ?? 'Mandante' }} x {{ $partida->visitante?->nome ?? 'Visitante' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Agendada em</p>
                <p>{{ $partida->scheduled_at?->format('d/m/Y H:i') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Check-in mandante</p>
                <p>{{ $partida->checkin_mandante_at?->format('d/m/Y H:i') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Check-in visitante</p>
                <p>{{ $partida->checkin_visitante_at?->format('d/m/Y H:i') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Placar registrado em</p>
                <p>{{ $partida->placar_registrado_em?->format('d/m/Y H:i') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Placar registrado por</p>
                <p>{{ $partida->placarRegistradoPorUser?->name ?? ($partida->placar_registrado_por ? ('User #'.$partida->placar_registrado_por) : '-') }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Criada em</p>
                <p>{{ $partida->created_at?->format('d/m/Y H:i') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Atualizada em</p>
                <p>{{ $partida->updated_at?->format('d/m/Y H:i') ?? '-' }}</p>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between gap-3 pt-2">
        <a href="{{ route('admin.partidas.index') }}{{ $querySuffix }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Voltar para listagem</a>
        <button
            type="submit"
            class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
        >
            {{ $submitLabel }}
        </button>
    </div>
</form>
