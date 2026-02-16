<x-app-layout title="Ligas">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Ligas</h2>
                <p class="text-sm text-slate-500">Listagem simples ordenada pelas mais recentes.</p>
            </div>
            <a
                href="{{ route('admin.ligas.create') }}"
                class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500"
            >
                Nova liga
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Nome</th>
                            <th class="px-4 py-3 font-semibold">Confederacao</th>
                            <th class="px-4 py-3 font-semibold">Jogo</th>
                            <th class="px-4 py-3 font-semibold">Geração</th>
                            <th class="px-4 py-3 font-semibold">Plataforma</th>
                            <th class="px-4 py-3 font-semibold">Máx. clubes</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Clubes</th>
                            <th class="px-4 py-3 font-semibold">Usuários</th>
                            <th class="px-4 py-3 font-semibold">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($ligas as $liga)
                            @php
                                $statusLabel = match ($liga->status) {
                                    'ativa' => 'Ativa',
                                    'finalizada', 'encerrada' => 'Finalizada',
                                    'aguardando' => 'Inativa',
                                    default => ucfirst($liga->status),
                                };
                                $statusClass = match ($liga->status) {
                                    'ativa' => 'bg-emerald-100 text-emerald-700',
                                    'finalizada', 'encerrada' => 'bg-slate-200 text-slate-700',
                                    default => 'bg-amber-100 text-amber-700',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-4 align-top">
                                    <div class="text-sm font-semibold text-slate-900">{{ $liga->nome }}</div>
                                    <div class="text-xs text-slate-500">{{ $liga->created_at?->format('d/m/Y H:i') }}</div>
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $liga->confederacao?->nome ?? '-' }}</td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $liga->jogo?->nome ?? '-' }}</td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $liga->geracao?->nome ?? '-' }}</td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $liga->plataforma?->nome ?? '-' }}</td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $liga->max_times }}</td>
                                <td class="px-4 py-4 align-top">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $liga->clubes_count }}</td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $liga->users_count }}</td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex flex-wrap gap-2">
                                        <a
                                            href="{{ route('admin.ligas.edit', $liga) }}"
                                            class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                                        >
                                            Editar
                                        </a>
                                        @if($liga->status !== 'finalizada' && $liga->status !== 'encerrada')
                                            <form action="{{ route('admin.ligas.finalize', $liga) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center rounded-xl border border-amber-200 px-3 py-1 text-xs font-semibold text-amber-700 transition hover:bg-amber-50"
                                                >
                                                    Finalizar
                                                </button>
                                            </form>
                                        @endif
                                        @if($liga->clubes_count === 0 && $liga->users_count === 0)
                                            <form action="{{ route('admin.ligas.destroy', $liga) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center rounded-xl border border-red-200 px-3 py-1 text-xs font-semibold text-red-700 transition hover:bg-red-50"
                                                >
                                                    Excluir
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-6 text-center text-sm text-slate-500">
                                    Ainda não existem ligas cadastradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
