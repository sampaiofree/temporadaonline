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
    const [openActionsFor, setOpenActionsFor] = useState(null);
    const [reclamacaoForm, setReclamacaoForm] = useState({ motivo: '', descricao: '', imagem: '' });

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
        em_andamento: 'info',
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

    const getWoWinner = (partida) => {
        if (!partida.wo_para_user_id) return null;
        if (partida.wo_para_user_id === partida.mandante_user_id) {
            return partida.mandante_nickname ?? partida.mandante;
        }
        if (partida.wo_para_user_id === partida.visitante_user_id) {
            return partida.visitante_nickname ?? partida.visitante;
        }
        return null;
    };


    const isParticipant = (partida) =>
        clube &&
        (Number(partida.mandante_id) === Number(clube.id) ||
            Number(partida.visitante_id) === Number(clube.id));

    const isMandante = (partida) => clube && Number(partida.mandante_id) === Number(clube.id);

    const isRegistrante = (partida) =>
        clube && Number(partida.placar_registrado_por) === Number(clube.user_id);

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

    const canDesistir = (partida) => {
        if (!isParticipant(partida)) return false;
        const allowed = ['confirmacao_necessaria', 'agendada', 'confirmada'];
        if (!allowed.includes(partida.estado)) return false;
        if (!partida.scheduled_at) return true;

        const now = new Date();
        const start = new Date(partida.scheduled_at);
        const limit = new Date(start.getTime() - 60 * 60 * 1000);

        return now < limit;
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
        setReclamacaoForm({ motivo: '', descricao: '', imagem: '' });
    };

    const openModal = (type, partida) => {
        resetModalState();
        setOpenActionsFor(null);
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
                error.response?.data?.message ?? 'Não foi possível confirmar o placar.';
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

    const getActionItems = (partida) => {
        const actions = [];
        const participant = isParticipant(partida);
        const userId = clube?.user_id;

        const canConfirm =
            participant && partida.estado === 'confirmacao_necessaria';

        const canAlter =
            participant &&
            isMandante(partida) &&
            ['agendada', 'confirmada', 'confirmacao_necessaria'].includes(partida.estado) &&
            Boolean(partida.scheduled_at);

        const canCheckin =
            participant &&
            partida.estado === 'confirmada' &&
            withinCheckinWindow(partida) &&
            !alreadyCheckedIn(partida);

        if (canConfirm) {
            actions.push({
                label: 'Confirmar horário',
                onClick: () => openModal('confirm', partida),
            });
        }

        if (canAlter) {
            actions.push({
                label: 'Alterar horário',
                onClick: () => openModal('alterar', partida),
            });
        }

        if (canCheckin) {
            actions.push({
                label: 'Check-in',
                onClick: () => openModal('checkin', partida),
            });
        }

        if (participant && partida.estado === 'em_andamento') {
            actions.push({
                label: 'Registrar placar',
                onClick: () => openModal('placar', partida),
            });
            actions.push({
                label: 'Denunciar partida',
                onClick: () => openModal('denuncia', partida),
            });
        }

        if (
            participant &&
            partida.estado === 'placar_registrado' &&
            Number(partida.placar_registrado_por) !== Number(userId)
        ) {
            actions.push({
                label: 'Confirmar placar',
                onClick: () => openModal('confirmar_placar', partida),
            });
            actions.push({
                label: 'Contestar placar',
                onClick: () => openModal('reclamacao', partida),
            });
        }

        if (canDesistir(partida)) {
            actions.push({
                label: 'Desistir (W.O.)',
                onClick: () => openModal('desistir', partida),
            });
        }

        if (['placar_confirmado', 'finalizada'].includes(partida.estado)) {
            actions.push({
                label: 'Ver placar',
                onClick: () => openModal('ver-placar', partida),
            });
        }

        if (partida.estado === 'wo') {
            actions.push({
                label: 'Ver W.O',
                onClick: () => openModal('ver-wo', partida),
            });
        }

        return actions;
    };

    const renderActionMenu = (partida) => {
        const items = getActionItems(partida);

        if (items.length === 0) {
            if (partida.estado === 'placar_registrado' && isRegistrante(partida)) {
                return <span style={{ opacity: 0.8 }}>Aguardando confirmação do adversário</span>;
            }
            if (partida.estado === 'em_reclamacao') {
                return <span style={{ opacity: 0.8 }}>Partida em análise</span>;
            }
            return <span style={{ opacity: 0.6 }}>—</span>;
        }

        const isOpen = openActionsFor === partida.id;

        return (
            <div className="actions-menu" style={{ position: 'relative', display: 'inline-block' }}>
                <button
                    type="button"
                    className={`table-action-badge primary${isOpen ? ' active' : ''}`}
                    onClick={() => setOpenActionsFor(isOpen ? null : partida.id)}
                >
                    Ações
                </button>
                {isOpen && (
                    <div
                        className="actions-menu-list"
                        style={{
                            position: 'absolute',
                            right: 0,
                            zIndex: 10,
                            marginTop: 8,
                            minWidth: 200,
                            background: '#0f0f11',
                            border: '1px solid #2b2b32',
                            borderRadius: 8,
                            boxShadow: '0 8px 18px rgba(0, 0, 0, 0.35)',
                            padding: 8,
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 6,
                        }}
                    >
                        {items.map((item) => (
                            <button
                                key={item.label}
                                type="button"
                                className="table-action-badge outline"
                                style={{ width: '100%', textAlign: 'left' }}
                                onClick={() => {
                                    setOpenActionsFor(null);
                                    item.onClick();
                                }}
                            >
                                {item.label}
                            </button>
                        ))}
                    </div>
                )}
            </div>
        );
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
                    onClick={() => {
                        setActiveTab('minhas');
                        setOpenActionsFor(null);
                    }}
                >
                    Minhas partidas
                </button>
                <button
                    type="button"
                    className={`filter-pill${activeTab === 'todas' ? ' active' : ''}`}
                    onClick={() => {
                        setActiveTab('todas');
                        setOpenActionsFor(null);
                    }}
                >
                    Todas as partidas
                </button>
            </section>

            <section className="liga-partidas-table" style={{ marginTop: 20 }}>
                {tabContent.length === 0 ? (
                    <p className="ligas-empty">Nenhuma partida disponível no momento.</p>
                ) : (
                    <div className="mercado-table-wrap" aria-label="Tabela de partidas">
                        <div className="mercado-table-scroll">
                            <table className="mercado-table">
                                <thead>
                                    <tr>
                                        <th>Clube mandante</th>
                                        <th>Clube visitante</th>
                                        <th className="col-compact">Status</th>
                                        <th className="col-compact">Dia da partida</th>
                                        <th className="col-action">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {tabContent.map((partida) => {
                                        const label = estadoLabels[partida.estado] ?? partida.estado;
                                        const badgeClass = estadoClass[partida.estado] ?? 'muted';
                                        const horario = partida.sem_slot_disponivel
                                            ? 'Sem horário disponível'
                                            : formatDate(partida.scheduled_at);
                                        const roleMandanteText = isMandante(partida) ? 'Você mandante' : null;
                                        const roleVisitanteText =
                                            clube && Number(partida.visitante_id) === Number(clube.id)
                                                ? 'Você visitante'
                                                : null;
                                        const chips = chipsForPartida(partida);
                                        const woWinner = getWoWinner(partida);
                                        const registrante = isRegistrante(partida);
                                        const hasScore = [
                                            'finalizada',
                                            'wo',
                                            'placar_registrado',
                                            'placar_confirmado',
                                            'em_reclamacao',
                                        ].includes(partida.estado);
                                        const placarLabel =
                                            partida.placar_mandante !== undefined &&
                                            partida.placar_visitante !== undefined
                                                ? `${partida.placar_mandante ?? '—'} x ${partida.placar_visitante ?? '—'}`
                                                : null;
                                        const isConfirmPending = partida.estado === 'confirmacao_necessaria';
                                        const canOpenStatusConfirm = isConfirmPending && isParticipant(partida);
                                        const statusClassName = `partida-estado ${badgeClass}${canOpenStatusConfirm ? ' clickable' : ''}`;
                                        const statusNote =
                                            partida.estado === 'placar_registrado'
                                                ? registrante
                                                    ? 'Aguardando confirmação do adversário'
                                                    : 'Confirme ou conteste o placar registrado'
                                                : partida.estado === 'em_reclamacao'
                                                ? 'Partida em análise'
                                                : null;

                                        const statusElement = canOpenStatusConfirm ? (
                                            <button
                                                type="button"
                                                className={statusClassName}
                                                onClick={() => openModal('confirm', partida)}
                                            >
                                                {label}
                                            </button>
                                        ) : (
                                            <span className={statusClassName}>{label}</span>
                                        );

                                        return (
                                            <tr key={partida.id}>
                                                <td>
                                                    <div className="partida-clube-info">
                                                        <div className="clube-shield">
                                                            {partida.mandante_logo ? (
                                                                <img src={partida.mandante_logo} alt={partida.mandante} />
                                                            ) : (
                                                                <span>{getInitials(partida.mandante)}</span>
                                                            )}
                                                        </div>
                                                        <div className="partida-clube-text">
                                                            <strong className="partida-clube">
                                                                {partida.mandante ?? 'Mandante indefinido'}
                                                            </strong>
                                                            {partida.mandante_nickname && (
                                                                <small className="partida-nickname">
                                                                    {partida.mandante_nickname}
                                                                </small>
                                                            )}
                                                            {roleMandanteText && (
                                                                <span className="partida-role-pill mandante">
                                                                    {roleMandanteText}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div className="partida-clube-info">
                                                        <div className="clube-shield">
                                                            {partida.visitante_logo ? (
                                                                <img src={partida.visitante_logo} alt={partida.visitante} />
                                                            ) : (
                                                                <span>{getInitials(partida.visitante)}</span>
                                                            )}
                                                        </div>
                                                        <div className="partida-clube-text">
                                                            <strong className="partida-clube">
                                                                {partida.visitante ?? 'Visitante indefinido'}
                                                            </strong>
                                                            {partida.visitante_nickname && (
                                                                <small className="partida-nickname">
                                                                    {partida.visitante_nickname}
                                                                </small>
                                                            )}
                                                            {roleVisitanteText && (
                                                                <span className="partida-role-pill visitante">
                                                                    {roleVisitanteText}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="col-compact">
                                                    <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                                                        {statusElement}
                                                        {statusNote && (
                                                            <small style={{ opacity: 0.7 }}>{statusNote}</small>
                                                        )}
                                                        {hasScore && placarLabel && (
                                                            <small style={{ opacity: 0.75 }}>Placar: {placarLabel}</small>
                                                        )}
                                                        {partida.estado === 'wo' && woWinner && (
                                                            <small style={{ color: '#ffb347' }}>
                                                                W.O por {partida.wo_motivo ?? 'desistência'} — vitória de {woWinner}
                                                            </small>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="col-compact">
                                                    <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                                                        <span>{horario}</span>
                                                        {chips.length > 0 && (
                                                            <div className="partida-flags" style={{ gap: 6 }}>
                                                                {chips.map((chip) => (
                                                                    <span key={chip} className="partida-chip">
                                                                        {chip}
                                                                    </span>
                                                                ))}
                                                            </div>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="col-action">{renderActionMenu(partida)}</td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
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
