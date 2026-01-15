@php
    $posicaoAtual = old('posicao', $premiacao->posicao ?? '');
    $premiacaoAtual = old('premiacao', $premiacao->premiacao ?? '');
    $imagemAtual = old('imagem', $premiacao->imagem ?? null);
@endphp

<div class="space-y-6">
    <div>
        <label for="posicao" class="block text-sm font-semibold text-slate-700">Posição</label>
        <input
            id="posicao"
            name="posicao"
            type="number"
            min="1"
            value="{{ $posicaoAtual }}"
            class="mt-1 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
        >
        @error('posicao')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="premiacao" class="block text-sm font-semibold text-slate-700">Valor da premiação</label>
        <input
            id="premiacao"
            name="premiacao"
            type="number"
            min="1"
            value="{{ $premiacaoAtual }}"
            class="mt-1 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
        >
        @error('premiacao')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="imagem" class="block text-sm font-semibold text-slate-700">Imagem</label>
        <input
            id="imagem"
            name="imagem"
            type="file"
            accept="image/png,image/jpeg,image/webp"
            class="mt-1 block w-full rounded-xl border border-dashed border-slate-200 bg-white px-3 py-2 text-sm text-slate-500 shadow-sm focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
        >
        @if($imagemAtual)
            <p class="text-xs text-slate-500 mt-1">Imagem atual: {{ $premiacao->imagem ? basename($premiacao->imagem) : '—' }}</p>
        @endif
        @error('imagem')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>
