@php
    use Illuminate\Support\Facades\Storage;
@endphp

<x-app-layout title="Clube Tamanho">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Clube Tamanho</h2>
                <p class="text-sm text-slate-500">Cadastre os níveis de tamanho do clube por faixa de fãs.</p>
            </div>
            <a
                href="{{ route('admin.clube-tamanho.create') }}"
                class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
            >
                Novo
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Imagem</th>
                            <th class="px-4 py-3 font-semibold">Nome</th>
                            <th class="px-4 py-3 font-semibold">Descrição</th>
                            <th class="px-4 py-3 font-semibold">Nº fãs</th>
                            <th class="px-4 py-3 font-semibold">Criado em</th>
                            <th class="px-4 py-3 font-semibold text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($clubesTamanho as $item)
                            <tr class="transition-colors hover:bg-slate-50">
                                <td class="px-4 py-4 align-top">
                                    @if($item->imagem)
                                        <span class="inline-flex h-12 w-12 items-center justify-center overflow-hidden rounded-xl bg-slate-100">
                                            <img
                                                src="{{ Storage::disk('public')->url($item->imagem) }}"
                                                alt="{{ $item->nome }}"
                                                class="h-full w-full object-cover"
                                            >
                                        </span>
                                    @else
                                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-xs font-semibold uppercase text-slate-500">
                                            {{ strtoupper(substr($item->nome, 0, 2)) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 align-top font-semibold text-slate-900">{{ $item->nome }}</td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $item->descricao ?: '-' }}</td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ number_format((int) $item->n_fans, 0, ',', '.') }}</td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $item->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="px-4 py-4 align-top text-right">
                                    <div class="flex justify-end gap-2">
                                        <a
                                            href="{{ route('admin.clube-tamanho.edit', $item) }}"
                                            class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                                        >
                                            Editar
                                        </a>
                                        <form action="{{ route('admin.clube-tamanho.destroy', $item) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                onclick="return confirm('Deseja realmente excluir este registro?')"
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
                                    Nenhum registro cadastrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
