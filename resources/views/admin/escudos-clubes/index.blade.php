@php
    use Illuminate\Support\Facades\Storage;
@endphp

<x-app-layout title="Escudos de clubes">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Escudos de clubes</h2>
                <p class="text-sm text-slate-500">Associe clubes a países e ligas, com upload em massa.</p>
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
                    <p class="text-sm text-slate-500">Defina país e liga padrão, depois confirme cada clube.</p>
                </div>
            </div>

            <form
                id="escudos-clubes-mass-upload-form"
                action="{{ route('admin.escudos-clubes.store') }}"
                method="POST"
                enctype="multipart/form-data"
                class="space-y-5 pt-4"
            >
                @csrf

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="space-y-2">
                        <label for="escudos-clubes-default-pais" class="text-sm font-semibold text-slate-700">País padrão</label>
                        <select
                            id="escudos-clubes-default-pais"
                            name="default_pais_id"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">Selecione um país</option>
                            @foreach ($paises as $pais)
                                <option value="{{ $pais->id }}">{{ $pais->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label for="escudos-clubes-default-liga" class="text-sm font-semibold text-slate-700">Liga padrão</label>
                        <select
                            id="escudos-clubes-default-liga"
                            name="default_liga_id"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">Selecione uma liga</option>
                            @foreach ($ligas as $liga)
                                <option value="{{ $liga->id }}">{{ $liga->liga_nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label for="escudos-clubes-mass-upload-input" class="text-sm font-semibold text-slate-700">Imagens dos clubes</label>
                        <input
                            id="escudos-clubes-mass-upload-input"
                            type="file"
                            multiple
                            accept="image/*"
                            class="w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-4 text-sm text-slate-500 transition hover:border-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                        <p class="text-xs text-slate-400">O nome do arquivo vira o nome do clube e pode ser editado depois.</p>
                    </div>
                </div>

                <div id="escudos-clubes-mass-upload-error" class="hidden rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-sm text-red-700"></div>

                <div id="escudos-clubes-mass-upload-preview" class="space-y-3">
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
                    <p class="text-sm text-slate-500">Lista por clube, liga e país.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Clube</th>
                            <th class="px-4 py-3 font-semibold">Liga</th>
                            <th class="px-4 py-3 font-semibold">País</th>
                            <th class="px-4 py-3 font-semibold">Criado em</th>
                            <th class="px-4 py-3 font-semibold">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($escudos as $escudo)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-4 align-top">
                                    <div class="flex items-center gap-3">
                                        @if($escudo->clube_imagem)
                                            <span class="inline-flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl bg-slate-100">
                                                <img
                                                    src="{{ Storage::disk('public')->url($escudo->clube_imagem) }}"
                                                    alt="{{ $escudo->clube_nome }}"
                                                    class="h-full w-full object-cover"
                                                >
                                            </span>
                                        @else
                                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-xs font-semibold uppercase text-slate-500">
                                                {{ strtoupper(substr($escudo->clube_nome, 0, 2)) }}
                                            </span>
                                        @endif
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">{{ $escudo->clube_nome }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $escudo->liga?->liga_nome }}</td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $escudo->pais?->nome }}</td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $escudo->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex flex-wrap gap-2">
                                        <a
                                            href="{{ route('admin.escudos-clubes.edit', $escudo) }}"
                                            class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                                        >
                                            Editar
                                        </a>
                                        <form action="{{ route('admin.escudos-clubes.destroy', $escudo) }}" method="POST" class="inline">
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
                                    Ainda não existem escudos de clubes cadastrados.
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
            const form = document.getElementById('escudos-clubes-mass-upload-form');
            if (! form) {
                return;
            }

            const countries = @json($paises->map(fn($pais) => ['id' => $pais->id, 'nome' => $pais->nome]));
            const leagues = @json($ligas->map(fn($liga) => ['id' => $liga->id, 'nome' => $liga->liga_nome]));
            const fileInput = document.getElementById('escudos-clubes-mass-upload-input');
            const defaultPaisSelect = document.getElementById('escudos-clubes-default-pais');
            const defaultLigaSelect = document.getElementById('escudos-clubes-default-liga');
            const previewContainer = document.getElementById('escudos-clubes-mass-upload-preview');
            const errorContainer = document.getElementById('escudos-clubes-mass-upload-error');
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
                    img.alt = upload.clubeNome;
                    const url = URL.createObjectURL(upload.file);
                    img.src = url;
                    img.onload = () => URL.revokeObjectURL(url);

                    const fieldset = document.createElement('div');
                    fieldset.className = 'flex-1 min-w-[200px] space-y-2';

                    const nameLabel = document.createElement('label');
                    nameLabel.className = 'block text-xs font-semibold text-slate-500';
                    nameLabel.textContent = 'Nome do clube';

                    const nameInput = document.createElement('input');
                    nameInput.className = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100';
                    nameInput.value = upload.clubeNome;
                    nameInput.dataset.index = index;
                    nameInput.addEventListener('input', () => {
                        uploads[index].clubeNome = nameInput.value;
                    });

                    const countryLabel = document.createElement('label');
                    countryLabel.className = 'block text-xs font-semibold text-slate-500';
                    countryLabel.textContent = 'País';

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
                        uploads[index].useDefaultPais = false;
                    });

                    const leagueLabel = document.createElement('label');
                    leagueLabel.className = 'block text-xs font-semibold text-slate-500';
                    leagueLabel.textContent = 'Liga';

                    const leagueSelect = document.createElement('select');
                    leagueSelect.className = countrySelect.className;
                    leagueSelect.dataset.index = index;
                    leagues.forEach((league) => {
                        const option = document.createElement('option');
                        option.value = league.id;
                        option.textContent = league.nome;
                        if (upload.ligaId === league.id) {
                            option.selected = true;
                        }
                        leagueSelect.appendChild(option);
                    });
                    leagueSelect.addEventListener('change', () => {
                        uploads[index].ligaId = parseInt(leagueSelect.value, 10);
                        uploads[index].useDefaultLiga = false;
                    });

                    fieldset.appendChild(nameLabel);
                    fieldset.appendChild(nameInput);
                    fieldset.appendChild(countryLabel);
                    fieldset.appendChild(countrySelect);
                    fieldset.appendChild(leagueLabel);
                    fieldset.appendChild(leagueSelect);

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

            const applyDefault = () => {
                const paisValue = defaultPaisSelect.value ? parseInt(defaultPaisSelect.value, 10) : null;
                const ligaValue = defaultLigaSelect.value ? parseInt(defaultLigaSelect.value, 10) : null;

                uploads = uploads.map((upload) => ({
                    ...upload,
                    paisId: upload.useDefaultPais ? paisValue : upload.paisId,
                    ligaId: upload.useDefaultLiga ? ligaValue : upload.ligaId,
                    useDefaultPais: paisValue && upload.useDefaultPais ? true : upload.useDefaultPais,
                    useDefaultLiga: ligaValue && upload.useDefaultLiga ? true : upload.useDefaultLiga,
                }));

                renderPreview();
            };

            defaultPaisSelect.addEventListener('change', (event) => {
                const value = event.target.value;
                if (! value) {
                    uploads = uploads.map((upload) => ({ ...upload, useDefaultPais: false }));
                    renderPreview();
                    return;
                }
                uploads = uploads.map((upload) => ({
                    ...upload,
                    paisId: value ? parseInt(value, 10) : upload.paisId,
                    useDefaultPais: true,
                }));
                renderPreview();
            });

            defaultLigaSelect.addEventListener('change', (event) => {
                const value = event.target.value;
                if (! value) {
                    uploads = uploads.map((upload) => ({ ...upload, useDefaultLiga: false }));
                    renderPreview();
                    return;
                }
                uploads = uploads.map((upload) => ({
                    ...upload,
                    ligaId: value ? parseInt(value, 10) : upload.ligaId,
                    useDefaultLiga: true,
                }));
                renderPreview();
            });

            fileInput.addEventListener('change', (event) => {
                const files = Array.from(event.target.files);
                const defaultPaisId = defaultPaisSelect.value ? parseInt(defaultPaisSelect.value, 10) : null;
                const defaultLigaId = defaultLigaSelect.value ? parseInt(defaultLigaSelect.value, 10) : null;

                files.forEach((file) => {
                    uploads.push({
                        file,
                        clubeNome: normalizeName(file),
                        paisId: defaultPaisId,
                        ligaId: defaultLigaId,
                        useDefaultPais: !! defaultPaisId,
                        useDefaultLiga: !! defaultLigaId,
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
                const missingLiga = uploads.some((upload) => ! upload.ligaId);

                if (missingPais || missingLiga) {
                    errorContainer.textContent = 'Informe país e liga para cada escudo.';
                    errorContainer.classList.remove('hidden');
                    return;
                }

                errorContainer.classList.add('hidden');
                submitButton.setAttribute('disabled', 'disabled');

                const formData = new FormData();
                formData.append('_token', form.querySelector('input[name=\"_token\"]').value);

                uploads.forEach((upload, index) => {
                    formData.append(`uploads[${index}][clube_nome]`, upload.clubeNome);
                    formData.append(`uploads[${index}][pais_id]`, upload.paisId);
                    formData.append(`uploads[${index}][liga_id]`, upload.ligaId);
                    formData.append(`uploads[${index}][clube_imagem]`, upload.file);
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
