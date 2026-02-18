<x-app-layout :title="'Log: '.$fileName">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Visualizar log</h2>
                <p class="text-sm text-slate-500">
                    Arquivo: <span class="font-semibold text-slate-700">{{ $fileName }}</span> • Tamanho total: {{ $sizeHuman }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('admin.logs.index') }}"
                    class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                >
                    Voltar
                </a>
                <a
                    href="{{ route('admin.logs.download', ['file' => $fileName]) }}"
                    class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    Baixar arquivo
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
            @if ($truncated)
                Exibindo apenas os últimos {{ number_format($tailBytes / 1024, 0, ',', '.') }} KB do arquivo.
            @else
                Exibindo o conteúdo completo do arquivo.
            @endif
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-950 shadow-sm">
            <pre class="max-h-[70vh] overflow-auto p-4 text-xs leading-5 text-slate-100">{{ $content }}</pre>
        </div>
    </div>
</x-app-layout>
