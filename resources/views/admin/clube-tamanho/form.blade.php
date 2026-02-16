@php
    use Illuminate\Support\Facades\Storage;

    $title = $isEdit ? 'Editar Clube Tamanho' : 'Novo Clube Tamanho';
@endphp

<x-app-layout :title="$title">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">{{ $title }}</h2>
                <p class="text-sm text-slate-500">Preencha os dados do tamanho do clube.</p>
            </div>
            <a
                href="{{ route('admin.clube-tamanho.index') }}"
                class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
            >
                Voltar
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if($errors->any())
            <div class="rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
                <p class="font-semibold">Corrija os campos e tente novamente.</p>
                <ul class="mt-2 list-disc pl-5 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <form
                action="{{ $isEdit ? route('admin.clube-tamanho.update', $item) : route('admin.clube-tamanho.store') }}"
                method="POST"
                enctype="multipart/form-data"
                class="space-y-4"
            >
                @csrf
                @if($isEdit)
                    @method('PATCH')
                @endif

                <div>
                    <label for="nome" class="text-xs font-semibold text-slate-700">Nome</label>
                    <input
                        id="nome"
                        name="nome"
                        value="{{ old('nome', $item->nome) }}"
                        maxlength="150"
                        required
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                    @error('nome')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="descricao" class="text-xs font-semibold text-slate-700">Descrição</label>
                    <textarea
                        id="descricao"
                        name="descricao"
                        rows="3"
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >{{ old('descricao', $item->descricao) }}</textarea>
                    @error('descricao')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="n_fans" class="text-xs font-semibold text-slate-700">Nº fãs</label>
                    <input
                        id="n_fans"
                        type="number"
                        name="n_fans"
                        value="{{ old('n_fans', $item->n_fans ?? 0) }}"
                        min="0"
                        required
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                    @error('n_fans')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="imagem" class="text-xs font-semibold text-slate-700">Imagem</label>
                    @if($isEdit && $item->imagem)
                        <div class="max-w-[220px] overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                            <img
                                src="{{ Storage::disk('public')->url($item->imagem) }}"
                                alt="{{ $item->nome }}"
                                class="h-28 w-full object-cover"
                            >
                        </div>
                    @endif
                    <input
                        id="imagem"
                        type="file"
                        name="imagem"
                        accept="image/*"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                    <p class="text-xs text-slate-400">Ao editar, enviar nova imagem substitui a atual.</p>
                    @error('imagem')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a
                        href="{{ route('admin.clube-tamanho.index') }}"
                        class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                    >
                        Cancelar
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                    >
                        {{ $isEdit ? 'Salvar alterações' : 'Salvar' }}
                    </button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
