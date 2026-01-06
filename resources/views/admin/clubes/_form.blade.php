@php
    use Illuminate\Support\Facades\Storage;
@endphp

@props([
    'action',
    'method' => 'POST',
    'clube',
    'escudos',
    'selectedEscudoId' => null,
    'usedEscudos' => [],
    'saldoAtual' => 0,
    'submitLabel' => 'Salvar',
    'queryString' => '',
])

@php
    $currentNome = old('nome', $clube->nome ?? '');
    $currentEscudoId = old('escudo_id', $selectedEscudoId ?? '');
    $currentSaldo = old('saldo', $saldoAtual ?? 0);
    $previewPath = '';
    if ((string) $currentEscudoId !== '') {
        $previewPath = $escudos->firstWhere('id', (int) $currentEscudoId)?->clube_imagem ?? '';
    }
    if (! $previewPath && isset($clube->escudo?->clube_imagem)) {
        $previewPath = $clube->escudo?->clube_imagem;
    }
    $previewUrl = $previewPath ? Storage::disk('public')->url($previewPath) : null;
@endphp

<form action="{{ $action }}" method="POST" class="space-y-6">
    @csrf
    @unless(in_array(strtoupper($method), ['POST'], true))
        @method($method)
    @endunless

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="nome" class="block text-sm font-semibold text-slate-700">Nome do clube</label>
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
            <label class="block text-sm font-semibold text-slate-700">Liga</label>
            <div class="mt-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                {{ $clube->liga?->nome ?? '—' }}
            </div>
        </div>
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700">Usuário dono</label>
        <div class="mt-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
            {{ $clube->user?->nickname ?? $clube->user?->name ?? '—' }}
            @if($clube->user)
                <div class="text-xs text-slate-500">ID {{ $clube->user->id }} · {{ $clube->user->email }}</div>
            @endif
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="clube-escudo-select" class="block text-sm font-semibold text-slate-700">Escudo</label>
            <select
                id="clube-escudo-select"
                name="escudo_id"
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
                <option value="">Nenhum escudo</option>
                @foreach($escudos as $escudo)
                    @php
                        $isUsed = in_array($escudo->id, $usedEscudos, true);
                        $isSelected = (string) $escudo->id === (string) $currentEscudoId;
                    @endphp
                    <option
                        value="{{ $escudo->id }}"
                        data-escudo-url="{{ $escudo->clube_imagem ? Storage::disk('public')->url($escudo->clube_imagem) : '' }}"
                        @selected((string) $escudo->id === (string) $currentEscudoId)
                        @disabled($isUsed && ! $isSelected)
                    >
                        {{ $escudo->clube_nome }}{{ $isUsed && ! $isSelected ? ' (em uso na confederação)' : '' }}
                    </option>
                @endforeach
            </select>
            @error('escudo_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="saldo" class="block text-sm font-semibold text-slate-700">Saldo disponível</label>
            <input
                type="number"
                id="saldo"
                name="saldo"
                min="0"
                value="{{ $currentSaldo }}"
                required
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
            @error('saldo')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div id="clube-escudo-preview-wrapper" class="{{ $previewUrl ? '' : 'hidden' }}">
            <img
                id="clube-escudo-preview"
                src="{{ $previewUrl }}"
                alt="Preview do escudo"
                class="h-16 w-16 rounded-xl border border-slate-200 object-cover"
            >
        </div>
        <p class="text-sm text-slate-500">Selecione um escudo para substituir a imagem atual.</p>
    </div>

    <div class="flex items-center justify-between gap-3 pt-6">
        <a
            href="{{ route('admin.clubes.index') }}{{ $queryString ? '?'.$queryString : '' }}"
            class="text-sm font-semibold text-slate-600 hover:text-slate-900"
        >
            Voltar para a lista
        </a>
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
        const select = document.getElementById('clube-escudo-select');
        const preview = document.getElementById('clube-escudo-preview');
        const previewWrapper = document.getElementById('clube-escudo-preview-wrapper');

        const updatePreview = (url) => {
            if (! url) {
                previewWrapper?.classList.add('hidden');
                if (preview) {
                    preview.removeAttribute('src');
                }
                return;
            }

            if (preview) {
                preview.src = url;
                previewWrapper?.classList.remove('hidden');
            }
        };

        select?.addEventListener('change', (event) => {
            const option = event.target.selectedOptions[0];
            updatePreview(option?.dataset.escudoUrl || '');
        });
    });
</script>
