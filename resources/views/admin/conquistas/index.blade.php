@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;
@endphp

<x-app-layout title="Conquistas">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Conquistas</h2>
                <p class="text-sm text-slate-500">Gerencie os selos de conquistas do clube.</p>
            </div>
            <a
                href="{{ route('admin.conquistas.create') }}"
                class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500"
            >
                Nova conquista
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
                            <th class="px-4 py-3 font-semibold">Conquista</th>
                            <th class="px-4 py-3 font-semibold">Tipo</th>
                            <th class="px-4 py-3 font-semibold">Quantidade</th>
                            <th class="px-4 py-3 font-semibold">Fans</th>
                            <th class="px-4 py-3 font-semibold">Criado em</th>
                            <th class="px-4 py-3 font-semibold">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($conquistas as $conquista)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-4 align-top">
                                    <div class="flex items-center gap-3">
                                        @if($conquista->imagem)
                                            <span class="inline-flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl bg-slate-100">
                                                <img
                                                    src="{{ Storage::disk('public')->url($conquista->imagem) }}"
                                                    alt="{{ $conquista->nome }}"
                                                    class="h-full w-full object-cover"
                                                >
                                            </span>
                                        @else
                                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-xs font-semibold uppercase text-slate-500">
                                                {{ strtoupper(substr($conquista->nome, 0, 2)) }}
                                            </span>
                                        @endif
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">{{ $conquista->nome }}</div>
                                            <p class="text-xs text-slate-500">{{ Str::limit($conquista->descricao, 90) }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $tipos[$conquista->tipo] ?? $conquista->tipo }}</td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $conquista->quantidade }}</td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $conquista->fans }}</td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $conquista->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex flex-wrap gap-2">
                                        <a
                                            href="{{ route('admin.conquistas.edit', $conquista) }}"
                                            class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                                        >
                                            Editar
                                        </a>
                                        <form action="{{ route('admin.conquistas.destroy', $conquista) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-xl border border-red-200 px-3 py-1 text-xs font-semibold text-red-700 transition hover:bg-red-50"
                                            >
                                                Excluir
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">
                                    Ainda nao existem conquistas cadastradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
