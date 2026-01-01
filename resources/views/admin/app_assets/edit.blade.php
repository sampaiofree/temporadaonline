@php
    use Illuminate\Support\Facades\Storage;
@endphp

<x-app-layout title="Imagens do app">
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Imagens do app</h2>
            <p class="text-sm text-slate-500">Atualize logos, favicon e imagens do aplicativo.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <form action="{{ route('admin.app-assets.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-700">Favicon (.ico ou .png)</label>
                    @if($assets->favicon)
                        <div class="mb-2 inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                            <img
                                src="{{ Storage::disk('public')->url($assets->favicon) }}"
                                alt="Favicon"
                                class="h-8 w-8"
                            >
                            <span class="text-xs text-slate-500">Arquivo atual</span>
                        </div>
                    @endif
                    <input
                        type="file"
                        name="favicon"
                        accept="image/png,image/x-icon"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                    <p class="text-xs text-slate-400">Enviar um novo arquivo substitui o anterior.</p>
                    @error('favicon')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-700">Logo padrao</label>
                    @if($assets->logo_padrao)
                        <div class="mb-2 max-w-xs overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                            <img
                                src="{{ Storage::disk('public')->url($assets->logo_padrao) }}"
                                alt="Logo padrao"
                                class="h-28 w-full object-contain"
                            >
                        </div>
                    @endif
                    <input
                        type="file"
                        name="logo_padrao"
                        accept="image/*"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                    <p class="text-xs text-slate-400">Enviar um novo arquivo substitui o anterior.</p>
                    @error('logo_padrao')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-700">Logo dark</label>
                    @if($assets->logo_dark)
                        <div class="mb-2 max-w-xs overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                            <img
                                src="{{ Storage::disk('public')->url($assets->logo_dark) }}"
                                alt="Logo dark"
                                class="h-28 w-full object-contain"
                            >
                        </div>
                    @endif
                    <input
                        type="file"
                        name="logo_dark"
                        accept="image/*"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                    <p class="text-xs text-slate-400">Enviar um novo arquivo substitui o anterior.</p>
                    @error('logo_dark')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-700">Imagem do campo</label>
                    @if($assets->imagem_campo)
                        <div class="mb-2 max-w-xl overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                            <img
                                src="{{ Storage::disk('public')->url($assets->imagem_campo) }}"
                                alt="Imagem do campo"
                                class="h-40 w-full object-cover"
                            >
                        </div>
                    @endif
                    <input
                        type="file"
                        name="imagem_campo"
                        accept="image/*"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                    <p class="text-xs text-slate-400">Enviar um novo arquivo substitui o anterior.</p>
                    @error('imagem_campo')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-700">Imagem de background</label>
                    @if($assets->background_app)
                        <div class="mb-2 max-w-xl overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                            <img
                                src="{{ Storage::disk('public')->url($assets->background_app) }}"
                                alt="Imagem de background"
                                class="h-40 w-full object-cover"
                            >
                        </div>
                    @endif
                    <input
                        type="file"
                        name="background_app"
                        accept="image/*"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                    <p class="text-xs text-slate-400">Enviar um novo arquivo substitui o anterior.</p>
                    @error('background_app')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-6">
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                    >
                        Salvar imagens
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
