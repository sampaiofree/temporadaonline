import { useMemo, useState } from 'react';
import Navbar from '../components/app_publico/Navbar';

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getClubeFromWindow = () => window.__CLUBE__ ?? null;
const getPartidaFromWindow = () => window.__PARTIDA__ ?? null;

export default function LigaPartidaFinalizar() {
    const liga = getLigaFromWindow();
    const clube = getClubeFromWindow();
    const partida = getPartidaFromWindow();
    const [mandanteImage, setMandanteImage] = useState(null);
    const [visitanteImage, setVisitanteImage] = useState(null);
    const [mandanteEntries, setMandanteEntries] = useState([]);
    const [visitanteEntries, setVisitanteEntries] = useState([]);
    const [unknownMandante, setUnknownMandante] = useState([]);
    const [unknownVisitante, setUnknownVisitante] = useState([]);
    const [estado, setEstado] = useState(partida?.estado ?? '');
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [hasPreview, setHasPreview] = useState(false);

    if (!liga) {
        return (
            <main className="liga-partidas-screen">
                <p className="ligas-empty">Liga indisponível. Volte para o painel.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    if (!partida) {
        return (
            <main className="liga-partidas-screen">
                <p className="ligas-empty">Partida indisponível.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

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

    const resolveTeamLogo = (side) => {
        if (side === 'mandante') return partida.mandante_logo || null;
        return partida.visitante_logo || null;
    };

    const label = estadoLabels[estado] ?? estado;
    const badgeClass = estadoClass[estado] ?? 'muted';
    const horario = formatDate(partida.scheduled_at);

    const isActiveEntry = (entry) =>
        entry?.nota !== null && entry?.nota !== undefined && entry?.nota !== '' && !Number.isNaN(Number(entry.nota));

    const placar = useMemo(() => {
        const sumGoals = (entries) =>
            entries.reduce((total, entry) => total + (isActiveEntry(entry) ? Number(entry.gols || 0) : 0), 0);

        return {
            mandante: sumGoals(mandanteEntries),
            visitante: sumGoals(visitanteEntries),
        };
    }, [mandanteEntries, visitanteEntries]);

    const hasEntries = mandanteEntries.length > 0 || visitanteEntries.length > 0;
    const placarLabel = hasEntries ? `${placar.mandante} x ${placar.visitante}` : '—';
    const canAnalyze = ['confirmada', 'em_andamento'].includes(estado);

    const handleAnalyze = async () => {
        if (!mandanteImage || !visitanteImage) {
            setError('Envie as duas imagens para continuar.');
            return;
        }

        setError('');
        setSuccess('');
        setLoading(true);

        try {
            const formData = new FormData();
            formData.append('mandante_imagem', mandanteImage);
            formData.append('visitante_imagem', visitanteImage);

            const { data } = await window.axios.post(
                `/api/partidas/${partida.id}/desempenho/preview`,
                formData,
            );

            setMandanteEntries(data?.mandante?.entries ?? []);
            setVisitanteEntries(data?.visitante?.entries ?? []);
            setUnknownMandante(data?.mandante?.unknown_players ?? []);
            setUnknownVisitante(data?.visitante?.unknown_players ?? []);
            setHasPreview(true);
        } catch (err) {
            const message =
                err.response?.data?.message ?? 'Não foi possível analisar as imagens.';
            setError(message);
        } finally {
            setLoading(false);
        }
    };

    const handleConfirm = async () => {
        const filteredMandante = mandanteEntries.filter(isActiveEntry);
        const filteredVisitante = visitanteEntries.filter(isActiveEntry);

        if (filteredMandante.length === 0 || filteredVisitante.length === 0) {
            setError('Informe pelo menos um jogador de cada time.');
            return;
        }

        setSaving(true);
        setError('');
        setSuccess('');

        const normalize = (entry) => ({
            elencopadrao_id: entry.elencopadrao_id,
            nota: Number(entry.nota),
            gols: Number(entry.gols || 0),
            assistencias: Number(entry.assistencias || 0),
        });

        const payload = {
            mandante: filteredMandante.map(normalize),
            visitante: filteredVisitante.map(normalize),
        };

        try {
            const { data } = await window.axios.post(
                `/api/partidas/${partida.id}/desempenho/confirm`,
                payload,
            );

            setSuccess('Desempenho confirmado com sucesso.');
            setHasPreview(false);
            if (data?.estado) {
                setEstado(data.estado);
            }
        } catch (err) {
            const message =
                err.response?.data?.message ?? 'Não foi possível confirmar os dados.';
            setError(message);
        } finally {
            setSaving(false);
        }
    };

    const updateEntry = (side, index, field, value) => {
        const updater = side === 'mandante' ? setMandanteEntries : setVisitanteEntries;
        updater((prev) =>
            prev.map((item, i) => (i === index ? { ...item, [field]: value } : item)),
        );
    };

    return (
        <main className="liga-partidas-screen">
            <section className="liga-dashboard-hero">
                <p className="ligas-eyebrow">FINALIZAR</p>
                <h1 className="ligas-title">Dados da partida</h1>
                <p className="ligas-subtitle">
                    {clube
                        ? `Você finaliza a partida como visitante: ${clube.nome}.`
                        : 'Revise os dados antes de finalizar a partida.'}
                </p>
            </section>

            <section className="liga-partidas-cards">
                <div className="partida-card-grid">
                    <article className="partida-card">
                        <div className="partida-card-head">
                            <div className="partida-card-team">
                                <div className="partida-card-shield">
                                    {resolveTeamLogo('mandante') ? (
                                        <img
                                            src={resolveTeamLogo('mandante')}
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
                                    {resolveTeamLogo('visitante') ? (
                                        <img
                                            src={resolveTeamLogo('visitante')}
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
                        </div>

                        <div className="partida-meta">
                            <div className="partida-meta-item">
                                <small>Mandante</small>
                                <p>{partida.mandante ?? '—'}</p>
                            </div>
                            <div className="partida-meta-item">
                                <small>Visitante</small>
                                <p>{partida.visitante ?? '—'}</p>
                            </div>
                            <div className="partida-meta-item">
                                <small>Placar atual</small>
                                <p>{placarLabel}</p>
                            </div>
                            <div className="partida-meta-item">
                                <small>Seu papel</small>
                                <p>
                                    <span className="partida-role-pill visitante">Visitante</span>
                                </p>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section className="finalizar-form card card-gold">
                <div className="finalizar-header">
                    <div>
                        <p className="finalizar-eyebrow">IMAGENS</p>
                        <h2 className="finalizar-title">Enviar desempenho</h2>
                        <p className="finalizar-subtitle">
                            Envie uma imagem do mandante e uma do visitante para extrair notas, gols e assistências.
                        </p>
                    </div>
                    <div className="finalizar-score">
                        <small>Placar</small>
                        <strong>{placarLabel}</strong>
                    </div>
                </div>

                <div className="finalizar-upload-row">
                    <label className="finalizar-upload-card">
                        <span>Imagem do mandante</span>
                        <input
                            type="file"
                            accept="image/*"
                            onChange={(e) => {
                                setMandanteImage(e.target.files?.[0] ?? null);
                                setHasPreview(false);
                                setMandanteEntries([]);
                                setVisitanteEntries([]);
                                setUnknownMandante([]);
                                setUnknownVisitante([]);
                            }}
                            disabled={loading || saving}
                        />
                    </label>
                    <label className="finalizar-upload-card">
                        <span>Imagem do visitante</span>
                        <input
                            type="file"
                            accept="image/*"
                            onChange={(e) => {
                                setVisitanteImage(e.target.files?.[0] ?? null);
                                setHasPreview(false);
                                setMandanteEntries([]);
                                setVisitanteEntries([]);
                                setUnknownMandante([]);
                                setUnknownVisitante([]);
                            }}
                            disabled={loading || saving}
                        />
                    </label>
                </div>

                {loading && (
                    <div className="finalizar-progress">
                        <div className="finalizar-progress-bar" />
                    </div>
                )}

                {error && <p className="modal-error">{error}</p>}
                {success && <p className="finalizar-success">{success}</p>}

                <div className="finalizar-actions">
                    <button
                        type="button"
                        className="btn-primary"
                        onClick={handleAnalyze}
                        disabled={loading || saving || !canAnalyze}
                    >
                        {loading ? 'Analisando...' : 'Analisar imagens'}
                    </button>
                    <button
                        type="button"
                        className="btn-outline"
                        onClick={handleConfirm}
                        disabled={loading || saving || !hasPreview || !canAnalyze}
                    >
                        {saving ? 'Salvando...' : 'Confirmar dados'}
                    </button>
                </div>
            </section>

            {hasPreview && (
                <section className="finalizar-results">
                    <div className="finalizar-team">
                        <h3>Mandante</h3>
                        {unknownMandante.length > 0 && (
                            <p className="finalizar-warning">
                                Jogadores não identificados: {unknownMandante.join(', ')}.
                            </p>
                        )}
                        <div className="finalizar-table">
                            <div className="finalizar-row header">
                                <span>Jogador</span>
                                <span>NF</span>
                                <span>G</span>
                                <span>AST</span>
                            </div>
                            {mandanteEntries.map((entry, index) => (
                                <div key={entry.elencopadrao_id} className="finalizar-row">
                                    <strong>{entry.nome}</strong>
                                    <input
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        max="10"
                                        value={entry.nota ?? ''}
                                        onChange={(e) =>
                                            updateEntry('mandante', index, 'nota', e.target.value)
                                        }
                                    />
                                    <input
                                        type="number"
                                        min="0"
                                        value={entry.gols ?? 0}
                                        onChange={(e) =>
                                            updateEntry('mandante', index, 'gols', e.target.value)
                                        }
                                    />
                                    <input
                                        type="number"
                                        min="0"
                                        value={entry.assistencias ?? 0}
                                        onChange={(e) =>
                                            updateEntry('mandante', index, 'assistencias', e.target.value)
                                        }
                                    />
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="finalizar-team">
                        <h3>Visitante</h3>
                        {unknownVisitante.length > 0 && (
                            <p className="finalizar-warning">
                                Jogadores não identificados: {unknownVisitante.join(', ')}.
                            </p>
                        )}
                        <div className="finalizar-table">
                            <div className="finalizar-row header">
                                <span>Jogador</span>
                                <span>NF</span>
                                <span>G</span>
                                <span>AST</span>
                            </div>
                            {visitanteEntries.map((entry, index) => (
                                <div key={entry.elencopadrao_id} className="finalizar-row">
                                    <strong>{entry.nome}</strong>
                                    <input
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        max="10"
                                        value={entry.nota ?? ''}
                                        onChange={(e) =>
                                            updateEntry('visitante', index, 'nota', e.target.value)
                                        }
                                    />
                                    <input
                                        type="number"
                                        min="0"
                                        value={entry.gols ?? 0}
                                        onChange={(e) =>
                                            updateEntry('visitante', index, 'gols', e.target.value)
                                        }
                                    />
                                    <input
                                        type="number"
                                        min="0"
                                        value={entry.assistencias ?? 0}
                                        onChange={(e) =>
                                            updateEntry('visitante', index, 'assistencias', e.target.value)
                                        }
                                    />
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            <Navbar active="ligas" />
        </main>
    );
}
