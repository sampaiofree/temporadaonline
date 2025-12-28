@php
    $modalMode = session('horarios_modal');
    $modalDisponibilidadeId = session('horarios_modal_id');
    $modalDisponibilidade = $modalMode === 'edit'
        ? $disponibilidades->firstWhere('id', $modalDisponibilidadeId)
        : null;
    $modalIsEdit = $modalMode === 'edit' && $modalDisponibilidade;
    $modalAutoOpen = $modalMode === 'create' || $modalIsEdit;
    $modalAction = $modalIsEdit
        ? route('admin.users.horarios.update', [$user, $modalDisponibilidade])
        : route('admin.users.horarios.store', $user);
    $modalMethod = $modalIsEdit ? 'PUT' : 'POST';
    $modalDia = old('dia_semana', $modalDisponibilidade?->dia_semana);
    $modalInicio = old(
        'hora_inicio',
        $modalDisponibilidade?->hora_inicio ? substr($modalDisponibilidade->hora_inicio, 0, 5) : '',
    );
    $modalFim = old(
        'hora_fim',
        $modalDisponibilidade?->hora_fim ? substr($modalDisponibilidade->hora_fim, 0, 5) : '',
    );
    $modalTitle = $modalIsEdit ? 'Editar horário' : 'Adicionar horário';
    $modalSubmit = $modalIsEdit ? 'Salvar alterações' : 'Salvar horário';
@endphp

<x-app-layout title="Horários do usuário">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Horários de disponibilidade</h2>
                <p class="text-sm text-slate-500">
                    Usuário: <span class="font-semibold text-slate-700">{{ $user->name }}</span> · {{ $user->email }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    id="user-horarios-add"
                    data-action="{{ route('admin.users.horarios.store', $user) }}"
                    class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                >
                    Adicionar horário
                </button>
                <a
                    href="{{ route('admin.users.index') }}"
                    class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                >
                    Voltar para usuários
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

        @if($errors->any())
            <div class="rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
                <p class="font-semibold">Não foi possível salvar o horário.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Dia da semana</th>
                            <th class="px-4 py-3 font-semibold">Início</th>
                            <th class="px-4 py-3 font-semibold">Fim</th>
                            <th class="px-4 py-3 font-semibold">Criado em</th>
                            <th class="px-4 py-3 font-semibold">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($disponibilidades as $disponibilidade)
                            @php
                                $inicio = substr($disponibilidade->hora_inicio, 0, 5);
                                $fim = substr($disponibilidade->hora_fim, 0, 5);
                            @endphp
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-4 font-semibold text-slate-900">
                                    {{ $dayLabels[$disponibilidade->dia_semana] ?? '—' }}
                                </td>
                                <td class="px-4 py-4 text-slate-600">{{ $inicio }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $fim }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $disponibilidade->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-4 text-slate-600">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button
                                            type="button"
                                            data-edit-disponibilidade
                                            data-action="{{ route('admin.users.horarios.update', [$user, $disponibilidade]) }}"
                                            data-dia="{{ $disponibilidade->dia_semana }}"
                                            data-inicio="{{ $inicio }}"
                                            data-fim="{{ $fim }}"
                                            class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                                        >
                                            Editar
                                        </button>
                                        <form method="POST" action="{{ route('admin.users.horarios.destroy', [$user, $disponibilidade]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-xl border border-rose-200 px-3 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-50"
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
                                    Nenhum horário registrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

<div
    id="user-horarios-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/40 p-4"
    role="dialog"
    aria-modal="true"
    data-auto-open="{{ $modalAutoOpen ? 'true' : 'false' }}"
    data-auto-action="{{ $modalAction }}"
    data-auto-method="{{ $modalMethod }}"
    data-auto-dia="{{ $modalDia }}"
    data-auto-inicio="{{ $modalInicio }}"
    data-auto-fim="{{ $modalFim }}"
    data-auto-title="{{ $modalTitle }}"
    data-auto-submit="{{ $modalSubmit }}"
>
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <div class="flex items-center justify-between">
            <div>
                <h3 id="user-horarios-modal-title" class="text-lg font-semibold text-slate-900">Adicionar horário</h3>
                <p class="text-sm text-slate-500">Configure dia da semana e horário.</p>
            </div>
            <button
                type="button"
                data-close-user-horarios-modal
                class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500 transition hover:border-slate-300 hover:text-slate-900"
            >
                Fechar
            </button>
        </div>

        <form
            id="user-horarios-form"
            method="POST"
            action="{{ route('admin.users.horarios.store', $user) }}"
            class="mt-6 space-y-4"
        >
            @csrf
            <input type="hidden" name="_method" id="user-horarios-method" value="POST">
            <div>
                <label for="user-horarios-dia" class="text-sm font-semibold text-slate-700">Dia da semana</label>
                <select
                    id="user-horarios-dia"
                    name="dia_semana"
                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    required
                >
                    <option value="">Selecione</option>
                    @foreach($dayLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="user-horarios-inicio" class="text-sm font-semibold text-slate-700">Hora início</label>
                <input
                    id="user-horarios-inicio"
                    name="hora_inicio"
                    type="time"
                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    required
                >
            </div>
            <div>
                <label for="user-horarios-fim" class="text-sm font-semibold text-slate-700">Hora fim</label>
                <input
                    id="user-horarios-fim"
                    name="hora_fim"
                    type="time"
                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    required
                >
            </div>
            <div class="flex items-center justify-end gap-3 pt-2">
                <button
                    type="button"
                    data-close-user-horarios-modal
                    class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    id="user-horarios-modal-submit"
                    class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300"
                >
                    <span id="user-horarios-modal-submit-label">Salvar horário</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const addButton = document.getElementById('user-horarios-add');
        const modal = document.getElementById('user-horarios-modal');
        const form = document.getElementById('user-horarios-form');
        const methodInput = document.getElementById('user-horarios-method');
        const title = document.getElementById('user-horarios-modal-title');
        const submitLabel = document.getElementById('user-horarios-modal-submit-label');
        const diaSelect = document.getElementById('user-horarios-dia');
        const inicioInput = document.getElementById('user-horarios-inicio');
        const fimInput = document.getElementById('user-horarios-fim');

        if (! modal || ! form || ! methodInput || ! title || ! submitLabel || ! diaSelect || ! inicioInput || ! fimInput) {
            return;
        }

        const openModal = ({ action, method, dia, inicio, fim, titleText, submitText }) => {
            form.setAttribute('action', action);
            methodInput.value = method;
            title.textContent = titleText;
            submitLabel.textContent = submitText;
            diaSelect.value = dia ?? '';
            inicioInput.value = inicio ?? '';
            fimInput.value = fim ?? '';
            modal.classList.remove('hidden');
            diaSelect.focus();
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            form.reset();
            methodInput.value = 'POST';
        };

        addButton?.addEventListener('click', () => {
            openModal({
                action: addButton.dataset.action,
                method: 'POST',
                titleText: 'Adicionar horário',
                submitText: 'Salvar horário',
            });
        });

        document.querySelectorAll('[data-edit-disponibilidade]').forEach((button) => {
            button.addEventListener('click', () => {
                openModal({
                    action: button.dataset.action,
                    method: 'PUT',
                    dia: button.dataset.dia,
                    inicio: button.dataset.inicio,
                    fim: button.dataset.fim,
                    titleText: 'Editar horário',
                    submitText: 'Salvar alterações',
                });
            });
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.querySelectorAll('[data-close-user-horarios-modal]').forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && ! modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        if (modal.dataset.autoOpen === 'true') {
            openModal({
                action: modal.dataset.autoAction,
                method: modal.dataset.autoMethod,
                dia: modal.dataset.autoDia,
                inicio: modal.dataset.autoInicio,
                fim: modal.dataset.autoFim,
                titleText: modal.dataset.autoTitle,
                submitText: modal.dataset.autoSubmit,
            });
        }
    });
</script>
