@php
    use Illuminate\Support\Facades\Storage;
@endphp

@props([
    'action',
    'method' => 'POST',
    'pais' => null,
    'submitLabel' => 'Salvar',
])

@php
    $nomeAtual = old('nome', $pais?->nome ?? '');
    $ativoAtual = (bool) old('ativo', $pais?->ativo ?? true);
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @unless(in_array(strtoupper($method), ['POST'], true))
        @method($method)
    @endunless

    <div>
        <label for="nome" class="block text-sm font-semibold text-slate-700">Nome do país</label>
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
        @if($pais?->imagem)
            <div class="mb-2 max-w-xs overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                <img
                    src="{{ Storage::disk('public')->url($pais->imagem) }}"
                    alt="{{ $pais->nome }}"
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
        <p class="text-xs text-slate-400">Envie um PNG/JPG. Enviar um novo arquivo substitui o anterior.</p>
        @error('imagem')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <input type="hidden" name="ativo" value="0">
    <div class="flex items-center gap-3">
        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
            <input
                type="checkbox"
                name="ativo"
                value="1"
                {{ $ativoAtual ? 'checked' : '' }}
                class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
            >
            Ativo
        </label>
        @error('ativo')
            <p class="text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center justify-between gap-3 pt-6">
        <a href="{{ route('admin.paises.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Voltar para a lista</a>
        <button
            type="submit"
            class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
        >
            {{ $submitLabel }}
        </button>
    </div>
</form>
