@php
    use Illuminate\Support\Facades\Storage;

    $filters = array_merge([
        'search' => '',
    ], $filters ?? []);
    $queryString = request()->getQueryString() ?? '';
    $listingQuery = $queryString ? '?'.$queryString : '';
@endphp

<x-app-layout title="Playstyles">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Playstyles</h2>
                <p class="text-sm text-slate-500">Gerencie os playstyles e faca upload em massa de imagens.</p>
            </div>
        </div>
    </x-slot>

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
                    <p class="text-sm text-slate-500">Selecione os arquivos e ajuste o nome antes de salvar.</p>
                </div>
            </div>

            <form
                id="playstyles-mass-upload-form"
                action="{{ route('admin.playstyles.store') }}"
                method="POST"
                enctype="multipart/form-data"
                class="space-y-5 pt-4"
            >
                @csrf

                <div class="space-y-2">
                    <label for="playstyles-mass-upload-input" class="text-sm font-semibold text-slate-700">Imagens dos playstyles</label>
                    <input
                        id="playstyles-mass-upload-input"
                        type="file"
                        multiple
                        accept="image/*"
                        class="w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-4 text-sm text-slate-500 transition hover:border-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                    <p class="text-xs text-slate-400">Os nomes dos arquivos viram o nome padrao, mas voce pode editar no preview.</p>
                </div>

                <div id="playstyles-mass-upload-error" class="hidden rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-sm text-red-700"></div>

                <div id="playstyles-mass-upload-preview" class="space-y-3">
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
                        <h3 class="text-lg font-semibold text-slate-900">Playstyles cadastrados</h3>
                        <p class="text-sm text-slate-500">Lista completa em ordem alfabetica.</p>
                    </div>
                    <form
                        id="playstyles-bulk-delete-form"
                        action="{{ route('admin.playstyles.bulk-destroy') }}{{ $listingQuery }}"
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
                            Excluir todos os playstyles
                        </button>
                    </form>
                </div>
                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <form
                        id="playstyles-search-form"
                        action="{{ route('admin.playstyles.index') }}"
                        method="GET"
                        class="flex flex-1 min-w-[260px] items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm"
                    >
                        <label class="sr-only" for="playstyles-search-input">Buscar playstyles</label>
                        <input
                            id="playstyles-search-input"
                            type="search"
                            name="search"
                            value="{{ $filters['search'] }}"
                            placeholder="Buscar por nome"
                            class="flex-1 rounded-xl border border-transparent bg-slate-50 px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                        >
                            Buscar
                        </button>
                    </form>
                    @if($filters['search'] !== '')
                        <a
                            href="{{ route('admin.playstyles.index') }}"
                            class="text-sm font-semibold text-blue-600 transition hover:text-blue-500"
                        >
                            Limpar busca
                        </a>
                    @endif
                </div>
            </div>
            <div class="px-6 py-4">
                @if($playstyles->hasPages())
                    <div class="mb-4">
                        {{ $playstyles->links() }}
                    </div>
                @endif
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Nome</th>
                                <th class="px-4 py-3 font-semibold">Criado em</th>
                                <th class="px-4 py-3 font-semibold">Acoes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($playstyles as $playstyle)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-4 align-top">
                                        <div class="flex items-center gap-3">
                                            @if($playstyle->imagem)
                                                <span class="inline-flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl bg-slate-100">
                                                    <img
                                                        src="{{ Storage::disk('public')->url($playstyle->imagem) }}"
                                                        alt="{{ $playstyle->nome }} image"
                                                        class="h-full w-full object-cover"
                                                    >
                                                </span>
                                            @else
                                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-xs font-semibold uppercase text-slate-500">
                                                    {{ strtoupper(substr($playstyle->nome, 0, 2)) }}
                                                </span>
                                            @endif
                                            <div class="text-sm font-semibold text-slate-900">{{ $playstyle->nome }}</div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 align-top text-slate-600">{{ $playstyle->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-4 align-top">
                                        <form action="{{ route('admin.playstyles.destroy', $playstyle) }}{{ $listingQuery }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-xl border border-rose-200 px-3 py-1 text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
                                            >
                                                Excluir
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center text-sm text-slate-500">
                                        Ainda nao existem playstyles cadastrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($playstyles->hasPages())
                    <div class="mt-4">
                        {{ $playstyles->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        (function () {
            const form = document.getElementById('playstyles-mass-upload-form');
            if (! form) {
                return;
            }

            const fileInput = document.getElementById('playstyles-mass-upload-input');
            const previewContainer = document.getElementById('playstyles-mass-upload-preview');
            const errorContainer = document.getElementById('playstyles-mass-upload-error');
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
                        throw new Error('Nao foi possivel enviar os arquivos.');
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
            const bulkDeleteForm = document.getElementById('playstyles-bulk-delete-form');

            bulkDeleteForm?.addEventListener('submit', (event) => {
                if (! window.confirm('Tem certeza que deseja excluir todos os playstyles? Esta acao nao pode ser desfeita.')) {
                    event.preventDefault();
                }
            });
        })();
    </script>
</x-app-layout>
