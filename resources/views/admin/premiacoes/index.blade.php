@php
    use Illuminate\Support\Facades\Storage;
@endphp

<x-app-layout title="Premiações">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Premiações</h2>
                <p class="text-sm text-slate-500">Defina a recompensa visual para cada posição.</p>
            </div>
            <a
                href="{{ route('admin.premiacoes.create') }}"
                class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500"
            >
                Nova premiação
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
                            <th class="px-4 py-3 font-semibold">Posição</th>
                            <th class="px-4 py-3 font-semibold">Imagem</th>
                            <th class="px-4 py-3 font-semibold">Premiação</th>
                            <th class="px-4 py-3 font-semibold">Criado em</th>
                            <th class="px-4 py-3 font-semibold">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($premiacoes as $premiacao)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-4 align-top text-slate-600">{{ $premiacao->posicao }}</td>
                                <td class="px-4 py-4 align-top">
                                    @if($premiacao->imagem)
                                        <span class="inline-flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl bg-slate-100">
                                            <img
                                                src="{{ Storage::disk('public')->url($premiacao->imagem) }}"
                                                alt="Imagem da premiação {{ $premiacao->posicao }}"
                                                class="h-full w-full object-cover"
                                            >
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-500">Sem imagem</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $premiacao->premiacao }}</td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $premiacao->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex flex-wrap gap-2">
                                        <a
                                            href="{{ route('admin.premiacoes.edit', $premiacao) }}"
                                            class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                                        >
                                            Editar
                                        </a>
                                        <form action="{{ route('admin.premiacoes.destroy', $premiacao) }}" method="POST" class="inline">
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
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">
                                    Ainda não existem premiações cadastradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
