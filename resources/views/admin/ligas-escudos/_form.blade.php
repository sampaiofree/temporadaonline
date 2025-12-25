@php
    use Illuminate\Support\Facades\Storage;
@endphp

@props([
    'action',
    'method' => 'POST',
    'ligaEscudo' => null,
    'paises',
    'submitLabel' => 'Salvar',
])

@php
    $nomeAtual = old('liga_nome', $ligaEscudo?->liga_nome ?? '');
    $paisAtual = old('pais_id', $ligaEscudo?->pais_id ?? '');
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @unless(in_array(strtoupper($method), ['POST'], true))
        @method($method)
    @endunless

    <div>
        <label for="liga_nome" class="block text-sm font-semibold text-slate-700">Nome da liga</label>
        <input
            type="text"
            id="liga_nome"
            name="liga_nome"
            value="{{ $nomeAtual }}"
            required
            class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
        >
        @error('liga_nome')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="pais_id" class="block text-sm font-semibold text-slate-700">País</label>
        <select
            id="pais_id"
            name="pais_id"
            required
            class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
        >
            <option value="">Selecione um país</option>
            @foreach($paises as $pais)
                <option value="{{ $pais->id }}" {{ $pais->id === (int) $paisAtual ? 'selected' : '' }}>
                    {{ $pais->nome }}
                </option>
            @endforeach
        </select>
        @error('pais_id')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-2">
        <label class="block text-sm font-semibold text-slate-700">Imagem</label>
        @if($ligaEscudo?->liga_imagem)
            <div class="mb-2 max-w-xs overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                <img
                    src="{{ Storage::disk('public')->url($ligaEscudo->liga_imagem) }}"
                    alt="{{ $ligaEscudo->liga_nome }}"
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
        <a href="{{ route('admin.ligas-escudos.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Voltar para a lista</a>
        <button
            type="submit"
            class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
        >
            {{ $submitLabel }}
        </button>
    </div>
</form>
