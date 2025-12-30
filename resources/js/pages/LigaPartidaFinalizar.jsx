import { useEffect, useMemo, useState } from 'react';
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
    const [placarExtras, setPlacarExtras] = useState({ mandante: 0, visitante: 0 });
    const [manualPlacar, setManualPlacar] = useState({ mandante: '', visitante: '' });
    const [manualDirty, setManualDirty] = useState(false);
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

    const labelWithClub = (labelText, clubName) =>
        clubName ? `${labelText} (${clubName})` : labelText;

    const label = estadoLabels[estado] ?? estado;
    const badgeClass = estadoClass[estado] ?? 'muted';
    const horario = formatDate(partida.scheduled_at);

    const isActiveEntry = (entry) =>
        entry?.nota !== null && entry?.nota !== undefined && entry?.nota !== '' && !Number.isNaN(Number(entry.nota));

    const sumGoals = (entries) =>
        entries.reduce((total, entry) => total + (isActiveEntry(entry) ? Number(entry.gols || 0) : 0), 0);

    const placar = useMemo(() => {
        const extraMandante = Number(placarExtras.mandante || 0);
        const extraVisitante = Number(placarExtras.visitante || 0);
        return {
            mandante: sumGoals(mandanteEntries) + extraMandante,
            visitante: sumGoals(visitanteEntries) + extraVisitante,
        };
    }, [mandanteEntries, visitanteEntries, placarExtras]);

    const resolvedPlacar = manualDirty ? manualPlacar : placar;
    const placarLabel = hasPreview ? `${resolvedPlacar.mandante} x ${resolvedPlacar.visitante}` : '—';
    const canAnalyze = ['confirmada', 'em_andamento'].includes(estado);

    useEffect(() => {
        if (!hasPreview || manualDirty) return;
        setManualPlacar({
            mandante: placar.mandante,
            visitante: placar.visitante,
        });
    }, [placar.mandante, placar.visitante, hasPreview, manualDirty]);

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

            const nextMandanteEntries = data?.mandante?.entries ?? [];
            const nextVisitanteEntries = data?.visitante?.entries ?? [];
            const previewMandante = Number(data?.placar?.mandante ?? 0);
            const previewVisitante = Number(data?.placar?.visitante ?? 0);
            const knownMandante = sumGoals(nextMandanteEntries);
            const knownVisitante = sumGoals(nextVisitanteEntries);

            setMandanteEntries(nextMandanteEntries);
            setVisitanteEntries(nextVisitanteEntries);
            setUnknownMandante(data?.mandante?.unknown_players ?? []);
            setUnknownVisitante(data?.visitante?.unknown_players ?? []);
            setPlacarExtras({
                mandante: Number.isFinite(previewMandante)
                    ? Math.max(previewMandante - knownMandante, 0)
                    : 0,
                visitante: Number.isFinite(previewVisitante)
                    ? Math.max(previewVisitante - knownVisitante, 0)
                    : 0,
            });
            setManualDirty(false);
            setManualPlacar({
                mandante: previewMandante,
                visitante: previewVisitante,
            });
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
        const placarMandante = Number(resolvedPlacar.mandante ?? 0);
        const placarVisitante = Number(resolvedPlacar.visitante ?? 0);

        if (!Number.isFinite(placarMandante) || !Number.isFinite(placarVisitante)) {
            setError('Placar inválido. Faça uma nova análise.');
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
            placar_mandante: placarMandante,
            placar_visitante: placarVisitante,
        };

        try {
            const { data } = await window.axios.post(
                `/api/partidas/${partida.id}/desempenho/confirm`,
                payload,
            );

            const params = new URLSearchParams();
            if (liga?.id) {
                params.set('liga_id', liga.id);
            }
            params.set('success', 'placar-registrado');
            window.location.assign(`/liga/partidas?${params.toString()}`);
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

    const handleManualPlacarChange = (side, value) => {
        setManualDirty(true);
        setManualPlacar((prev) => ({
            mandante: side === 'mandante' ? value : prev.mandante,
            visitante: side === 'visitante' ? value : prev.visitante,
        }));
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
                                <small>{labelWithClub('Mandante', partida.mandante)}</small>
                                <p>{partida.mandante ?? '—'}</p>
                            </div>
                            <div className="partida-meta-item">
                                <small>{labelWithClub('Visitante', partida.visitante)}</small>
                                <p>{partida.visitante ?? '—'}</p>
                            </div>
                            <div className="partida-meta-item">
                                <small>Placar atual</small>
                                <p>{placarLabel}</p>
                            </div>
                            <div className="partida-meta-item">
                                <small>Seu papel</small>
                                <p>
                                    <span className="partida-role-pill visitante">
                                        {labelWithClub('Visitante', partida.visitante)}
                                    </span>
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
                        {hasPreview && (
                            <div className="finalizar-score-edit">
                                <label>
                                    <span>{labelWithClub('Mandante', partida.mandante)}</span>
                                    <input
                                        type="number"
                                        min="0"
                                        value={manualPlacar.mandante}
                                        onChange={(e) => handleManualPlacarChange('mandante', e.target.value)}
                                        disabled={loading || saving}
                                    />
                                </label>
                                <label>
                                    <span>{labelWithClub('Visitante', partida.visitante)}</span>
                                    <input
                                        type="number"
                                        min="0"
                                        value={manualPlacar.visitante}
                                        onChange={(e) => handleManualPlacarChange('visitante', e.target.value)}
                                        disabled={loading || saving}
                                    />
                                </label>
                            </div>
                        )}
                    </div>
                </div>

                <div className="finalizar-upload-row">
                    <label className="finalizar-upload-card">
                        <span>{labelWithClub('Imagem do mandante', partida.mandante)}</span>
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
                                setPlacarExtras({ mandante: 0, visitante: 0 });
                                setManualDirty(false);
                                setManualPlacar({ mandante: '', visitante: '' });
                            }}
                            disabled={loading || saving}
                        />
                    </label>
                    <label className="finalizar-upload-card">
                        <span>{labelWithClub('Imagem do visitante', partida.visitante)}</span>
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
                                setPlacarExtras({ mandante: 0, visitante: 0 });
                                setManualDirty(false);
                                setManualPlacar({ mandante: '', visitante: '' });
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
                        <h3>{labelWithClub('Mandante', partida.mandante)}</h3>
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
                        <h3>{labelWithClub('Visitante', partida.visitante)}</h3>
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
