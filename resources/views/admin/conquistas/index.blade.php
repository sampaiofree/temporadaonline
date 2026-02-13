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
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    id="conquistas-bulk-toggle"
                    class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-300"
                >
                    Upload em massa
                </button>
                <a
                    href="{{ route('admin.conquistas.create') }}"
                    class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500"
                >
                    Nova conquista
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div id="conquistas-bulk-panel" class="hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Upload em massa de conquistas</h3>
                    <p class="text-sm text-slate-500">Selecione as imagens e preencha os dados de cada conquista antes de enviar.</p>
                </div>
            </div>

            <form
                id="conquistas-mass-upload-form"
                action="{{ route('admin.conquistas.bulk-store') }}"
                method="POST"
                enctype="multipart/form-data"
                class="space-y-5 pt-4"
            >
                @csrf

                <div class="space-y-2">
                    <label for="conquistas-mass-upload-input" class="text-sm font-semibold text-slate-700">Imagens das conquistas</label>
                    <input
                        id="conquistas-mass-upload-input"
                        type="file"
                        multiple
                        accept="image/*"
                        class="w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-4 text-sm text-slate-500 transition hover:border-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                    <p class="text-xs text-slate-400">Formatos aceitos: JPG, PNG ou WEBP (max 4MB por arquivo).</p>
                </div>

                <div id="conquistas-mass-upload-error" class="hidden rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-sm text-red-700"></div>

                <div id="conquistas-mass-upload-preview" class="space-y-3">
                    <p class="text-sm text-slate-500">Nenhum arquivo selecionado.</p>
                </div>

                <button
                    type="submit"
                    data-upload-action
                    class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                >
                    Enviar conquistas
                </button>
            </form>
        </div>

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

    <script>
        (function () {
            const toggleButton = document.getElementById('conquistas-bulk-toggle');
            const panel = document.getElementById('conquistas-bulk-panel');
            const form = document.getElementById('conquistas-mass-upload-form');
            const fileInput = document.getElementById('conquistas-mass-upload-input');
            const previewContainer = document.getElementById('conquistas-mass-upload-preview');
            const errorContainer = document.getElementById('conquistas-mass-upload-error');
            const submitButton = form?.querySelector('[data-upload-action]');
            const tipos = {!! json_encode($tipos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};
            const tipoKeys = Object.keys(tipos);
            const defaultTipo = tipoKeys[0] ?? '';
            let uploads = [];

            if (!toggleButton || !panel || !form || !fileInput || !previewContainer || !errorContainer || !submitButton) {
                return;
            }

            const normalizeName = (file) => {
                const name = file.name.replace(/\.[^/.]+$/, '');
                return name.replace(/[-_]+/g, ' ').trim() || name;
            };

            toggleButton.addEventListener('click', () => {
                panel.classList.toggle('hidden');
            });

            const renderPreview = () => {
                previewContainer.innerHTML = '';

                if (uploads.length === 0) {
                    previewContainer.innerHTML = '<p class="text-sm text-slate-500">Nenhum arquivo selecionado.</p>';
                    return;
                }

                uploads.forEach((upload, index) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'rounded-xl border border-slate-100 px-4 py-4';

                    const top = document.createElement('div');
                    top.className = 'mb-3 flex flex-wrap items-start gap-3';

                    const img = document.createElement('img');
                    img.className = 'h-14 w-14 rounded-lg object-cover';
                    img.alt = upload.nome;
                    const url = URL.createObjectURL(upload.file);
                    img.src = url;
                    img.onload = () => URL.revokeObjectURL(url);

                    const fileInfo = document.createElement('div');
                    fileInfo.className = 'flex-1 min-w-[180px]';
                    const fileName = document.createElement('p');
                    fileName.className = 'text-xs font-semibold text-slate-700';
                    fileName.textContent = upload.file.name;
                    const fileSize = document.createElement('p');
                    fileSize.className = 'text-xs text-slate-400';
                    fileSize.textContent = `${Math.ceil(upload.file.size / 1024)} KB`;
                    fileInfo.appendChild(fileName);
                    fileInfo.appendChild(fileSize);

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'text-xs font-semibold uppercase text-rose-600 hover:text-rose-800';
                    removeBtn.textContent = 'Remover';
                    removeBtn.addEventListener('click', () => {
                        uploads.splice(index, 1);
                        renderPreview();
                    });

                    top.appendChild(img);
                    top.appendChild(fileInfo);
                    top.appendChild(removeBtn);

                    const fields = document.createElement('div');
                    fields.className = 'grid gap-3 md:grid-cols-2';

                    const createField = (label, input) => {
                        const box = document.createElement('div');
                        const labelEl = document.createElement('label');
                        labelEl.className = 'block text-xs font-semibold text-slate-500';
                        labelEl.textContent = label;
                        box.appendChild(labelEl);
                        box.appendChild(input);
                        return box;
                    };

                    const nomeInput = document.createElement('input');
                    nomeInput.className = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100';
                    nomeInput.value = upload.nome;
                    nomeInput.addEventListener('input', () => {
                        uploads[index].nome = nomeInput.value;
                    });

                    const descricaoInput = document.createElement('textarea');
                    descricaoInput.className = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100';
                    descricaoInput.rows = 3;
                    descricaoInput.value = upload.descricao;
                    descricaoInput.addEventListener('input', () => {
                        uploads[index].descricao = descricaoInput.value;
                    });

                    const tipoSelect = document.createElement('select');
                    tipoSelect.className = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100';
                    tipoKeys.forEach((key) => {
                        const option = document.createElement('option');
                        option.value = key;
                        option.textContent = tipos[key];
                        if (upload.tipo === key) {
                            option.selected = true;
                        }
                        tipoSelect.appendChild(option);
                    });
                    tipoSelect.addEventListener('change', () => {
                        uploads[index].tipo = tipoSelect.value;
                    });

                    const quantidadeInput = document.createElement('input');
                    quantidadeInput.type = 'number';
                    quantidadeInput.min = '1';
                    quantidadeInput.className = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100';
                    quantidadeInput.value = upload.quantidade;
                    quantidadeInput.addEventListener('input', () => {
                        uploads[index].quantidade = quantidadeInput.value;
                    });

                    const fansInput = document.createElement('input');
                    fansInput.type = 'number';
                    fansInput.min = '1';
                    fansInput.className = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100';
                    fansInput.value = upload.fans;
                    fansInput.addEventListener('input', () => {
                        uploads[index].fans = fansInput.value;
                    });

                    fields.appendChild(createField('Nome', nomeInput));
                    fields.appendChild(createField('Tipo', tipoSelect));
                    fields.appendChild(createField('Descricao', descricaoInput));
                    fields.appendChild(createField('Quantidade', quantidadeInput));
                    fields.appendChild(createField('Fans', fansInput));

                    wrapper.appendChild(top);
                    wrapper.appendChild(fields);
                    previewContainer.appendChild(wrapper);
                });
            };

            fileInput.addEventListener('change', (event) => {
                const files = Array.from(event.target.files);
                files.forEach((file) => {
                    uploads.push({
                        file,
                        nome: normalizeName(file),
                        descricao: '',
                        tipo: defaultTipo,
                        quantidade: 1,
                        fans: 1,
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

                const invalid = uploads.find((item) => {
                    return !item.nome?.trim()
                        || !item.descricao?.trim()
                        || !item.tipo
                        || Number(item.quantidade) < 1
                        || Number(item.fans) < 1;
                });

                if (invalid) {
                    errorContainer.textContent = 'Preencha nome, descricao, tipo, quantidade e fans para todas as imagens.';
                    errorContainer.classList.remove('hidden');
                    return;
                }

                errorContainer.classList.add('hidden');
                submitButton.setAttribute('disabled', 'disabled');

                const formData = new FormData();
                formData.append('_token', form.querySelector('input[name="_token"]').value);

                uploads.forEach((upload, index) => {
                    formData.append(`uploads[${index}][nome]`, upload.nome.trim());
                    formData.append(`uploads[${index}][descricao]`, upload.descricao.trim());
                    formData.append(`uploads[${index}][tipo]`, upload.tipo);
                    formData.append(`uploads[${index}][quantidade]`, upload.quantidade);
                    formData.append(`uploads[${index}][fans]`, upload.fans);
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
                        errorContainer.textContent = payload.message || 'Revise os dados e tente novamente.';
                        errorContainer.classList.remove('hidden');
                    } else {
                        throw new Error('Nao foi possivel enviar as conquistas.');
                    }
                } catch (error) {
                    errorContainer.textContent = error.message || 'Erro ao enviar conquistas.';
                    errorContainer.classList.remove('hidden');
                } finally {
                    submitButton.removeAttribute('disabled');
                }
            });
        })();
    </script>
</x-app-layout>
