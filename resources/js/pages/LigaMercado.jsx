import { useMemo, useState } from 'react';
import Navbar from '../components/app_publico/Navbar';
import backgroundDefault from '../../../storage/app/public/app/background/fundopadrao.webp';
import backgroundVertical from '../../../storage/app/public/app/background/fundo_vertical.webp';

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0,
});

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getClubeFromWindow = () => window.__CLUBE__ ?? null;
const getMercadoFromWindow = () => window.__MERCADO__ ?? { players: [] };

const STATUS_FILTERS = [
    { value: 'all', label: 'Todos' },
    { value: 'livre', label: 'Livre' },
    { value: 'meu', label: 'Meu clube' },
    { value: 'outro', label: 'Outros clubes' },
];

const proxyFaceUrl = (url) => {
    if (!url) {
        return null;
    }
    const trimmed = url.replace(/^https?:\/\//, '');
    return `https://images.weserv.nl/?url=${encodeURIComponent(trimmed)}&w=180&h=180`;
};

const formatCurrency = (value) => {
    if (value === null || typeof value === 'undefined') {
        return '—';
    }

    return currencyFormatter.format(value);
};

export default function LigaMercado() {
    const liga = getLigaFromWindow();
    const clube = getClubeFromWindow();
    const mercado = getMercadoFromWindow();
    const [statusFilter, setStatusFilter] = useState('all');
    const [feedback, setFeedback] = useState('');
    const [loadingId, setLoadingId] = useState(null);

    const backgroundStyles = {
        '--mco-cover': `url(${backgroundDefault})`,
        '--mco-cover-mobile': `url(${backgroundVertical})`,
    };

    const filteredPlayers = useMemo(() => {
        return (mercado.players || []).filter((player) => {
            if (statusFilter === 'all') {
                return true;
            }

            return player.club_status === statusFilter;
        });
    }, [mercado.players, statusFilter]);

    const handleAction = async (player, type) => {
        if (!liga || !clube) {
            setFeedback('Você precisa criar um clube antes de operar no mercado.');
            return;
        }

        if (loadingId) {
            return;
        }

        setLoadingId(player.elencopadrao_id);
        setFeedback('');

        const baseUrl = `/api/ligas/${liga.id}/clubes/${clube.id}`;
        let endpoint = 'comprar';
        const payload = { elencopadrao_id: player.elencopadrao_id };

        if (type === 'multa') {
            endpoint = 'multa';
        }

        try {
            const { data } = await window.axios.post(`${baseUrl}/${endpoint}`, payload);
            setFeedback(data?.message ?? 'Operação concluída.');
        } catch (error) {
            setFeedback(error.response?.data?.message ?? 'Não foi possível completar a operação.');
        } finally {
            setLoadingId(null);
        }
    };

    if (!liga) {
        return (
            <main className="liga-mercado-screen" style={backgroundStyles}>
                <p className="ligas-empty">Liga indisponível. Volte para o painel.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    const heroMessage = clube
        ? `Operando como ${clube.nome}`
        : 'Crie seu clube para negociar no mercado.';

    return (
        <main className="liga-mercado-screen" style={backgroundStyles}>
            <section className="liga-dashboard-hero">
                <p className="ligas-eyebrow">MERCADO</p>
                <h1 className="ligas-title">Jogadores da liga</h1>
                <p className="ligas-subtitle">{heroMessage}</p>
            </section>

            <section className="mercado-filters">
                {STATUS_FILTERS.map((filter) => (
                    <button
                        key={filter.value}
                        type="button"
                        className={`filter-pill${statusFilter === filter.value ? ' active' : ''}`}
                        onClick={() => setStatusFilter(filter.value)}
                    >
                        {filter.label}
                    </button>
                ))}
            </section>

            <section className="mercado-list" aria-label="Jogadores do mercado">
                {filteredPlayers.length === 0 ? (
                    <p className="ligas-empty">Nenhum jogador encontrado para esse filtro.</p>
                ) : (
                    filteredPlayers.map((player) => (
                        <article key={player.elencopadrao_id} className="mercado-card">
                            <div className="mercado-card-image">
                                {player.player_face_url ? (
                                    <img src={proxyFaceUrl(player.player_face_url)} alt={player.short_name || player.long_name} />
                                ) : (
                                    <span>{(player.short_name || player.long_name || '—').slice(0, 2).toUpperCase()}</span>
                                )}
                            </div>
                            <div className="mercado-card-body">
                                <div className="mercado-card-title">
                                    <strong>{player.short_name || player.long_name || 'Sem nome'}</strong>
                                    <span>{player.player_positions ?? 'Posição não informada'}</span>
                                </div>
                                <p className="mercado-card-overall">OVR {player.overall ?? '—'}</p>
                                <p className="mercado-card-meta">
                                    Valor: {formatCurrency(player.value_eur)} · Salário: {formatCurrency(player.wage_eur)}
                                </p>
                                <p className="mercado-card-club">
                                    {player.club_status === 'livre'
                                        ? 'Livre'
                                        : player.club_status === 'meu'
                                            ? 'Meu clube'
                                            : `Clube atual: ${player.club_name}`}
                                </p>
                            </div>
                            <div className="mercado-card-actions">
                                {player.can_buy && (
                                    <button
                                        type="button"
                                        className="btn-primary"
                                        onClick={() => handleAction(player, 'comprar')}
                                        disabled={loadingId === player.elencopadrao_id}
                                    >
                                        {loadingId === player.elencopadrao_id ? 'Operando...' : 'Comprar livre'}
                                    </button>
                                )}
                                {player.can_multa && (
                                    <button
                                        type="button"
                                        className="btn-outline"
                                        onClick={() => handleAction(player, 'multa')}
                                        disabled={loadingId === player.elencopadrao_id}
                                    >
                                        {loadingId === player.elencopadrao_id ? 'Operando...' : 'Pagar multa'}
                                    </button>
                                )}
                            </div>
                        </article>
                    ))
                )}
            </section>
            {feedback && <p className="elenco-feedback">{feedback}</p>}

            <Navbar active="ligas" />
        </main>
    );
}
