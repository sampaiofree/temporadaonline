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
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    id="premiacoes-bulk-toggle"
                    class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-300"
                >
                    Upload em massa
                </button>
                <a
                    href="{{ route('admin.premiacoes.create') }}"
                    class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500"
                >
                    Nova premiação
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

        <div id="premiacoes-bulk-panel" class="hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Upload em massa de premiações</h3>
                    <p class="text-sm text-slate-500">Selecione as imagens e preencha posição e valor da premiação antes de enviar.</p>
                </div>
            </div>

            <form
                id="premiacoes-mass-upload-form"
                action="{{ route('admin.premiacoes.bulk-store') }}"
                method="POST"
                enctype="multipart/form-data"
                class="space-y-5 pt-4"
            >
                @csrf

                <div class="space-y-2">
                    <label for="premiacoes-mass-upload-input" class="text-sm font-semibold text-slate-700">Imagens das premiações</label>
                    <input
                        id="premiacoes-mass-upload-input"
                        type="file"
                        multiple
                        accept="image/*"
                        class="w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-4 text-sm text-slate-500 transition hover:border-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                    <p class="text-xs text-slate-400">Formatos aceitos: JPG, PNG ou WEBP (max 4MB por arquivo).</p>
                </div>

                <div id="premiacoes-mass-upload-error" class="hidden rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-sm text-red-700"></div>

                <div id="premiacoes-mass-upload-preview" class="space-y-3">
                    <p class="text-sm text-slate-500">Nenhum arquivo selecionado.</p>
                </div>

                <button
                    type="submit"
                    data-upload-action
                    class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                >
                    Enviar premiações
                </button>
            </form>
        </div>

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

    <script>
        (function () {
            const toggleButton = document.getElementById('premiacoes-bulk-toggle');
            const panel = document.getElementById('premiacoes-bulk-panel');
            const form = document.getElementById('premiacoes-mass-upload-form');
            const fileInput = document.getElementById('premiacoes-mass-upload-input');
            const previewContainer = document.getElementById('premiacoes-mass-upload-preview');
            const errorContainer = document.getElementById('premiacoes-mass-upload-error');
            const submitButton = form?.querySelector('[data-upload-action]');
            let uploads = [];

            if (!toggleButton || !panel || !form || !fileInput || !previewContainer || !errorContainer || !submitButton) {
                return;
            }

            toggleButton.addEventListener('click', () => {
                panel.classList.toggle('hidden');
            });

            const nextPosicao = () => {
                const max = uploads.reduce((carry, item) => Math.max(carry, Number(item.posicao) || 0), 0);
                return max + 1;
            };

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
                    img.alt = `Premiação ${upload.posicao}`;
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

                    const posicaoInput = document.createElement('input');
                    posicaoInput.type = 'number';
                    posicaoInput.min = '1';
                    posicaoInput.className = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100';
                    posicaoInput.value = upload.posicao;
                    posicaoInput.addEventListener('input', () => {
                        uploads[index].posicao = posicaoInput.value;
                    });

                    const premiacaoInput = document.createElement('input');
                    premiacaoInput.type = 'number';
                    premiacaoInput.min = '1';
                    premiacaoInput.className = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100';
                    premiacaoInput.value = upload.premiacao;
                    premiacaoInput.addEventListener('input', () => {
                        uploads[index].premiacao = premiacaoInput.value;
                    });

                    fields.appendChild(createField('Posição', posicaoInput));
                    fields.appendChild(createField('Valor da premiação', premiacaoInput));

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
                        posicao: nextPosicao(),
                        premiacao: 1,
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

                const usedPosicoes = new Set();
                const invalid = uploads.find((item) => {
                    const posicao = Number(item.posicao);
                    const premio = Number(item.premiacao);
                    if (posicao < 1 || premio < 1) {
                        return true;
                    }
                    if (usedPosicoes.has(posicao)) {
                        return true;
                    }
                    usedPosicoes.add(posicao);
                    return false;
                });

                if (invalid) {
                    errorContainer.textContent = 'Preencha posições únicas e valores maiores que zero para todos os itens.';
                    errorContainer.classList.remove('hidden');
                    return;
                }

                errorContainer.classList.add('hidden');
                submitButton.setAttribute('disabled', 'disabled');

                const formData = new FormData();
                formData.append('_token', form.querySelector('input[name="_token"]').value);

                uploads.forEach((upload, index) => {
                    formData.append(`uploads[${index}][posicao]`, upload.posicao);
                    formData.append(`uploads[${index}][premiacao]`, upload.premiacao);
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
                        throw new Error('Nao foi possivel enviar as premiacoes.');
                    }
                } catch (error) {
                    errorContainer.textContent = error.message || 'Erro ao enviar premiacoes.';
                    errorContainer.classList.remove('hidden');
                } finally {
                    submitButton.removeAttribute('disabled');
                }
            });
        })();
    </script>
</x-app-layout>
