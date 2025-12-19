import { useState } from 'react';
import Navbar from '../components/app_publico/Navbar';
import backgroundDefault from '../../../storage/app/public/app/background/fundopadrao.jpg';
import backgroundVertical from '../../../storage/app/public/app/background/fundopadrao.jpg';

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getClubeFromWindow = () => window.__CLUBE__ ?? null;
const getPartidasFromWindow = () =>
    window.__PARTIDAS__ ?? { minhas_partidas: [], todas_partidas: [] };

export default function LigaPartidas() {
    const liga = getLigaFromWindow();
    const clube = getClubeFromWindow();
    const ligaTimezone =
        liga?.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
    const [partidasState, setPartidasState] = useState(getPartidasFromWindow());
    const [activeTab, setActiveTab] = useState('minhas');
    const [modal, setModal] = useState({ type: null, partida: null });
    const [optionsState, setOptionsState] = useState({ loading: false, opcoes: [], sugestoes: [] });
    const [selectedOptions, setSelectedOptions] = useState([]);
    const [selectedAlterSlot, setSelectedAlterSlot] = useState('');
    const [manualAlterDatetime, setManualAlterDatetime] = useState('');
    const [modalError, setModalError] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [placarForm, setPlacarForm] = useState({ mandante: '', visitante: '' });
    const [denunciaForm, setDenunciaForm] = useState({ motivo: '', descricao: '' });

    const backgroundStyles = {
        '--mco-cover': `url(${backgroundDefault})`,
        '--mco-cover-mobile': `url(${backgroundVertical})`,
    };

    if (!liga) {
        return (
            <main className="liga-partidas-screen" style={backgroundStyles}>
                <p className="ligas-empty">Liga indisponível. Volte para o painel.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    const tabContent =
        activeTab === 'minhas' ? partidasState.minhas_partidas : partidasState.todas_partidas;

    const estadoLabels = {
        agendada: 'Agendada',
        confirmacao_necessaria: 'Confirmação pendente',
        confirmada: 'Confirmada',
        em_andamento: 'Em andamento',
        finalizada: 'Finalizada',
        wo: 'W.O',
        cancelada: 'Cancelada',
    };

    const estadoClass = {
        confirmacao_necessaria: 'warning',
        confirmada: 'success',
        em_andamento: 'info',
        finalizada: 'muted',
        wo: 'danger',
        cancelada: 'muted',
        agendada: 'info',
    };

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

    const ellipsize = (text, limit) => {
        if (text.length <= limit) return text;
        if (limit <= 1) return '…';
        return `${text.slice(0, limit - 1)}…`;
    };

    const truncateClubNames = (mandanteName, visitanteName, maxTotal = 10) => {
        const a = mandanteName || '—';
        const b = visitanteName || '—';
        const spaceBuffer = 1; // espaço entre nomes (vs é separado)
        const currentTotal = a.length + b.length + spaceBuffer;
        if (currentTotal <= maxTotal) {
            return { mandante: a, visitante: b };
        }

        const available = Math.max(4, maxTotal - spaceBuffer); // pelo menos 2 + 2
        const ratio = a.length / (a.length + b.length || 1);
        let maxA = Math.max(2, Math.floor(available * ratio));
        let maxB = Math.max(2, available - maxA);

        if (maxA + maxB < available) {
            maxB += available - (maxA + maxB);
        }

        return {
            mandante: ellipsize(a, maxA),
            visitante: ellipsize(b, maxB),
        };
    };

    const isParticipant = (partida) =>
        clube &&
        (Number(partida.mandante_id) === Number(clube.id) ||
            Number(partida.visitante_id) === Number(clube.id));

    const isMandante = (partida) => clube && Number(partida.mandante_id) === Number(clube.id);

    const alreadyCheckedIn = (partida) => {
        if (!clube) return false;
        if (isMandante(partida)) return Boolean(partida.checkin_mandante_at);
        return Boolean(partida.checkin_visitante_at);
    };

    const withinCheckinWindow = (partida) => {
        if (!partida.scheduled_at) return false;
        const now = new Date();
        const start = new Date(partida.scheduled_at);
        start.setMinutes(start.getMinutes() - 30);
        const end = new Date(partida.scheduled_at);
        end.setMinutes(end.getMinutes() + 15);
        return now >= start && now <= end;
    };

    const updatePartida = (partidaId, updater) => {
        setPartidasState((prev) => {
            const apply = (arr) =>
                arr.map((item) => {
                    if (item.id !== partidaId) return item;
                    const patch = typeof updater === 'function' ? updater(item) : updater;
                    return { ...item, ...patch };
                });

            return {
                minhas_partidas: apply(prev.minhas_partidas),
                todas_partidas: apply(prev.todas_partidas),
            };
        });
    };

    const resetModalState = () => {
        setOptionsState({ loading: false, opcoes: [], sugestoes: [] });
        setSelectedOptions([]);
        setSelectedAlterSlot('');
        setManualAlterDatetime('');
        setModalError('');
        setPlacarForm({ mandante: '', visitante: '' });
        setDenunciaForm({ motivo: '', descricao: '' });
    };

    const openModal = (type, partida) => {
        resetModalState();
        setModal({ type, partida });
        if (type === 'confirm' || type === 'alterar') {
            loadOpcoes(partida.id);
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

    const loadOpcoes = async (partidaId) => {
        setOptionsState((prev) => ({ ...prev, loading: true }));
        setModalError('');

        try {
            const { data } = await window.axios.get(`/api/partidas/${partidaId}/opcoes`);
            setOptionsState({
                loading: false,
                opcoes: data.opcoes ?? [],
                sugestoes: data.sugestoes_alteracao ?? [],
            });
            if (!selectedAlterSlot && (data.sugestoes_alteracao ?? []).length > 0) {
                setSelectedAlterSlot(data.sugestoes_alteracao[0].datetime_utc);
            }
        } catch (error) {
            setModalError('Não foi possível carregar os horários.');
            setOptionsState({ loading: false, opcoes: [], sugestoes: [] });
        }
    };

    const handleConfirmSubmit = async () => {
        if (!modal.partida) return;
        if (selectedOptions.length === 0) {
            setModalError('Selecione ao menos um horário.');
            return;
        }

        setSubmitting(true);
        setModalError('');
        try {
            const { data } = await window.axios.post(
                `/api/partidas/${modal.partida.id}/confirmar-horario`,
                { datetimes: selectedOptions },
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
                error.response?.data?.errors?.datetimes?.[0] ??
                'Não foi possível confirmar.';
            setModalError(message);
        } finally {
            setSubmitting(false);
        }
    };

    const handleAlterarSubmit = async () => {
        if (!modal.partida) return;
        const datetime = selectedAlterSlot || manualAlterDatetime;

        if (!datetime) {
            setModalError('Selecione um horário ou informe data/hora manualmente.');
            return;
        }

        setSubmitting(true);
        setModalError('');
        try {
            const { data } = await window.axios.post(
                `/api/partidas/${modal.partida.id}/alterar-horario`,
                { datetime },
            );

            updatePartida(modal.partida.id, {
                estado: 'agendada',
                scheduled_at: data.scheduled_at ?? datetime,
                sem_slot_disponivel: false,
                forced_by_system: false,
            });
            closeModal();
        } catch (error) {
            const message =
                error.response?.data?.message ??
                error.response?.data?.errors?.datetime?.[0] ??
                'Não foi possível alterar o horário.';
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
        if (!denunciaForm.motivo) {
            setModalError('Selecione um motivo.');
            return;
        }

        setSubmitting(true);
        setModalError('');

        try {
            await window.axios.post(`/api/partidas/${modal.partida.id}/denunciar`, {
                motivo: denunciaForm.motivo,
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

    const renderActions = (partida) => {
        if (!isParticipant(partida)) return null;
        const actions = [];

        if (partida.estado === 'confirmada') {
            if (withinCheckinWindow(partida) && !alreadyCheckedIn(partida)) {
                actions.push({
                    label: 'Check-in',
                    variant: 'primary',
                    onClick: () => openModal('checkin', partida),
                });
            }
        }

        if (partida.estado === 'em_andamento') {
            actions.push({
                label: 'Registrar placar',
                variant: 'primary',
                onClick: () => openModal('placar', partida),
            });
            actions.push({
                label: 'Denunciar',
                variant: 'outline',
                onClick: () => openModal('denuncia', partida),
            });
        }

        if (partida.estado === 'finalizada') {
            actions.push({
                label: 'Ver placar',
                variant: 'neutral',
                onClick: () => openModal('ver-placar', partida),
            });
        }

        if (partida.estado === 'wo') {
            actions.push({
                label: 'Ver W.O',
                variant: 'neutral',
                onClick: () => openModal('ver-wo', partida),
            });
        }

        const limited = actions.slice(0, 2);

        if (limited.length === 0) {
            return null;
        }

        return (
            <div className="partida-actions">
                {limited.map((action) => (
                    <button
                        key={action.label}
                        type="button"
                        onClick={action.onClick}
                        className={`table-action-badge ${action.variant === 'neutral' ? 'neutral' : action.variant}`}
                    >
                        {action.label}
                    </button>
                ))}
            </div>
        );
    };

    const roleBadge = (partida) => {
        if (!clube) return null;
        if (isMandante(partida)) return 'Você é mandante';
        if (Number(partida.visitante_id) === Number(clube.id)) return 'Você é visitante';
        return null;
    };

    const renderModalContent = () => {
        if (!modal.partida) return null;

        const partida = modal.partida;

        if (modal.type === 'confirm') {
            return (
                <div className="meu-elenco-modal">
                    <h3>Confirmar horário</h3>
                    <p className="meu-elenco-modal-description">
                        Selecione os horários em que você pode jogar. Confirmamos quando ambos escolherem o mesmo horário.
                    </p>

                    {optionsState.loading ? (
                        <p>Carregando horários...</p>
                    ) : optionsState.opcoes.length === 0 ? (
                        <div className="partida-option-empty">
                            <p>Você não tem horários disponíveis para confirmar agora.</p>
                            <button
                                type="button"
                                className="btn-primary"
                                onClick={() => (window.location.href = '/perfil#horarios')}
                            >
                                Adicionar horários
                            </button>
                        </div>
                    ) : (
                        <div className="partida-option-list">
                            {optionsState.opcoes.map((option) => (
                                <label key={option.datetime_utc} className="partida-option">
                                    <input
                                        type="checkbox"
                                        checked={selectedOptions.includes(option.datetime_utc)}
                                        onChange={(event) => {
                                            const checked = event.target.checked;
                                            setSelectedOptions((prev) =>
                                                checked
                                                    ? [...prev, option.datetime_utc]
                                                    : prev.filter((item) => item !== option.datetime_utc),
                                            );
                                        }}
                                    />
                                    <span>{formatDate(option.datetime_local)}</span>
                                </label>
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
                            onClick={handleConfirmSubmit}
                            disabled={submitting}
                        >
                            {submitting ? 'Enviando...' : 'Confirmar horários'}
                        </button>
                    </div>
                </div>
            );
        }

        if (modal.type === 'alterar') {
            return (
                <div className="meu-elenco-modal">
                    <h3>Alterar horário</h3>
                    <p className="meu-elenco-modal-description">
                        Escolha uma opção sugerida. A alteração pode exigir nova confirmação do visitante.
                    </p>

                    {optionsState.loading ? (
                        <p>Carregando sugestões...</p>
                    ) : optionsState.sugestoes.length === 0 ? (
                        <p>Nenhuma sugestão disponível agora.</p>
                    ) : (
                        <div className="partida-option-list">
                            {optionsState.sugestoes.map((option) => (
                                <label key={option.datetime_utc} className="partida-option">
                                    <input
                                        type="radio"
                                        name="alter-slot"
                                        checked={selectedAlterSlot === option.datetime_utc}
                                        onChange={() => setSelectedAlterSlot(option.datetime_utc)}
                                    />
                                    <span>{formatDate(option.datetime_local)}</span>
                                </label>
                            ))}
                        </div>
                    )}

                    {modalError && <p className="modal-error">{modalError}</p>}

                    <div className="modal-field">
                        <span>Ou escolher dia e horário</span>
                        <input
                            type="datetime-local"
                            value={manualAlterDatetime}
                            onChange={(e) => setManualAlterDatetime(e.target.value)}
                            aria-label="Escolher dia e horário"
                        />
                        <small style={{ color: '#bfbfbf', textTransform: 'uppercase', letterSpacing: '0.08em' }}>
                            Fuso da liga: {ligaTimezone}
                        </small>
                    </div>

                    <div className="meu-elenco-modal-actions">
                        <button type="button" className="btn-outline" onClick={closeModal} disabled={submitting}>
                            Cancelar
                        </button>
                        <button
                            type="button"
                            className="btn-primary"
                            onClick={handleAlterarSubmit}
                            disabled={submitting}
                        >
                            {submitting ? 'Enviando...' : 'Salvar horário'}
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

        if (modal.type === 'denuncia') {
            return (
                <div className="meu-elenco-modal">
                    <h3>Denunciar</h3>
                    <p className="meu-elenco-modal-description">Selecione o motivo e descreva, se necessário.</p>

                    <div className="partida-option-list">
                        {[
                            { value: 'conduta_antidesportiva', label: 'Conduta antidesportiva' },
                            { value: 'escala_irregular', label: 'Escalação irregular' },
                            { value: 'conexao', label: 'Problema de conexão' },
                            { value: 'outro', label: 'Outro' },
                        ].map((item) => (
                            <label key={item.value} className="partida-option">
                                <input
                                    type="radio"
                                    name="denuncia-motivo"
                                    checked={denunciaForm.motivo === item.value}
                                    onChange={() =>
                                        setDenunciaForm((prev) => ({ ...prev, motivo: item.value }))
                                    }
                                />
                                <span>{item.label}</span>
                            </label>
                        ))}
                    </div>

                    <textarea
                        className="denuncia-textarea"
                        placeholder="Descreva brevemente (opcional)"
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
        <main className="liga-partidas-screen" style={backgroundStyles}>
            <section className="liga-dashboard-hero">
                <p className="ligas-eyebrow">PARTIDAS</p>
                <h1 className="ligas-title">Agenda da liga</h1>
                <p className="ligas-subtitle">
                    {clube
                        ? `Você joga como ${clube.nome}.`
                        : 'Crie um clube para começar a ver as partidas.'}
                </p>
            </section>

            <section className="liga-partidas-tabs">
                <button
                    type="button"
                    className={`filter-pill${activeTab === 'minhas' ? ' active' : ''}`}
                    onClick={() => setActiveTab('minhas')}
                >
                    Minhas partidas
                </button>
                <button
                    type="button"
                    className={`filter-pill${activeTab === 'todas' ? ' active' : ''}`}
                    onClick={() => setActiveTab('todas')}
                >
                    Todas as partidas
                </button>
            </section>

            <section className="liga-partidas-list">
                {tabContent.length === 0 ? (
                    <p className="ligas-empty">Nenhuma partida disponível no momento.</p>
                ) : (
                    tabContent.map((partida) => {
                        const label = estadoLabels[partida.estado] ?? partida.estado;
                        const badgeClass = estadoClass[partida.estado] ?? 'muted';
                        const horario = partida.sem_slot_disponivel
                            ? 'Sem horário disponível'
                            : formatDate(partida.scheduled_at);
                        const role = roleBadge(partida);
                        const canAlter =
                            isMandante(partida) &&
                            ['agendada', 'confirmada', 'confirmacao_necessaria'].includes(partida.estado);
                        const hasScore = partida.estado === 'finalizada' || partida.estado === 'wo';

                        const { mandante: mandanteNome, visitante: visitanteNome } = truncateClubNames(
                            partida.mandante ?? 'Mandante',
                            partida.visitante ?? 'Visitante',
                            14,
                        );
                        const showEstado = !['agendada', 'confirmacao_necessaria'].includes(partida.estado);
                        const canClickHorario =
                            (partida.scheduled_at && canAlter) ||
                            (!partida.scheduled_at && isParticipant(partida) && !partida.sem_slot_disponivel);
                        const horarioLabel = partida.scheduled_at
                            ? formatDate(partida.scheduled_at)
                            : partida.sem_slot_disponivel
                                ? 'Sem horário disponível'
                                : 'Confirmar horário';

                        return (
                            <article key={partida.id} className="partida-card">
                                <div className="partida-header">
                                    <div className="partida-clubes">
                                        <div className="partida-clube-info">
                                            <div className="clube-shield">
                                                {partida.mandante_logo ? (
                                                    <img src={partida.mandante_logo} alt={partida.mandante} />
                                                ) : (
                                                    <span>{getInitials(partida.mandante)}</span>
                                                )}
                                            </div>
                                            <div className="partida-clube-text">
                                                <span className="partida-clube">{mandanteNome ?? 'Mandante indefinido'}</span>
                                                {partida.mandante_nickname && (
                                                    <small className="partida-nickname">{partida.mandante_nickname}</small>
                                                )}
                                            </div>
                                        </div>
                                        <span className="partida-vs">vs</span>
                                        <div className="partida-clube-info">
                                            
                                            
                                            <div className="partida-clube-text">
                                                <span className="partida-clube">{visitanteNome ?? 'Visitante indefinido'}</span>
                                                {partida.visitante_nickname && (
                                                    <small className="partida-nickname">{partida.visitante_nickname}</small>
                                                )}
                                            </div>
                                            <div className="clube-shield">
                                                {partida.visitante_logo ? (
                                                    <img src={partida.visitante_logo} alt={partida.visitante} />
                                                ) : (
                                                    <span>{getInitials(partida.visitante)}</span>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    <div className="partida-header-right">
                                        {role && (
                                            <span className={`partida-role ${isMandante(partida) ? 'mandante' : 'visitante'}`}>
                                                {role}
                                            </span>
                                        )}
                                        {showEstado && <span className={`partida-estado ${badgeClass}`}>{label}</span>}
                                        <button
                                            type="button"
                                            className={`table-action-badge primary partida-horario-badge${canClickHorario ? ' clickable' : ''}`}
                                            onClick={() => {
                                                if (!canClickHorario) return;
                                                if (partida.scheduled_at && canAlter) {
                                                    openModal('alterar', partida);
                                                } else {
                                                    openModal('confirm', partida);
                                                }
                                            }}
                                            disabled={!canClickHorario}
                                        >
                                            {horarioLabel}
                                        </button>
                                    </div>
                                </div>

                                {hasScore && (
                                    <div className="partida-meta">
                                        <div className="partida-meta-item">
                                            <small>Placar</small>
                                            <p>
                                                {partida.placar_mandante ?? '—'} x{' '}
                                                {partida.placar_visitante ?? '—'}
                                            </p>
                                        </div>
                                    </div>
                                )}

                                <div className="partida-flags">
                                    {partida.forced_by_system && (
                                        <span className="partida-chip">Horário forçado pelo sistema</span>
                                    )}
                                    {partida.sem_slot_disponivel && (
                                        <span className="partida-chip warning">
                                            Sem slot futuro disponível (ajuste disponibilidades)
                                        </span>
                                    )}
                                    
                                </div>

                                {renderActions(partida)}
                            </article>
                        );
                    })
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
