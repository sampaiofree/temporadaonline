@props([
    'title' => 'Upload em massa',
    'description' => '',
    'formAction',
    'formId',
    'inputId',
    'previewId',
    'errorId',
    'fileLabel' => 'Imagens',
    'inputHint' => 'Os nomes dos arquivos serão convertidos automaticamente, mas você pode ajustá-los no preview.',
    'submitLabel' => 'Enviar arquivos',
])

<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-4">
        <div>
            <h3 class="text-lg font-semibold text-slate-900">{{ $title }}</h3>
            <p class="text-sm text-slate-500">{{ $description }}</p>
        </div>
    </div>

    <form
        id="{{ $formId }}"
        action="{{ $formAction }}"
        method="POST"
        enctype="multipart/form-data"
        class="space-y-5 pt-4"
    >
        @csrf

        <div class="space-y-2">
            <label for="{{ $inputId }}" class="text-sm font-semibold text-slate-700">{{ $fileLabel }}</label>
            <input
                id="{{ $inputId }}"
                type="file"
                multiple
                accept="image/*"
                class="w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-4 text-sm text-slate-500 transition hover:border-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
            <p class="text-xs text-slate-400">{{ $inputHint }}</p>
        </div>

        <div id="{{ $errorId }}" class="hidden rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-sm text-red-700"></div>

        <div id="{{ $previewId }}" class="space-y-3">
            <p class="text-sm text-slate-500">Nenhum arquivo selecionado.</p>
        </div>

        <button
            type="submit"
            data-upload-action
            class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
        >
            {{ $submitLabel }}
        </button>
    </form>
</div>

<script>
    (function () {
        const form = document.getElementById('{{ $formId }}');
        if (! form) {
            return;
        }

        const fileInput = document.getElementById('{{ $inputId }}');
        const previewContainer = document.getElementById('{{ $previewId }}');
        const errorContainer = document.getElementById('{{ $errorId }}');
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
