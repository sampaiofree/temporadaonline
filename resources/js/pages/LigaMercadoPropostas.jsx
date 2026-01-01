import { useEffect, useState } from 'react';
import Navbar from '../components/app_publico/Navbar';
import Alert from '../components/app_publico/Alert';

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0,
});

const formatCurrency = (value) => {
    if (value === null || value === undefined) return '-';
    return currencyFormatter.format(value);
};

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getClubeFromWindow = () => window.__CLUBE__ ?? null;

const formatShortDate = (value) => {
    if (!value) return null;
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return null;
    return date.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
};

export default function LigaMercadoPropostas() {
    const liga = getLigaFromWindow();
    const clube = getClubeFromWindow();

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [feedback, setFeedback] = useState('');
    const [recebidas, setRecebidas] = useState([]);
    const [enviadas, setEnviadas] = useState([]);
    const [busyIds, setBusyIds] = useState(() => new Set());

    const mercadoHref = liga ? `/liga/mercado?liga_id=${liga.id}` : '/liga/mercado';
    const minhaLigaHref = liga ? `/minha_liga?liga_id=${liga.id}` : '/minha_liga';

    const totalRecebidas = recebidas.length;
    const totalEnviadas = enviadas.length;

    const loadPropostas = async () => {
        if (!liga || !clube) {
            setLoading(false);
            return;
        }

        setLoading(true);
        setError('');

        try {
            const { data } = await window.axios.get(
                `/api/ligas/${liga.id}/clubes/${clube.id}/propostas`,
            );
            setRecebidas(Array.isArray(data?.recebidas) ? data.recebidas : []);
            setEnviadas(Array.isArray(data?.enviadas) ? data.enviadas : []);
        } catch (err) {
            setError(err.response?.data?.message ?? 'Nao foi possivel carregar as propostas.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        void loadPropostas();
    }, []);

    const updateBusy = (id, value) => {
        setBusyIds((prev) => {
            const next = new Set(prev);
            if (value) {
                next.add(id);
            } else {
                next.delete(id);
            }
            return next;
        });
    };

    const handleAccept = async (proposta) => {
        if (!liga || !clube || !proposta) return;
        if (!window.confirm('Deseja aceitar esta proposta?')) return;

        updateBusy(proposta.id, true);
        try {
            const { data } = await window.axios.post(
                `/api/ligas/${liga.id}/clubes/${clube.id}/propostas/${proposta.id}/aceitar`,
            );
            setRecebidas((prev) => prev.filter((item) => item.id !== proposta.id));
            setFeedback(data?.message ?? 'Proposta aceita.');
        } catch (err) {
            setFeedback(err.response?.data?.message ?? 'Nao foi possivel aceitar a proposta.');
        } finally {
            updateBusy(proposta.id, false);
        }
    };

    const handleReject = async (proposta) => {
        if (!liga || !clube || !proposta) return;

        updateBusy(proposta.id, true);
        try {
            const { data } = await window.axios.post(
                `/api/ligas/${liga.id}/clubes/${clube.id}/propostas/${proposta.id}/rejeitar`,
            );
            setRecebidas((prev) => prev.filter((item) => item.id !== proposta.id));
            setFeedback(data?.message ?? 'Proposta rejeitada.');
        } catch (err) {
            setFeedback(err.response?.data?.message ?? 'Nao foi possivel rejeitar a proposta.');
        } finally {
            updateBusy(proposta.id, false);
        }
    };

    const handleCancel = async (proposta) => {
        if (!liga || !clube || !proposta) return;
        if (!window.confirm('Deseja cancelar esta proposta?')) return;

        updateBusy(proposta.id, true);
        try {
            const { data } = await window.axios.post(
                `/api/ligas/${liga.id}/clubes/${clube.id}/propostas/${proposta.id}/cancelar`,
            );
            setEnviadas((prev) => prev.filter((item) => item.id !== proposta.id));
            setFeedback(data?.message ?? 'Proposta cancelada.');
        } catch (err) {
            setFeedback(err.response?.data?.message ?? 'Nao foi possivel cancelar a proposta.');
        } finally {
            updateBusy(proposta.id, false);
        }
    };

    const renderOfferSummary = (proposta) => {
        const valor = Number(proposta?.valor ?? 0);
        const jogadores = Array.isArray(proposta?.oferta_jogadores) ? proposta.oferta_jogadores : [];
        const parts = [];
        if (valor > 0) {
            parts.push(formatCurrency(valor));
        }
        if (jogadores.length > 0) {
            parts.push(`${jogadores.length} jogador${jogadores.length > 1 ? 'es' : ''}`);
        }
        if (parts.length === 0) {
            return 'Sem oferta registrada';
        }
        return parts.join(' + ');
    };

    const renderPlayerName = (player) => {
        if (!player) return 'Jogador';
        return player.short_name || player.long_name || 'Jogador';
    };

    if (!liga) {
        return (
            <main className="liga-mercado-screen">
                <p className="ligas-empty">Liga indisponivel.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    return (
        <main className="liga-mercado-screen">
            <section className="liga-dashboard-hero">
                <p className="ligas-eyebrow">MERCADO</p>
                <h1 className="ligas-title">Propostas</h1>
                <p className="ligas-subtitle">
                    {clube ? `Operando como ${clube.nome}` : 'Crie seu clube para receber propostas.'}
                </p>
            </section>

            <section className="mercado-filters propostas-actions" aria-label="Acoes do mercado">
                <a className="btn-outline" href={mercadoHref}>
                    Voltar ao mercado
                </a>
            </section>

            {!clube && (
                <section className="financeiro-empty">
                    <p className="ligas-empty">Voce ainda nao criou um clube nesta liga.</p>
                    <a className="btn-primary" href={minhaLigaHref}>
                        Criar meu clube
                    </a>
                </section>
            )}

            {clube && (
                <>
                    <section className="propostas-section">
                        <header className="propostas-header">
                            <h2>Propostas recebidas</h2>
                            <span className="propostas-count">{totalRecebidas}</span>
                        </header>

                        {loading ? (
                            <p className="card-meta">Carregando propostas...</p>
                        ) : totalRecebidas === 0 ? (
                            <p className="card-meta">Nenhuma proposta recebida no momento.</p>
                        ) : (
                            <div className="propostas-grid">
                                {recebidas.map((proposta) => {
                                    const busy = busyIds.has(proposta.id);
                                    const targetName = renderPlayerName(proposta.elencopadrao);
                                    const offerSummary = renderOfferSummary(proposta);
                                    const dateLabel = formatShortDate(proposta.created_at);
                                    const origem = proposta.clube_destino?.nome ?? 'Clube rival';
                                    const origemLiga = proposta.clube_destino?.liga_nome;
                                    const origemLabel = origemLiga ? `${origem} (${origemLiga})` : origem;
                                    const jogadores = Array.isArray(proposta.oferta_jogadores)
                                        ? proposta.oferta_jogadores
                                        : [];

                                    return (
                                        <article key={proposta.id} className="proposta-card">
                                            <div className="proposta-card-header">
                                                <div>
                                                    <h3>{targetName}</h3>
                                                    <p className="proposta-card-meta">
                                                        De {origemLabel}
                                                        {dateLabel ? ` - ${dateLabel}` : ''}
                                                    </p>
                                                </div>
                                                <span className="proposta-card-badge">Recebida</span>
                                            </div>

                                            <div className="proposta-card-body">
                                                <p className="proposta-card-offer">
                                                    Oferta: {offerSummary}
                                                </p>
                                                {jogadores.length > 0 && (
                                                    <div className="proposta-card-players">
                                                        {jogadores.map((player) => (
                                                            <span key={player.id} className="proposta-pill">
                                                                {renderPlayerName(player)}
                                                            </span>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>

                                            <div className="proposta-card-actions">
                                                <button
                                                    type="button"
                                                    className="btn-primary"
                                                    onClick={() => handleAccept(proposta)}
                                                    disabled={busy}
                                                >
                                                    {busy ? 'Processando...' : 'Aceitar'}
                                                </button>
                                                <button
                                                    type="button"
                                                    className="btn-outline"
                                                    onClick={() => handleReject(proposta)}
                                                    disabled={busy}
                                                >
                                                    Rejeitar
                                                </button>
                                            </div>
                                        </article>
                                    );
                                })}
                            </div>
                        )}
                    </section>

                    <section className="propostas-section">
                        <header className="propostas-header">
                            <h2>Minhas propostas</h2>
                            <span className="propostas-count">{totalEnviadas}</span>
                        </header>

                        {loading ? (
                            <p className="card-meta">Carregando propostas...</p>
                        ) : totalEnviadas === 0 ? (
                            <p className="card-meta">Nenhuma proposta enviada no momento.</p>
                        ) : (
                            <div className="propostas-grid">
                                {enviadas.map((proposta) => {
                                    const busy = busyIds.has(proposta.id);
                                    const targetName = renderPlayerName(proposta.elencopadrao);
                                    const offerSummary = renderOfferSummary(proposta);
                                    const dateLabel = formatShortDate(proposta.created_at);
                                    const destino = proposta.clube_origem?.nome ?? 'Clube rival';
                                    const destinoLiga = proposta.clube_origem?.liga_nome;
                                    const destinoLabel = destinoLiga ? `${destino} (${destinoLiga})` : destino;
                                    const jogadores = Array.isArray(proposta.oferta_jogadores)
                                        ? proposta.oferta_jogadores
                                        : [];

                                    return (
                                        <article key={proposta.id} className="proposta-card">
                                            <div className="proposta-card-header">
                                                <div>
                                                    <h3>{targetName}</h3>
                                                    <p className="proposta-card-meta">
                                                        Para {destinoLabel}
                                                        {dateLabel ? ` - ${dateLabel}` : ''}
                                                    </p>
                                                </div>
                                                <span className="proposta-card-badge is-outbound">Enviada</span>
                                            </div>

                                            <div className="proposta-card-body">
                                                <p className="proposta-card-offer">
                                                    Oferta: {offerSummary}
                                                </p>
                                                {jogadores.length > 0 && (
                                                    <div className="proposta-card-players">
                                                        {jogadores.map((player) => (
                                                            <span key={player.id} className="proposta-pill">
                                                                {renderPlayerName(player)}
                                                            </span>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>

                                            <div className="proposta-card-actions">
                                                <button
                                                    type="button"
                                                    className="btn-outline"
                                                    onClick={() => handleCancel(proposta)}
                                                    disabled={busy}
                                                >
                                                    {busy ? 'Processando...' : 'Cancelar'}
                                                </button>
                                            </div>
                                        </article>
                                    );
                                })}
                            </div>
                        )}
                    </section>
                </>
            )}

            {feedback && (
                <Alert
                    variant="info"
                    title="Aviso"
                    floating
                    onClose={() => setFeedback('')}
                >
                    {feedback}
                </Alert>
            )}

            {error && (
                <Alert
                    variant="danger"
                    title="Erro"
                    floating
                    onClose={() => setError('')}
                >
                    {error}
                </Alert>
            )}

            <Navbar active="ligas" />
        </main>
    );
}
