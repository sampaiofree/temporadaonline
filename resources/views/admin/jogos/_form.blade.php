@props([
    'action',
    'method' => 'POST',
    'jogo' => null,
    'submitLabel' => 'Salvar',
])

@php
    $nomeAtual = old('nome', $jogo->nome ?? '');
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if (! in_array(strtoupper($method), ['POST'], true))
        @method($method)
    @endif

    <div>
        <label for="nome" class="block text-sm font-semibold text-slate-700">Nome do jogo</label>
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

    <div class="space-y-2">
        <label class="block text-sm font-semibold text-slate-700">Imagem</label>
        @if($jogo?->imagem)
            <div class="mb-2 max-w-xs overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                <img
                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($jogo->imagem) }}"
                    alt="{{ $jogo->nome }}"
                    class="h-32 w-full object-cover"
                >
            </div>
        @endif
        <input
            type="file"
            name="imagem"
            accept=".png,.jpg,.jpeg,.webp,.svg"
            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
        >
        <p class="text-xs text-slate-400">PNG, JPG, WEBP ou SVG. Enviar substitui a imagem atual.</p>
        @error('imagem')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center justify-between gap-3 pt-6">
        <a href="{{ route('admin.jogos.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Voltar para a lista</a>
        <button
            type="submit"
            class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
        >
            {{ $submitLabel }}
        </button>
    </div>
</form>
