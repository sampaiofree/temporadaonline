@php
    use Illuminate\Support\Facades\Storage;

    $filters = array_merge([
        'search' => '',
        'ativo' => '',
        'created_from' => '',
        'created_until' => '',
    ], $filters ?? []);
    $filtersActive = collect($filters)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
    $queryString = request()->getQueryString() ?? '';
    $listingQuery = $queryString ? '?'.$queryString : '';
@endphp

<x-app-layout title="Países">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Países</h2>
                <p class="text-sm text-slate-500">Gerencie os registros geográficos e faça upload em massa das imagens.</p>
            </div>
        </div>
    </x-slot>

    <div
        id="paises-filters-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/40 p-4"
        role="dialog"
        aria-modal="true"
    >
        <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Filtros avançados</h3>
                    <p class="text-sm text-slate-500">Refine a lista por status ou período de criação.</p>
                </div>
                <button
                    type="button"
                    data-close-paises-filters
                    class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500 transition hover:border-slate-300 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-100"
                >
                    Fechar
                </button>
            </div>

            <form
                id="paises-filters-form"
                action="{{ route('admin.paises.index') }}"
                method="GET"
                class="mt-6 space-y-4"
            >
                <input type="hidden" name="search" value="{{ $filters['search'] }}">

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-sm font-semibold text-slate-700" for="paises-filter-ativo">Status</label>
                        <select
                            id="paises-filter-ativo"
                            name="ativo"
                            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">Todos os status</option>
                            <option value="1" {{ $filters['ativo'] === '1' ? 'selected' : '' }}>Ativos</option>
                            <option value="0" {{ $filters['ativo'] === '0' ? 'selected' : '' }}>Inativos</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700" for="paises-filter-created-from">Criado a partir de</label>
                        <input
                            id="paises-filter-created-from"
                            name="created_from"
                            type="date"
                            value="{{ $filters['created_from'] }}"
                            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-sm font-semibold text-slate-700" for="paises-filter-created-until">Criado até</label>
                        <input
                            id="paises-filter-created-until"
                            name="created_until"
                            type="date"
                            value="{{ $filters['created_until'] }}"
                            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                    </div>
                    <div></div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-4">
                    <a href="{{ route('admin.paises.index') }}" class="text-sm font-semibold text-slate-500 transition hover:text-slate-700">
                        Limpar filtros
                    </a>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            data-close-paises-filters
                            class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:border-slate-300 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                        >
                            Aplicar filtros
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Upload em massa</h3>
                    <p class="text-sm text-slate-500">Selecione um ou vários arquivos e confirme o nome antes de salvar.</p>
                </div>
            </div>

            <form
                id="paises-mass-upload-form"
                action="{{ route('admin.paises.store') }}"
                method="POST"
                enctype="multipart/form-data"
                class="space-y-5 pt-4"
            >
                @csrf

                <div class="space-y-2">
                    <label for="paises-mass-upload-input" class="text-sm font-semibold text-slate-700">Imagens dos países</label>
                    <input
                        id="paises-mass-upload-input"
                        type="file"
                        multiple
                        accept="image/*"
                        class="w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-4 text-sm text-slate-500 transition hover:border-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                    <p class="text-xs text-slate-400">Os nomes dos arquivos serão convertidos automaticamente em nomes de países, mas você pode ajustá-los no preview.</p>
                </div>

                <div id="paises-mass-upload-error" class="hidden rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-sm text-red-700"></div>

                <div id="paises-mass-upload-preview" class="space-y-3">
                    <p class="text-sm text-slate-500">Nenhum arquivo selecionado.</p>
                </div>

                <button
                    type="submit"
                    data-upload-action
                    class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                >
                    Enviar arquivos
                </button>
            </form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Países cadastrados</h3>
                        <p class="text-sm text-slate-500">Lista completa em ordem alfabética.</p>
                    </div>
                    <form
                        id="paises-bulk-delete-form"
                        action="{{ route('admin.paises.bulk-destroy') }}{{ $listingQuery }}"
                        method="POST"
                        class="inline-flex"
                    >
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            data-bulk-delete
                            class="inline-flex items-center rounded-xl border border-rose-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-wide text-rose-600 transition hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-100"
                        >
                            Excluir todos os países
                        </button>
                    </form>
                </div>
                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <form
                        id="paises-search-form"
                        action="{{ route('admin.paises.index') }}"
                        method="GET"
                        class="flex flex-1 min-w-[260px] items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm"
                    >
                        <label class="sr-only" for="paises-search-input">Buscar países</label>
                        <input
                            id="paises-search-input"
                            type="search"
                            name="search"
                            value="{{ $filters['search'] }}"
                            placeholder="Buscar nome ou slug"
                            class="flex-1 rounded-xl border border-transparent bg-slate-50 px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                        >
                            Buscar
                        </button>
                        <input type="hidden" name="ativo" value="{{ $filters['ativo'] }}">
                        <input type="hidden" name="created_from" value="{{ $filters['created_from'] }}">
                        <input type="hidden" name="created_until" value="{{ $filters['created_until'] }}">
                    </form>
                    <button
                        type="button"
                        data-open-paises-filters
                        class="inline-flex items-center rounded-xl border px-4 py-2 text-xs font-semibold uppercase tracking-wide transition focus:outline-none focus:ring-2 focus:ring-blue-100 {{ $filtersActive ? 'border-blue-500 text-blue-600 shadow-sm' : 'border-slate-200 text-slate-600' }}"
                    >
                        Filtros avançados
                    </button>
                    @if($filtersActive)
                        <a
                            href="{{ route('admin.paises.index') }}"
                            class="text-sm font-semibold text-blue-600 transition hover:text-blue-500"
                        >
                            Limpar filtros
                        </a>
                    @endif
                </div>
            </div>
            <div class="px-6 py-4">
                @if($paises->hasPages())
                    <div class="mb-4">
                        {{ $paises->links() }}
                    </div>
                @endif
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Nome</th>
                                <th class="px-4 py-3 font-semibold">Slug</th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3 font-semibold">Criado em</th>
                                <th class="px-4 py-3 font-semibold">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($paises as $pais)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-4 align-top">
                                        <div class="flex items-center gap-3">
                                            @if($pais->imagem)
                                                <span class="inline-flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl bg-slate-100">
                                                    <img
                                                        src="{{ Storage::disk('public')->url($pais->imagem) }}"
                                                        alt="{{ $pais->nome }} logo"
                                                        class="h-full w-full object-cover"
                                                    >
                                                </span>
                                            @else
                                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-xs font-semibold uppercase text-slate-500">
                                                    {{ strtoupper(substr($pais->nome, 0, 2)) }}
                                                </span>
                                            @endif
                                            <div>
                                                <div class="text-sm font-semibold text-slate-900">{{ $pais->nome }}</div>
                                                <div class="text-xs text-slate-500">slug: {{ $pais->slug }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 align-top text-slate-600">{{ $pais->slug }}</td>
                                    <td class="px-4 py-4 align-top">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $pais->ativo ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ $pais->ativo ? 'Ativo' : 'Inativo' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 align-top text-slate-600">{{ $pais->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-4 align-top">
                                        <div class="flex flex-wrap gap-2">
                                            <a
                                                href="{{ route('admin.paises.edit', $pais) }}{{ $listingQuery }}"
                                                class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                                            >
                                                Editar
                                            </a>
                                            <form action="{{ route('admin.paises.destroy', $pais) }}{{ $listingQuery }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center rounded-xl border border-rose-200 px-3 py-1 text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
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
                                        Ainda não existem países cadastrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($paises->hasPages())
                    <div class="mt-4">
                        {{ $paises->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        (function () {
            const form = document.getElementById('paises-mass-upload-form');
            if (! form) {
                return;
            }

            const fileInput = document.getElementById('paises-mass-upload-input');
            const previewContainer = document.getElementById('paises-mass-upload-preview');
            const errorContainer = document.getElementById('paises-mass-upload-error');
            const submitButton = form.querySelector('[data-upload-action]');
            let uploads = [];

            const normalizeName = (file) => {
                const name = file.name.replace(/\.[^/.]+$/, '');
                return name.replace(/[-_]+/g, ' ').trim() || name;
            };

            const renderPreview = () => {
                previewContainer.innerHTML = '';

                if (uploads.length === 0) {
                    previewContainer.innerHTML = '<p class="text-sm text-slate-500">Nenhum arquivo selecionado.</p>';
                    return;
                }

                uploads.forEach((upload, index) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'flex flex-wrap items-center gap-3 rounded-xl border border-slate-100 px-4 py-3';

                    const img = document.createElement('img');
                    img.className = 'h-12 w-12 rounded-lg object-cover';
                    img.alt = upload.nome;
                    const url = URL.createObjectURL(upload.file);
                    img.src = url;
                    img.onload = () => URL.revokeObjectURL(url);

                    const fieldset = document.createElement('div');
                    fieldset.className = 'flex-1 min-w-[160px]';

                    const label = document.createElement('label');
                    label.className = 'block text-xs font-semibold text-slate-500';
                    label.textContent = 'Nome';

                    const input = document.createElement('input');
                    input.className = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100';
                    input.value = upload.nome;
                    input.dataset.index = index;
                    input.addEventListener('input', () => {
                        uploads[index].nome = input.value;
                    });

                    fieldset.appendChild(label);
                    fieldset.appendChild(input);

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.dataset.remove = index;
                    removeBtn.className = 'text-xs font-semibold uppercase text-rose-600 hover:text-rose-800';
                    removeBtn.textContent = 'Remover';
                    removeBtn.addEventListener('click', () => {
                        uploads.splice(index, 1);
                        renderPreview();
                    });

                    const info = document.createElement('p');
                    info.className = 'text-xs text-slate-400';
                    info.textContent = upload.file.name;

                    wrapper.appendChild(img);
                    wrapper.appendChild(fieldset);
                    wrapper.appendChild(info);
                    wrapper.appendChild(removeBtn);

                    previewContainer.appendChild(wrapper);
                });
            };

            fileInput.addEventListener('change', (event) => {
                const files = Array.from(event.target.files);
                files.forEach((file) => {
                    uploads.push({
                        file,
                        nome: normalizeName(file),
                    });
                });
                event.target.value = '';
                renderPreview();
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                if (uploads.length === 0) {
                    errorContainer.textContent = 'Selecione ao menos um arquivo antes de enviar.';
                    errorContainer.classList.remove('hidden');
                    return;
                }

                errorContainer.classList.add('hidden');
                submitButton.setAttribute('disabled', 'disabled');

                const formData = new FormData();
                formData.append('_token', form.querySelector('input[name="_token"]').value);

                uploads.forEach((upload, index) => {
                    formData.append(`uploads[${index}][nome]`, upload.nome);
                    formData.append(`uploads[${index}][imagem]`, upload.file);
                });

                try {
                    const response = await fetch(form.action, {
                        method: form.method || 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (response.ok) {
                        window.location.reload();
                        return;
                    }

                    if (response.status === 422) {
                        const payload = await response.json();
                        const message = payload.message || 'Revise os dados e tente novamente.';
                        errorContainer.textContent = message;
                        errorContainer.classList.remove('hidden');
                    } else {
                        throw new Error('Não foi possível enviar os arquivos.');
                    }
                } catch (error) {
                    errorContainer.textContent = error.message || 'Erro ao enviar os arquivos.';
                    errorContainer.classList.remove('hidden');
                } finally {
                    submitButton.removeAttribute('disabled');
                }
            });
        })();
    </script>
    <script>
        (function () {
            const modal = document.getElementById('paises-filters-modal');
            if (! modal) {
                return;
            }

            const openButton = document.querySelector('[data-open-paises-filters]');
            const closeButtons = modal.querySelectorAll('[data-close-paises-filters]');
            const bulkDeleteForm = document.getElementById('paises-bulk-delete-form');

            const openModal = () => {
                modal.classList.remove('hidden');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
            };

            openButton?.addEventListener('click', openModal);
            closeButtons.forEach((button) => button.addEventListener('click', closeModal));

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });

            bulkDeleteForm?.addEventListener('submit', (event) => {
                if (! window.confirm('Tem certeza que deseja excluir todos os países? Esta ação não pode ser desfeita.')) {
                    event.preventDefault();
                }
            });
        })();
    </script>
</x-app-layout>
