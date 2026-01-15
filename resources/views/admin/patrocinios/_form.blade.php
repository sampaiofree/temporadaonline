@props([
    'action',
    'method' => 'POST',
    'patrocinio' => null,
    'submitLabel' => 'Salvar',
])

@php
    use Illuminate\Support\Facades\Storage;

    $nomeAtual = old('nome', $patrocinio->nome ?? '');
    $descricaoAtual = old('descricao', $patrocinio->descricao ?? '');
    $valorAtual = old('valor', $patrocinio->valor ?? '');
    $fansAtual = old('fans', $patrocinio->fans ?? '');
    $imagemAtual = $patrocinio->imagem ?? null;
    $imagemObrigatoria = $imagemAtual === null;
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

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label for="valor" class="block text-sm font-semibold text-slate-700">Valor</label>
            <input
                type="number"
                id="valor"
                name="valor"
                min="1"
                value="{{ $valorAtual }}"
                required
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
            @error('valor')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="fans" class="block text-sm font-semibold text-slate-700">Fans</label>
            <input
                type="number"
                id="fans"
                name="fans"
                min="1"
                value="{{ $fansAtual }}"
                required
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
            @error('fans')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="imagem" class="block text-sm font-semibold text-slate-700">Imagem</label>
        @if($imagemAtual)
            <div class="mt-2 flex items-center gap-4">
                <span class="inline-flex h-14 w-14 items-center justify-center overflow-hidden rounded-xl bg-slate-100">
                    <img
                        src="{{ Storage::disk('public')->url($imagemAtual) }}"
                        alt="{{ $nomeAtual }}"
                        class="h-full w-full object-cover"
                    >
                </span>
                <span class="text-xs text-slate-500">Envie uma nova imagem para substituir.</span>
            </div>
        @endif
        <input
            type="file"
            id="imagem"
            name="imagem"
            accept="image/*"
            class="mt-2 w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-sm text-slate-500 transition hover:border-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            @if($imagemObrigatoria) required @endif
        >
        <p class="mt-1 text-xs text-slate-400">Formatos aceitos: JPG, PNG ou WEBP (max 4MB).</p>
        @error('imagem')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center justify-between gap-3 pt-6">
        <a href="{{ route('admin.patrocinios.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Voltar para a lista</a>
        <button
            type="submit"
            class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
        >
            {{ $submitLabel }}
        </button>
    </div>
</form>
