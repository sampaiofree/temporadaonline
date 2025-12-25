@php
    use Illuminate\Support\Facades\Storage;
@endphp

<x-app-layout title="Escudos de ligas">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Escudos de ligas</h2>
                <p class="text-sm text-slate-500">Gerencie os escudos vinculados aos países e faça upload em massa.</p>
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
                    <p class="text-sm text-slate-500">Selecione uma ou mais imagens, escolha o país padrão e confirme os dados.</p>
                </div>
            </div>

            <form
                id="ligas-escudos-mass-upload-form"
                action="{{ route('admin.ligas-escudos.store') }}"
                method="POST"
                enctype="multipart/form-data"
                class="space-y-5 pt-4"
            >
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label for="ligas-escudos-default-pais" class="text-sm font-semibold text-slate-700">País padrão</label>
                        <select
                            id="ligas-escudos-default-pais"
                            name="default_pais_id"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">Selecione um país</option>
                            @foreach ($paises as $pais)
                                <option value="{{ $pais->id }}">{{ $pais->nome }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-400">O país escolhido será aplicado às entradas sem seleção manual.</p>
                    </div>
                    <div class="space-y-2">
                        <label for="ligas-escudos-mass-upload-input" class="text-sm font-semibold text-slate-700">Imagens das ligas</label>
                        <input
                            id="ligas-escudos-mass-upload-input"
                            type="file"
                            multiple
                            accept="image/*"
                            class="w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-4 text-sm text-slate-500 transition hover:border-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                        <p class="text-xs text-slate-400">O nome do arquivo vira o nome sugestivo da liga, mas você pode alterar no preview.</p>
                    </div>
                </div>

                <div id="ligas-escudos-mass-upload-error" class="hidden rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-sm text-red-700"></div>

                <div id="ligas-escudos-mass-upload-preview" class="space-y-3">
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
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Escudos cadastrados</h3>
                    <p class="text-sm text-slate-500">Lista completa por liga e país.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Liga</th>
                            <th class="px-4 py-3 font-semibold">País</th>
                            <th class="px-4 py-3 font-semibold">Criado em</th>
                            <th class="px-4 py-3 font-semibold">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($ligas as $liga)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-4 align-top">
                                    <div class="flex items-center gap-3">
                                        @if($liga->liga_imagem)
                                            <span class="inline-flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl bg-slate-100">
                                                <img
                                                    src="{{ Storage::disk('public')->url($liga->liga_imagem) }}"
                                                    alt="{{ $liga->liga_nome }}"
                                                    class="h-full w-full object-cover"
                                                >
                                            </span>
                                        @else
                                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-xs font-semibold uppercase text-slate-500">
                                                {{ strtoupper(substr($liga->liga_nome, 0, 2)) }}
                                            </span>
                                        @endif
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">{{ $liga->liga_nome }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $liga->pais?->nome }}</td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $liga->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex flex-wrap gap-2">
                                        <a
                                            href="{{ route('admin.ligas-escudos.edit', $liga) }}"
                                            class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                                        >
                                            Editar
                                        </a>
                                        <form action="{{ route('admin.ligas-escudos.destroy', $liga) }}" method="POST" class="inline">
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
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">
                                    Ainda não existem escudos cadastrados.
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
            const form = document.getElementById('ligas-escudos-mass-upload-form');
            if (! form) {
                return;
            }

            const countries = @json($paises->map(fn($pais) => ['id' => $pais->id, 'nome' => $pais->nome]));
            const fileInput = document.getElementById('ligas-escudos-mass-upload-input');
            const defaultPaisSelect = document.getElementById('ligas-escudos-default-pais');
            const previewContainer = document.getElementById('ligas-escudos-mass-upload-preview');
            const errorContainer = document.getElementById('ligas-escudos-mass-upload-error');
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
                    img.alt = upload.ligaNome;
                    const url = URL.createObjectURL(upload.file);
                    img.src = url;
                    img.onload = () => URL.revokeObjectURL(url);

                    const fieldset = document.createElement('div');
                    fieldset.className = 'flex-1 min-w-[160px] space-y-2';

                    const nameLabel = document.createElement('label');
                    nameLabel.className = 'block text-xs font-semibold text-slate-500';
                    nameLabel.textContent = 'Nome da liga';

                    const nameInput = document.createElement('input');
                    nameInput.className = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100';
                    nameInput.value = upload.ligaNome;
                    nameInput.dataset.index = index;
                    nameInput.addEventListener('input', () => {
                        uploads[index].ligaNome = nameInput.value;
                    });

                    const selectLabel = document.createElement('label');
                    selectLabel.className = 'block text-xs font-semibold text-slate-500';
                    selectLabel.textContent = 'País';

                    const countrySelect = document.createElement('select');
                    countrySelect.className = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100';
                    countrySelect.dataset.index = index;
                    countries.forEach((country) => {
                        const option = document.createElement('option');
                        option.value = country.id;
                        option.textContent = country.nome;
                        if (upload.paisId === country.id) {
                            option.selected = true;
                        }
                        countrySelect.appendChild(option);
                    });

                    countrySelect.addEventListener('change', () => {
                        uploads[index].paisId = parseInt(countrySelect.value, 10);
                        uploads[index].useDefault = false;
                    });

                    fieldset.appendChild(nameLabel);
                    fieldset.appendChild(nameInput);
                    fieldset.appendChild(selectLabel);
                    fieldset.appendChild(countrySelect);

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
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

            const applyDefaultPais = (value) => {
                if (! value) {
                    return;
                }

                const parsed = parseInt(value, 10);
                uploads = uploads.map((upload) => {
                    if (upload.useDefault || ! upload.paisId) {
                        return { ...upload, paisId: parsed, useDefault: true };
                    }

                    return upload;
                });
                renderPreview();
            };

            defaultPaisSelect.addEventListener('change', (event) => {
                applyDefaultPais(event.target.value);
            });

            fileInput.addEventListener('change', (event) => {
                const files = Array.from(event.target.files);
                const defaultPaisId = defaultPaisSelect.value ? parseInt(defaultPaisSelect.value, 10) : null;

                files.forEach((file) => {
                    uploads.push({
                        file,
                        ligaNome: normalizeName(file),
                        paisId: defaultPaisId,
                        useDefault: !! defaultPaisId,
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

                const missingPais = uploads.some((upload) => ! upload.paisId);
                if (missingPais) {
                    errorContainer.textContent = 'Informe um país para cada escudo.';
                    errorContainer.classList.remove('hidden');
                    return;
                }

                errorContainer.classList.add('hidden');
                submitButton.setAttribute('disabled', 'disabled');

                const formData = new FormData();
                formData.append('_token', form.querySelector('input[name=\"_token\"]').value);

                uploads.forEach((upload, index) => {
                    formData.append(`uploads[${index}][liga_nome]`, upload.ligaNome);
                    formData.append(`uploads[${index}][pais_id]`, upload.paisId);
                    formData.append(`uploads[${index}][liga_imagem]`, upload.file);
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
</x-app-layout>
