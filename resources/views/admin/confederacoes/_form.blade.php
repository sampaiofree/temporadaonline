@php
    use Illuminate\Support\Facades\Storage;
@endphp

@props([
    'action',
    'method' => 'POST',
    'confederacao' => null,
    'jogos',
    'geracoes',
    'plataformas',
    'submitLabel' => 'Salvar',
    'lockSelections' => false,
])

@php
    $nomeAtual = old('nome', $confederacao->nome ?? '');
    $descricaoAtual = old('descricao', $confederacao->descricao ?? '');
    $currentJogoId = old('jogo_id', $confederacao->jogo_id ?? '');
    $currentGeracaoId = old('geracao_id', $confederacao->geracao_id ?? '');
    $currentPlataformaId = old('plataforma_id', $confederacao->plataforma_id ?? '');
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
        <label for="descricao" class="block text-sm font-semibold text-slate-700">Descricao</label>
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
            <label for="geracao_id" class="block text-sm font-semibold text-slate-700">Geracao</label>
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
            A confederacao ja possui ligas cadastradas, entao Jogo, Geracao e Plataforma nao podem ser alterados.
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
