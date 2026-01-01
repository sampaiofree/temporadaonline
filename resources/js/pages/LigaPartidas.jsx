import { useEffect, useState } from 'react';
import Navbar from '../components/app_publico/Navbar';
import Alert from '../components/app_publico/Alert';

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getClubeFromWindow = () => window.__CLUBE__ ?? null;
const getPartidasFromWindow = () => {
    const data = window.__PARTIDAS__;
    if (Array.isArray(data)) {
        return data;
    }
    if (data?.partidas && Array.isArray(data.partidas)) {
        return data.partidas;
    }
    return [];
};

export default function LigaPartidas() {
    const liga = getLigaFromWindow();
    const clube = getClubeFromWindow();
    const ligaTimezone =
        liga?.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
    const [partidasState, setPartidasState] = useState(getPartidasFromWindow());
    const [filters, setFilters] = useState({ role: 'all', status: 'all' });
    const [modal, setModal] = useState({ type: null, partida: null });
    const [calendarState, setCalendarState] = useState({ loading: false, days: [] });
    const [selectedSlot, setSelectedSlot] = useState('');
    const [modalError, setModalError] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [placarForm, setPlacarForm] = useState({ mandante: '', visitante: '' });
    const [denunciaForm, setDenunciaForm] = useState({ descricao: '' });
    const [reclamacaoForm, setReclamacaoForm] = useState({ motivo: '', descricao: '', imagem: '' });
    const [avaliacaoForm, setAvaliacaoForm] = useState({ nota: 0 });
    const [successMessage, setSuccessMessage] = useState('');

    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        if (params.get('success') === 'placar-registrado') {
            setSuccessMessage('Placar registrado com sucesso.');
            params.delete('success');
            const query = params.toString();
            const nextUrl = query ? `${window.location.pathname}?${query}` : window.location.pathname;
            window.history.replaceState({}, '', nextUrl);
        }
    }, []);

    if (!liga) {
        return (
            <main className="liga-partidas-screen">
                <p className="ligas-empty">Liga indisponível. Volte para o painel.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    const estadoLabels = {
        agendada: 'Agendada',
        confirmacao_necessaria: 'Confirmação pendente',
        confirmada: 'Confirmada',
        placar_registrado: 'Placar registrado',
        placar_confirmado: 'Placar confirmado',
        em_reclamacao: 'Em reclamação',
        finalizada: 'Finalizada',
        wo: 'W.O',
        cancelada: 'Cancelada',
    };

    const estadoClass = {
        confirmacao_necessaria: 'warning',
        confirmada: 'success',
        placar_registrado: 'warning',
        placar_confirmado: 'success',
        em_reclamacao: 'danger',
        finalizada: 'muted',
        wo: 'danger',
        cancelada: 'muted',
        agendada: 'info',
    };

    const RECLAMACAO_MOTIVOS = [
        { value: 'placar_incorreto', label: 'Placar incorreto' },
        { value: 'wo_indevido', label: 'W.O. indevido' },
        { value: 'queda_conexao', label: 'Queda de conexão' },
        { value: 'outro', label: 'Outro' },
    ];

    const formatDate = (iso) => {
        if (!iso) return 'Aguardando confirmação';
        const date = new Date(iso);
        return date.toLocaleString(undefined, {
            weekday: 'short',
            day: '2-digit',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getInitials = (name) => {
        if (!name) return '';
        const parts = name.split(' ').filter(Boolean);
        if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
        return `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase();
    };

    const chipsForPartida = (partida) => {
        const chips = [];
        if (partida.forced_by_system) chips.push('Horário forçado pelo sistema');
        if (partida.sem_slot_disponivel) chips.push('Sem slot futuro disponível');
        if (partida.estado === 'em_reclamacao') {
            chips.push('Reclamação aberta');
        }
        return chips;
    };

    const isParticipant = (partida) =>
        clube &&
        (Number(partida.mandante_id) === Number(clube.id) ||
            Number(partida.visitante_id) === Number(clube.id));

    const isMandante = (partida) => clube && Number(partida.mandante_id) === Number(clube.id);
    const isVisitante = (partida) => clube && Number(partida.visitante_id) === Number(clube.id);

    const resolveTeamLogo = (partida, side) => {
        const baseLogo = side === 'mandante' ? partida.mandante_logo : partida.visitante_logo;
        if (baseLogo) return baseLogo;
        if (!clube?.escudo_url) return null;
        if (side === 'mandante' && isMandante(partida)) return clube.escudo_url;
        if (side === 'visitante' && isVisitante(partida)) return clube.escudo_url;
        return null;
    };

    const labelWithClub = (labelText, clubName) =>
        clubName ? `${labelText} (${clubName})` : labelText;

    const canDesistir = (partida) => {
        if (!isParticipant(partida)) return false;
        if (partida.estado !== 'confirmada') return false;
        if (!partida.scheduled_at) return false;

        const now = new Date();
        const start = new Date(partida.scheduled_at);
        const limit = new Date(start.getTime() - 60 * 60 * 1000);

        return now < limit;
    };

    const roleFilters = [
        { id: 'all', label: 'Todas' },
        { id: 'mandante', label: labelWithClub('Mandante', clube?.nome) },
        { id: 'visitante', label: labelWithClub('Visitante', clube?.nome) },
    ];

    const statusFilters = [
        { id: 'all', label: 'Todas' },
        { id: 'aguardando', label: 'Aguardando confirmação' },
        { id: 'confirmadas', label: 'Confirmadas' },
        { id: 'finalizadas', label: 'Finalizadas' },
    ];

    const pendingVisitorCount = partidasState.filter(
        (partida) =>
            isVisitante(partida) &&
            partida.estado === 'confirmacao_necessaria' &&
            !partida.scheduled_at,
    ).length;

    const hasActiveFilters = filters.role !== 'all' || filters.status !== 'all';

    const matchesRole = (partida) => {
        if (filters.role === 'all') return true;
        if (filters.role === 'mandante') return isMandante(partida);
        if (filters.role === 'visitante') return isVisitante(partida);
        return true;
    };

    const matchesStatus = (partida) => {
        switch (filters.status) {
            case 'aguardando':
                return partida.estado === 'confirmacao_necessaria';
            case 'confirmadas':
                return ['confirmada', 'agendada'].includes(partida.estado);
            case 'finalizadas':
                return [
                    'finalizada',
                    'placar_confirmado',
                    'placar_registrado',
                    'em_reclamacao',
                    'wo',
                    'cancelada',
                ].includes(partida.estado);
            default:
                return true;
        }
    };

    const filteredPartidas = partidasState.filter(
        (partida) => matchesRole(partida) && matchesStatus(partida),
    );

    const updatePartida = (partidaId, updater) => {
        setPartidasState((prev) =>
            prev.map((item) => {
                if (item.id !== partidaId) return item;
                const patch = typeof updater === 'function' ? updater(item) : updater;
                return { ...item, ...patch };
            }),
        );
    };

    const resetModalState = () => {
        setCalendarState({ loading: false, days: [] });
        setSelectedSlot('');
        setModalError('');
        setPlacarForm({ mandante: '', visitante: '' });
        setDenunciaForm({ descricao: '' });
        setReclamacaoForm({ motivo: '', descricao: '', imagem: '' });
        setAvaliacaoForm({ nota: 0 });
    };

    const openModal = (type, partida) => {
        resetModalState();
        setModal({ type, partida });
        if (type === 'agendar' || type === 'reagendar') {
            loadSlots(partida.id);
        }
        if (type === 'placar' && partida) {
            setPlacarForm({
                mandante: partida.placar_mandante ?? '',
                visitante: partida.placar_visitante ?? '',
            });
        }
    };

    const closeModal = () => {
        setModal({ type: null, partida: null });
        resetModalState();
    };

    const loadSlots = async (partidaId) => {
        setCalendarState((prev) => ({ ...prev, loading: true }));
        setModalError('');

        try {
            const { data } = await window.axios.get(`/api/partidas/${partidaId}/slots`);
            setCalendarState({
                loading: false,
                days: data.days ?? [],
            });
        } catch (error) {
            const message =
                error.response?.data?.message ??
                'Não foi possível carregar os horários.';
            setModalError(message);
            setCalendarState({ loading: false, days: [] });
        }
    };

    const handleScheduleSubmit = async () => {
        if (!modal.partida) return;
        if (!selectedSlot) {
            setModalError('Selecione um horário.');
            return;
        }

        setSubmitting(true);
        setModalError('');
        try {
            const { data } = await window.axios.post(
                `/api/partidas/${modal.partida.id}/agendar`,
                { datetime: selectedSlot },
            );

            updatePartida(modal.partida.id, {
                estado: data.estado ?? modal.partida.estado,
                scheduled_at: data.scheduled_at ?? modal.partida.scheduled_at,
                sem_slot_disponivel: false,
                forced_by_system: false,
            });
            closeModal();
        } catch (error) {
            const message =
                error.response?.data?.message ??
                error.response?.data?.errors?.datetime?.[0] ??
                error.response?.data?.errors?.estado?.[0] ??
                'Não foi possível agendar.';
            setModalError(message);
        } finally {
            setSubmitting(false);
        }
    };

    const handleCheckin = async () => {
        if (!modal.partida) return;
        setSubmitting(true);
        setModalError('');

        try {
            const { data } = await window.axios.post(
                `/api/partidas/${modal.partida.id}/checkin`,
                {},
            );
            updatePartida(modal.partida.id, {
                estado: data.estado ?? modal.partida.estado,
                checkin_mandante_at: data.checkin_mandante_at ?? modal.partida.checkin_mandante_at,
                checkin_visitante_at:
                    data.checkin_visitante_at ?? modal.partida.checkin_visitante_at,
            });
            closeModal();
        } catch (error) {
            const message =
                error.response?.data?.message ??
                error.response?.data?.errors?.checkin?.[0] ??
                'Não foi possível registrar o check-in.';
            setModalError(message);
        } finally {
            setSubmitting(false);
        }
    };

    const handleRegistrarPlacar = async () => {
        if (!modal.partida) return;
        setSubmitting(true);
        setModalError('');

        try {
            const { data } = await window.axios.post(
                `/api/partidas/${modal.partida.id}/registrar-placar`,
                {
                    placar_mandante: Number(placarForm.mandante),
                    placar_visitante: Number(placarForm.visitante),
                },
            );

            updatePartida(modal.partida.id, {
                estado: data.estado ?? 'finalizada',
                placar_mandante: data.placar_mandante,
                placar_visitante: data.placar_visitante,
                placar_registrado_por: clube?.user_id ?? modal.partida.placar_registrado_por,
            });
            closeModal();
        } catch (error) {
            const message =
                error.response?.data?.message ??
                error.response?.data?.errors?.placar_mandante?.[0] ??
                'Não foi possível registrar o placar.';
            setModalError(message);
        } finally {
            setSubmitting(false);
        }
    };

    const handleDenuncia = async () => {
        if (!modal.partida) return;
        if (!denunciaForm.descricao) {
            setModalError('Informe o texto da denuncia.');
            return;
        }

        setSubmitting(true);
        setModalError('');

        try {
            await window.axios.post(`/api/partidas/${modal.partida.id}/denunciar`, {
                descricao: denunciaForm.descricao,
            });

            closeModal();
        } catch (error) {
            const message =
                error.response?.data?.message ??
                error.response?.data?.errors?.motivo?.[0] ??
                'Não foi possível enviar a denúncia.';
            setModalError(message);
        } finally {
            setSubmitting(false);
        }
    };

    const handleDesistir = async () => {
        if (!modal.partida) return;
        setSubmitting(true);
        setModalError('');

        try {
            const { data } = await window.axios.post(`/api/partidas/${modal.partida.id}/desistir`);

            updatePartida(modal.partida.id, {
                estado: data.estado ?? 'wo',
                wo_para_user_id: data.wo_para_user_id,
                wo_motivo: data.wo_motivo,
                placar_mandante: data.placar_mandante,
                placar_visitante: data.placar_visitante,
            });

            closeModal();
        } catch (error) {
            const message =
                error.response?.data?.message ??
                error.response?.data?.errors?.scheduled_at?.[0] ??
                'Não é possível desistir desta partida agora.';
            setModalError(message);
        } finally {
            setSubmitting(false);
        }
    };

    const handleConfirmarPlacar = async () => {
        if (!modal.partida) return;
        setSubmitting(true);
        setModalError('');

        try {
            const { data } = await window.axios.post(
                `/api/partidas/${modal.partida.id}/confirmar-placar`,
            );

            updatePartida(modal.partida.id, {
                estado: data.estado ?? 'placar_confirmado',
                placar_mandante: data.placar_mandante,
                placar_visitante: data.placar_visitante,
            });
            closeModal();
        } catch (error) {
            const message =
                error.response?.data?.errors?.avaliacao?.[0] ??
                error.response?.data?.message ??
                'Não foi possível confirmar o placar.';
            setModalError(message);
        } finally {
            setSubmitting(false);
        }
    };

    const handleRegistrarAvaliacao = async () => {
        if (!modal.partida) return;
        if (!avaliacaoForm.nota) {
            setModalError('Selecione uma nota de 1 a 5.');
            return;
        }

        setSubmitting(true);
        setModalError('');

        try {
            const { data } = await window.axios.post(
                `/api/partidas/${modal.partida.id}/avaliacoes`,
                { nota: Number(avaliacaoForm.nota) },
            );

            updatePartida(modal.partida.id, {
                avaliacao: {
                    nota: data.nota ?? Number(avaliacaoForm.nota),
                    avaliado_user_id: data.avaliado_user_id ?? null,
                },
            });
            closeModal();
        } catch (error) {
            const message =
                error.response?.data?.errors?.avaliacao?.[0] ??
                error.response?.data?.errors?.nota?.[0] ??
                error.response?.data?.message ??
                'Nao foi possivel registrar a avaliacao.';
            setModalError(message);
        } finally {
            setSubmitting(false);
        }
    };

    const handleReclamacao = async () => {
        if (!modal.partida) return;
        if (!reclamacaoForm.motivo || !reclamacaoForm.descricao) {
            setModalError('Informe motivo e descrição.');
            return;
        }

        setSubmitting(true);
        setModalError('');

        try {
            const payload = {
                motivo: reclamacaoForm.motivo,
                descricao: reclamacaoForm.descricao,
                imagem: reclamacaoForm.imagem || null,
            };
            const { data } = await window.axios.post(
                `/api/partidas/${modal.partida.id}/reclamacoes`,
                payload,
            );

            updatePartida(modal.partida.id, {
                estado: data.estado ?? 'em_reclamacao',
            });
            closeModal();
        } catch (error) {
            const message =
                error.response?.data?.message ?? 'Não foi possível registrar a reclamação.';
            setModalError(message);
        } finally {
            setSubmitting(false);
        }
    };

    const renderModalContent = () => {
        if (!modal.partida) return null;

        const partida = modal.partida;

        if (modal.type === 'agendar' || modal.type === 'reagendar') {
            const isReagendar = modal.type === 'reagendar';

            return (
                <div className="meu-elenco-modal">
                    <h3>{isReagendar ? 'Reagendar partida' : 'Agendar partida'}</h3>
                    <p className="meu-elenco-modal-description">
                        Escolha um horário disponível com base na disponibilidade do adversário.
                    </p>
                    <small className="partida-calendar-timezone">Fuso da liga: {ligaTimezone}</small>

                    {calendarState.loading ? (
                        <p>Carregando horários...</p>
                    ) : calendarState.days.length === 0 ? (
                        <div className="partida-option-empty">
                            <p>Nenhum horário disponível no período da liga.</p>
                            <span className="partida-calendar-help">
                                Aguarde o adversário atualizar sua disponibilidade.
                            </span>
                        </div>
                    ) : (
                        <div className="partida-calendar">
                            {calendarState.days.map((day) => (
                                <div key={day.date} className="partida-calendar-day">
                                    <p className="partida-calendar-label">{day.label}</p>
                                    <div className="partida-calendar-slots">
                                        {day.slots.map((slot) => (
                                            <button
                                                key={slot.datetime_utc}
                                                type="button"
                                                className={`partida-slot${selectedSlot === slot.datetime_utc ? ' is-selected' : ''}`}
                                                onClick={() => setSelectedSlot(slot.datetime_utc)}
                                            >
                                                {slot.time_label}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {modalError && <p className="modal-error">{modalError}</p>}

                    <div className="meu-elenco-modal-actions">
                        <button type="button" className="btn-outline" onClick={closeModal} disabled={submitting}>
                            Fechar
                        </button>
                        <button
                            type="button"
                            className="btn-primary"
                            onClick={handleScheduleSubmit}
                            disabled={submitting || !selectedSlot}
                        >
                            {submitting
                                ? 'Enviando...'
                                : isReagendar
                                ? 'Reagendar'
                                : 'Confirmar horário'}
                        </button>
                    </div>
                </div>
            );
        }

        if (modal.type === 'checkin') {
            return (
                <div className="meu-elenco-modal">
                    <h3>Check-in</h3>
                    <p className="meu-elenco-modal-description">
                        Confirme sua presença. Check-in liberado de 30min antes até 15min depois do horário.
                    </p>
                    {modalError && <p className="modal-error">{modalError}</p>}
                    <div className="meu-elenco-modal-actions">
                        <button type="button" className="btn-outline" onClick={closeModal} disabled={submitting}>
                            Cancelar
                        </button>
                        <button
                            type="button"
                            className="btn-primary"
                            onClick={handleCheckin}
                            disabled={submitting}
                        >
                            {submitting ? 'Enviando...' : 'Fazer check-in'}
                        </button>
                    </div>
                </div>
            );
        }

        if (modal.type === 'placar') {
            return (
                <div className="meu-elenco-modal">
                    <h3>Registrar placar</h3>
                    <div className="modal-field">
                        <span>{partida.mandante}</span>
                        <input
                            type="number"
                            min="0"
                            value={placarForm.mandante}
                            onChange={(e) =>
                                setPlacarForm((prev) => ({ ...prev, mandante: e.target.value }))
                            }
                        />
                    </div>
                    <div className="modal-field">
                        <span>{partida.visitante}</span>
                        <input
                            type="number"
                            min="0"
                            value={placarForm.visitante}
                            onChange={(e) =>
                                setPlacarForm((prev) => ({ ...prev, visitante: e.target.value }))
                            }
                        />
                    </div>
                    {modalError && <p className="modal-error">{modalError}</p>}
                    <div className="meu-elenco-modal-actions">
                        <button type="button" className="btn-outline" onClick={closeModal} disabled={submitting}>
                            Cancelar
                        </button>
                        <button
                            type="button"
                            className="btn-primary"
                            onClick={handleRegistrarPlacar}
                            disabled={submitting}
                        >
                            {submitting ? 'Enviando...' : 'Enviar resultado'}
                        </button>
                    </div>
                </div>
            );
        }

        if (modal.type === 'avaliar') {
            const opponentName = isMandante(partida)
                ? partida.visitante_nickname || partida.visitante
                : partida.mandante_nickname || partida.mandante;

            return (
                <div className="meu-elenco-modal">
                    <h3>Avaliar adversario</h3>
                    <p className="meu-elenco-modal-description">
                        Selecione uma nota de 1 a 5 para {opponentName ?? 'o adversario'}.
                    </p>
                    <div className="filter-pill-row filter-pill-row-compact">
                        {[1, 2, 3, 4, 5].map((nota) => (
                            <button
                                key={nota}
                                type="button"
                                className={`filter-pill${avaliacaoForm.nota === nota ? ' active' : ''}`}
                                onClick={() => setAvaliacaoForm({ nota })}
                            >
                                {nota}
                            </button>
                        ))}
                    </div>
                    {modalError && <p className="modal-error">{modalError}</p>}
                    <div className="meu-elenco-modal-actions">
                        <button type="button" className="btn-outline" onClick={closeModal} disabled={submitting}>
                            Cancelar
                        </button>
                        <button
                            type="button"
                            className="btn-primary"
                            onClick={handleRegistrarAvaliacao}
                            disabled={submitting || !avaliacaoForm.nota}
                        >
                            {submitting ? 'Enviando...' : 'Enviar avaliacao'}
                        </button>
                    </div>
                </div>
            );
        }

        if (modal.type === 'confirmar_placar') {
            return (
                <div className="meu-elenco-modal">
                    <h3>Confirmar placar</h3>
                    <p className="meu-elenco-modal-description">
                        O placar será considerado definitivo e passará a valer para a classificação.
                    </p>
                    {modalError && <p className="modal-error">{modalError}</p>}
                    <div className="meu-elenco-modal-actions">
                        <button type="button" className="btn-outline" onClick={closeModal} disabled={submitting}>
                            Cancelar
                        </button>
                        <button
                            type="button"
                            className="btn-primary"
                            onClick={handleConfirmarPlacar}
                            disabled={submitting}
                        >
                            {submitting ? 'Confirmando...' : 'Confirmar placar'}
                        </button>
                    </div>
                </div>
            );
        }

        if (modal.type === 'denuncia') {
            return (
                <div className="meu-elenco-modal">
                    <h3>Denunciar partida</h3>
                    <p className="meu-elenco-modal-description">
                        Descreva o ocorrido para registrar a denuncia da partida.
                    </p>

                    <textarea
                        className="denuncia-textarea"
                        placeholder="Descreva a denuncia"
                        value={denunciaForm.descricao}
                        onChange={(e) =>
                            setDenunciaForm((prev) => ({ ...prev, descricao: e.target.value }))
                        }
                    />

                    {modalError && <p className="modal-error">{modalError}</p>}

                    <div className="meu-elenco-modal-actions">
                        <button type="button" className="btn-outline" onClick={closeModal} disabled={submitting}>
                            Cancelar
                        </button>
                        <button
                            type="button"
                            className="btn-primary"
                            onClick={handleDenuncia}
                            disabled={submitting}
                        >
                            {submitting ? 'Enviando...' : 'Enviar denúncia'}
                        </button>
                    </div>
                </div>
            );
        }

        if (modal.type === 'reclamacao') {
            return (
                <div className="meu-elenco-modal">
                    <h3>Contestar placar</h3>
                    <p className="meu-elenco-modal-description">
                        Abra uma reclamação para que a partida fique congelada até a revisão.
                    </p>
                    <label className="modal-field">
                        <span>Motivo</span>
                        <select
                            value={reclamacaoForm.motivo}
                            onChange={(e) => setReclamacaoForm((prev) => ({ ...prev, motivo: e.target.value }))}
                        >
                            <option value="">Selecione um motivo</option>
                            {RECLAMACAO_MOTIVOS.map((item) => (
                                <option key={item.value} value={item.value}>
                                    {item.label}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="modal-field">
                        <span>Descrição</span>
                        <textarea
                            value={reclamacaoForm.descricao}
                            onChange={(e) => setReclamacaoForm((prev) => ({ ...prev, descricao: e.target.value }))}
                            placeholder="Descreva o motivo (obrigatório)"
                        />
                    </label>
                    <label className="modal-field">
                        <span>Imagem (opcional)</span>
                        <input
                            type="text"
                            value={reclamacaoForm.imagem}
                            onChange={(e) => setReclamacaoForm((prev) => ({ ...prev, imagem: e.target.value }))}
                            placeholder="URL da imagem (opcional)"
                        />
                    </label>
                    {modalError && <p className="modal-error">{modalError}</p>}
                    <div className="meu-elenco-modal-actions">
                        <button type="button" className="btn-outline" onClick={closeModal} disabled={submitting}>
                            Cancelar
                        </button>
                        <button
                            type="button"
                            className="btn-primary"
                            onClick={handleReclamacao}
                            disabled={submitting}
                        >
                            {submitting ? 'Enviando...' : 'Enviar reclamação'}
                        </button>
                    </div>
                </div>
            );
        }

        if (modal.type === 'desistir') {
            return (
                <div className="meu-elenco-modal">
                    <h3>Desistir da partida</h3>
                    <p className="meu-elenco-modal-description">
                        Ao desistir, a partida será encerrada por W.O. e o adversário receberá vitória por 3x0.
                    </p>
                    {modalError && <p className="modal-error">{modalError}</p>}
                    <div className="meu-elenco-modal-actions">
                        <button type="button" className="btn-outline" onClick={closeModal} disabled={submitting}>
                            Voltar
                        </button>
                        <button
                            type="button"
                            className="btn-primary"
                            onClick={handleDesistir}
                            disabled={submitting}
                        >
                            {submitting ? 'Enviando...' : 'Desistir e aplicar W.O.'}
                        </button>
                    </div>
                </div>
            );
        }

        if (modal.type === 'ver-placar') {
            return (
                <div className="meu-elenco-modal">
                    <h3>Placar final</h3>
                    <p className="meu-elenco-modal-description">{formatDate(partida.scheduled_at)}</p>
                    <div className="placar-resumo">
                        <div className="placar-resumo-row">
                            <span>{partida.mandante}</span>
                            <strong>{partida.placar_mandante ?? '-'}</strong>
                        </div>
                        <div className="placar-resumo-row">
                            <span>{partida.visitante}</span>
                            <strong>{partida.placar_visitante ?? '-'}</strong>
                        </div>
                    </div>
                    <div className="meu-elenco-modal-actions">
                        <button type="button" className="btn-outline" onClick={closeModal}>
                            Fechar
                        </button>
                    </div>
                </div>
            );
        }

        if (modal.type === 'ver-wo') {
            return (
                <div className="meu-elenco-modal">
                    <h3>W.O</h3>
                    <p className="meu-elenco-modal-description">
                        Motivo: {modal.partida.wo_motivo ?? 'Não informado'}
                    </p>
                    <div className="meu-elenco-modal-actions">
                        <button type="button" className="btn-outline" onClick={closeModal}>
                            Fechar
                        </button>
                    </div>
                </div>
            );
        }

        return null;
    };

    return (
        <main className="liga-partidas-screen">
            {successMessage && (
                <Alert
                    variant="success"
                    title="Partida finalizada"
                    description={successMessage}
                    floating
                    onClose={() => setSuccessMessage('')}
                />
            )}
            <section className="liga-dashboard-hero">
                <p className="ligas-eyebrow">PARTIDAS</p>
                <h1 className="ligas-title">Agenda da liga</h1>
                <p className="ligas-subtitle">
                    {clube
                        ? `Você joga como ${clube.nome}.`
                        : 'Crie um clube para começar a ver as partidas.'}
                </p>
            </section>

            <section className="liga-partidas-filters">
                <div className="partidas-toolbar">
                    <button
                        type="button"
                        className={`btn-primary partida-confirmar-btn${filters.role === 'visitante' && filters.status === 'aguardando' ? ' active' : ''}`}
                        onClick={() => setFilters({ role: 'visitante', status: 'aguardando' })}
                    >
                        <span>Confirmar partidas</span>
                        <span className="partida-confirmar-count">{pendingVisitorCount}</span>
                    </button>
                    {hasActiveFilters && (
                        <button
                            type="button"
                            className="btn-outline partida-clear-btn"
                            onClick={() => setFilters({ role: 'all', status: 'all' })}
                        >
                            Limpar filtros
                        </button>
                    )}
                </div>
                <div className="partidas-filter-group">
                    <span className="filter-group-label">Papel</span>
                    <div className="filter-pill-row">
                        {roleFilters.map((filter) => (
                            <button
                                key={filter.id}
                                type="button"
                                className={`filter-pill${filters.role === filter.id ? ' active' : ''}`}
                                onClick={() => setFilters((prev) => ({ ...prev, role: filter.id }))}
                            >
                                {filter.label}
                            </button>
                        ))}
                    </div>
                </div>
                <div className="partidas-filter-group">
                    <span className="filter-group-label">Status</span>
                    <div className="filter-pill-row">
                        {statusFilters.map((filter) => (
                            <button
                                key={filter.id}
                                type="button"
                                className={`filter-pill${filters.status === filter.id ? ' active' : ''}`}
                                onClick={() => setFilters((prev) => ({ ...prev, status: filter.id }))}
                            >
                                {filter.label}
                            </button>
                        ))}
                    </div>
                </div>
            </section>

            <section className="liga-partidas-cards">
                {filteredPartidas.length === 0 ? (
                    <p className="ligas-empty">
                        {filters.role === 'visitante' && filters.status === 'aguardando'
                            ? 'Nenhuma partida aguardando confirmação para você.'
                            : 'Você ainda não tem partidas nesta liga.'}
                    </p>
                ) : (
                    <div className="partida-card-grid">
                        {filteredPartidas.map((partida) => {
                            const label = estadoLabels[partida.estado] ?? partida.estado;
                            const badgeClass = estadoClass[partida.estado] ?? 'muted';
                            const horario = partida.sem_slot_disponivel
                                ? 'Sem horário disponível'
                                : formatDate(partida.scheduled_at);
                            const chips = chipsForPartida(partida);
                            const hasAvaliacao = Boolean(partida.avaliacao?.nota);
                            const isRegistrante =
                                clube?.user_id &&
                                Number(partida.placar_registrado_por) === Number(clube.user_id);
                            const canSchedule =
                                isParticipant(partida) &&
                                partida.estado === 'confirmacao_necessaria' &&
                                !partida.scheduled_at;
                            const canReschedule =
                                isParticipant(partida) &&
                                ['confirmada', 'agendada'].includes(partida.estado) &&
                                Boolean(partida.scheduled_at);
                            const canFinalizar =
                                isParticipant(partida) &&
                                ['confirmada'].includes(partida.estado);
                            const canConfirmarPlacar =
                                isParticipant(partida) &&
                                partida.estado === 'placar_registrado' &&
                                !isRegistrante;
                            const canDenunciar =
                                isParticipant(partida) &&
                                partida.estado === 'placar_registrado' &&
                                !isRegistrante;
                            const canAvaliar =
                                isParticipant(partida) &&
                                ['placar_registrado', 'placar_confirmado', 'em_reclamacao', 'finalizada']
                                    .includes(partida.estado) &&
                                !hasAvaliacao;
                            const canWo = canDesistir(partida);
                            const finalizarUrl = `/liga/partidas/${partida.id}/finalizar?liga_id=${liga.id}`;
                            const isFinalizada = [
                                'finalizada',
                                'placar_confirmado',
                                'placar_registrado',
                                'em_reclamacao',
                                'wo',
                                'cancelada',
                            ].includes(partida.estado);
                            const hasPlacar =
                                partida.placar_mandante !== null &&
                                partida.placar_mandante !== undefined &&
                                partida.placar_visitante !== null &&
                                partida.placar_visitante !== undefined;
                            const showPlacar = isFinalizada && hasPlacar;
                            const placarFinal = showPlacar
                                ? `${partida.placar_mandante} x ${partida.placar_visitante}`
                                : null;

                            return (
                                <article key={partida.id} className="partida-card">
                                    <div className="partida-card-head">
                                        <div className="partida-card-team">
                                            <div className="partida-card-shield">
                                                {resolveTeamLogo(partida, 'mandante') ? (
                                                    <img
                                                        src={resolveTeamLogo(partida, 'mandante')}
                                                        alt={partida.mandante}
                                                    />
                                                ) : (
                                                    <span>{getInitials(partida.mandante)}</span>
                                                )}
                                            </div>
                                            <div className="partida-card-team-info">
                                                <strong>{partida.mandante ?? 'Mandante indefinido'}</strong>
                                                <span>{partida.mandante_nickname ?? 'Sem nickname'}</span>
                                            </div>
                                        </div>
                                        <div className="partida-card-vs">
                                            <span>VS</span>
                                        </div>
                                        <div className="partida-card-team">
                                            <div className="partida-card-shield">
                                                {resolveTeamLogo(partida, 'visitante') ? (
                                                    <img
                                                        src={resolveTeamLogo(partida, 'visitante')}
                                                        alt={partida.visitante}
                                                    />
                                                ) : (
                                                    <span>{getInitials(partida.visitante)}</span>
                                                )}
                                            </div>
                                            <div className="partida-card-team-info">
                                                <strong>{partida.visitante ?? 'Visitante indefinido'}</strong>
                                                <span>{partida.visitante_nickname ?? 'Sem nickname'}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="partida-card-meta">
                                        <span className={`partida-estado ${badgeClass}`}>{label}</span>
                                        <span className="partida-card-time">{horario}</span>
                                        {showPlacar && (
                                            <div className="partida-card-score">
                                                <small>Placar final</small>
                                                <strong>{placarFinal}</strong>
                                            </div>
                                        )}
                                    </div>
                                    {chips.length > 0 && (
                                        <div className="partida-card-chips">
                                            {chips.map((chip) => (
                                                <span key={chip} className="partida-chip">
                                                    {chip}
                                                </span>
                                            ))}
                                        </div>
                                    )}
                                    <div className="partida-card-actions">
                                        {canSchedule && (
                                            <button
                                                type="button"
                                                className="btn-primary"
                                                onClick={() => openModal('agendar', partida)}
                                            >
                                                Agendar horário
                                            </button>
                                        )}
                                        {canReschedule && (
                                            <button
                                                type="button"
                                                className="btn-primary"
                                                onClick={() => openModal('reagendar', partida)}
                                            >
                                                Reagendar horário
                                            </button>
                                        )}
                                        {canFinalizar && (
                                            <button
                                                type="button"
                                                className="btn-primary"
                                                onClick={() => window.location.assign(finalizarUrl)}
                                            >
                                                Finalizar partida
                                            </button>
                                        )}
                                        {canAvaliar && (
                                            <button
                                                type="button"
                                                className="btn-outline"
                                                onClick={() => openModal('avaliar', partida)}
                                            >
                                                Avaliar adversario
                                            </button>
                                        )}
                                        {canConfirmarPlacar && (
                                            <button
                                                type="button"
                                                className="btn-primary"
                                                onClick={() => openModal('confirmar_placar', partida)}
                                                disabled={!hasAvaliacao}
                                                title={
                                                    hasAvaliacao
                                                        ? 'Confirmar placar'
                                                        : 'Avalie o adversario para confirmar'
                                                }
                                            >
                                                Confirmar placar
                                            </button>
                                        )}
                                        {canDenunciar && (
                                            <button
                                                type="button"
                                                className="btn-outline"
                                                onClick={() => openModal('denuncia', partida)}
                                            >
                                                Denunciar
                                            </button>
                                        )}
                                        {canWo && (
                                            <button
                                                type="button"
                                                className="btn-outline"
                                                onClick={() => openModal('desistir', partida)}
                                            >
                                                Desistir (W.O.)
                                            </button>
                                        )}
                                        {!canSchedule &&
                                            !canReschedule &&
                                            !canFinalizar &&
                                            !canWo &&
                                            !canAvaliar &&
                                            !canConfirmarPlacar &&
                                            !canDenunciar && (
                                            <span className="partida-card-empty">Sem ações disponíveis.</span>
                                        )}
                                    </div>
                                </article>
                            );
                        })}
                    </div>
                )}
            </section>

            {modal.type && (
                <div className="meu-elenco-modal-overlay" role="dialog" aria-modal="true">
                    {renderModalContent()}
                </div>
            )}

            <Navbar active="ligas" />
        </main>
    );
}
