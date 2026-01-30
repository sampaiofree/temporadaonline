@php
    use Illuminate\\Support\\Facades\\Storage;
@endphp

<x-app-layout title="Imagens das conquistas">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Imagens das conquistas</h2>
                <p class="text-sm text-slate-500">Gerencie os arquivos vinculados aos selos das conquistas.</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @include('admin.imagens._mass_upload', [
            'title' => 'Upload em massa de conquistas',
            'description' => 'Selecione um ou vários arquivos e confirme o nome antes de salvar.',
            'formAction' => route('admin.conquistas-imagens.store'),
            'formId' => 'conquistas-imagens-mass-upload-form',
            'inputId' => 'conquistas-imagens-mass-upload-input',
            'previewId' => 'conquistas-imagens-mass-upload-preview',
            'errorId' => 'conquistas-imagens-mass-upload-error',
            'fileLabel' => 'Imagens das conquistas',
            'inputHint' => 'Os nomes dos arquivos serão convertidos automaticamente em nomes das conquistas, mas você pode ajustá-los no preview.',
            'submitLabel' => 'Enviar arquivos',
        ])

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Imagem</th>
                            <th class="px-4 py-3 font-semibold">Nome</th>
                            <th class="px-4 py-3 font-semibold">Criado em</th>
                            <th class="px-4 py-3 font-semibold">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($imagens as $imagem)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-4 align-top">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl bg-slate-100">
                                            @if($imagem->url)
                                                <img
                                                    src="{{ Storage::disk('public')->url($imagem->url) }}"
                                                    alt="{{ $imagem->nome }}"
                                                    class="h-full w-full object-cover"
                                                >
                                            @else
                                                <span class="text-xs font-semibold uppercase text-slate-500">
                                                    {{ strtoupper(substr($imagem->nome, 0, 2)) }}
                                                </span>
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $imagem->nome }}</td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $imagem->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex flex-wrap gap-2">
                                        <a
                                            href="{{ route('admin.conquistas-imagens.edit', $imagem) }}"
                                            class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                                        >
                                            Editar
                                        </a>
                                        <form action="{{ route('admin.conquistas-imagens.destroy', $imagem) }}" method="POST" class="inline">
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
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">
                                    Ainda não existem imagens cadastradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($imagens->hasPages())
                <div class="px-6 py-4">
                    <div class="mb-4">
                        {{ $imagens->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
