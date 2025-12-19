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
    $currentNome = old('nome', $liga->nome ?? '');
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
