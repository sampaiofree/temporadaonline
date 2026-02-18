<x-app-layout title="Logs do sistema">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Logs do sistema</h2>
                <p class="text-sm text-slate-500">Arquivos disponíveis em <code>storage/logs</code>.</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Arquivo</th>
                            <th class="px-4 py-3 font-semibold">Tamanho</th>
                            <th class="px-4 py-3 font-semibold">Modificado em</th>
                            <th class="px-4 py-3 font-semibold">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($files as $file)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-4 font-semibold text-slate-900">{{ $file['name'] }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $file['size_human'] }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $file['modified_at']->format('d/m/Y H:i:s') }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a
                                            href="{{ route('admin.logs.view', ['file' => $file['name']]) }}"
                                            class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                                        >
                                            Ver
                                        </a>
                                        <a
                                            href="{{ route('admin.logs.download', ['file' => $file['name']]) }}"
                                            class="inline-flex items-center rounded-xl bg-slate-900 px-3 py-1 text-xs font-semibold text-white transition hover:bg-slate-800"
                                        >
                                            Baixar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">
                                    Nenhum arquivo encontrado em <code>storage/logs</code>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
