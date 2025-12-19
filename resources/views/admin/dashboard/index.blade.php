<x-app-layout title="Painel administrativo">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Status do Ecossistema</h2>
                <p class="text-sm text-slate-500 font-medium">Última atualização: {{ $lastUpdated->format('H:i') }}h</p>
            </div>
            <button class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <i data-lucide="download" class="w-4 h-4 text-slate-400"></i>
                Exportar relatório
            </button>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-5 mb-8">
        @foreach ($metrics as $metric)
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $metric['label'] }}</span>
                    <div class="rounded-lg bg-blue-50 p-2 text-blue-600">
                        <i data-lucide="activity" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-slate-900">{{ $metric['value'] }}</span>
                </div>
                <p class="mt-1 text-xs text-slate-500 leading-tight">{{ $metric['description'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 bg-white px-6 py-4">
                <div class="flex items-center gap-2 text-slate-800 font-bold">
                    <i data-lucide="list-ordered" class="w-4 h-4 text-blue-500"></i>
                    Últimas ações do sistema
                </div>
                <a href="#" class="text-xs font-bold text-blue-600 hover:underline">Ver tudo</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3 font-semibold text-slate-600">Ação</th>
                            <th class="px-6 py-3 font-semibold text-slate-600">Autor</th>
                            <th class="px-6 py-3 font-semibold text-slate-600">Data/Hora</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($acoesRecentes as $acao)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="font-medium text-slate-900">{{ $acao['acao'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $acao['descricao'] }}</p>
                                </td>
                                <td class="px-6 py-4 text-slate-600 font-medium">{{ $acao['autor'] }}</td>
                                <td class="px-6 py-4 text-slate-500">{{ $acao['quando'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-slate-400">Nenhum registro encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 font-bold text-slate-800 flex items-center gap-2">
                    <i data-lucide="shield-alert" class="w-4 h-4 text-amber-500"></i>
                    Regras de ouro
                </h3>
                <div class="space-y-3">
                    @foreach ($alerts as $alert)
                        <div class="flex items-start gap-3 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <i data-lucide="check-circle-2" class="mt-0.5 h-4 w-4 text-green-500 flex-shrink-0"></i>
                            <span class="text-xs font-medium text-slate-700">{{ $alert }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl bg-slate-900 p-6 text-white shadow-lg">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Suporte técnico</p>
                <p class="mt-2 text-sm text-slate-300">Precisa de ajuda com o painel?</p>
                <button class="mt-4 w-full rounded-lg bg-blue-600 py-2 text-sm font-bold hover:bg-blue-500 transition-colors">
                    Abrir chamado
                </button>
            </div>
        </div>
    </div>
</x-app-layout>
