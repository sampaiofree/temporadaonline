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

const OVR_FILTERS = [
    { value: 'all', label: 'OVR: Todos' },
    { value: '50-69', label: '50–69' },
    { value: '70-79', label: '70–79' },
    { value: '80-84', label: '80–84' },
    { value: '85-89', label: '85–89' },
    { value: '90+', label: '90+' },
];

const proxyFaceUrl = (url) => {
    if (!url) return null;
    const trimmed = url.replace(/^https?:\/\//, '');
    return `https://images.weserv.nl/?url=${encodeURIComponent(trimmed)}&w=180&h=180`;
};

const formatCurrency = (value) => {
    if (value === null || typeof value === 'undefined') return '—';
    return currencyFormatter.format(value);
};

const normalizePositions = (positions) => {
    if (!positions) return [];
    return String(positions).split(',').map((p) => p.trim()).filter(Boolean);
};

export default function LigaMercado() {
    const liga = getLigaFromWindow();
    const clube = getClubeFromWindow();
    const mercado = getMercadoFromWindow();

    const [statusFilter, setStatusFilter] = useState('all');
    const [positionFilter, setPositionFilter] = useState('all');
    const [ovrFilter, setOvrFilter] = useState('all');
    const [page, setPage] = useState(1);
    const perPage = 25;

    const [feedback, setFeedback] = useState('');
    const [loadingId, setLoadingId] = useState(null);

    const backgroundStyles = {
        '--mco-cover': `url(${backgroundDefault})`,
        '--mco-cover-mobile': `url(${backgroundVertical})`,
    };

    const allPlayers = mercado.players || [];

    // Gera opções de posição dinamicamente baseado nos jogadores da liga
    const positionOptions = useMemo(() => {
        const set = new Set();
        allPlayers.forEach((p) => {
            normalizePositions(p.player_positions).forEach((pos) => set.add(pos));
        });
        return ['all', ...Array.from(set).sort()];
    }, [allPlayers]);

    // Lógica de Filtro e Ordenação
    const filteredPlayers = useMemo(() => {
        return allPlayers.filter((player) => {
            const matchesStatus = statusFilter === 'all' || player.club_status === statusFilter;
            const matchesPos = positionFilter === 'all' || normalizePositions(player.player_positions).includes(positionFilter);
            
            let matchesOvr = true;
            if (ovrFilter !== 'all') {
                const ovr = Number(player.overall ?? 0);
                if (ovrFilter === '90+') matchesOvr = ovr >= 90;
                else {
                    const [min, max] = ovrFilter.split('-').map(Number);
                    matchesOvr = ovr >= min && ovr <= max;
                }
            }
            return matchesStatus && matchesPos && matchesOvr;
        }).sort((a, b) => (b.overall || 0) - (a.overall || 0)); // Ordena por maior OVR por padrão
    }, [allPlayers, statusFilter, positionFilter, ovrFilter]);

    // Paginação
    const paginatedItems = useMemo(() => {
        const start = (page - 1) * perPage;
        return filteredPlayers.slice(start, start + perPage);
    }, [filteredPlayers, page]);

    const handleAction = async (player, type) => {
        if (!liga || !clube) {
            setFeedback('Crie um clube antes de negociar.');
            return;
        }
        if (loadingId) return;
        setLoadingId(player.elencopadrao_id);
        setFeedback('');

        const endpoint = type === 'multa' ? 'multa' : 'comprar';
        try {
            const { data } = await window.axios.post(`/api/ligas/${liga.id}/clubes/${clube.id}/${endpoint}`, {
                elencopadrao_id: player.elencopadrao_id 
            });
            setFeedback(data?.message ?? 'Sucesso!');
        } catch (error) {
            setFeedback(error.response?.data?.message ?? 'Erro na operação.');
        } finally {
            setLoadingId(null);
        }
    };

    if (!liga) return null;

    return (
        <main className="liga-mercado-screen" style={backgroundStyles}>
            <section className="liga-dashboard-hero">
                <p className="ligas-eyebrow">MERCADO</p>
                <h1 className="ligas-title">Jogadores da liga</h1>
                <p className="ligas-subtitle">{clube ? `Operando como ${clube.nome}` : 'Crie seu clube para negociar.'}</p>
            </section>

            {/* Filtros Principais (Pills) */}
            <section className="mercado-filters">
                {STATUS_FILTERS.map((f) => (
                    <button
                        key={f.value}
                        className={`filter-pill ${statusFilter === f.value ? 'active' : ''}`}
                        onClick={() => { setStatusFilter(f.value); setPage(1); }}
                    >
                        {f.label}
                    </button>
                ))}
            </section>

            {/* Filtros Avançados (Selects) - Estilizados para não quebrar */}
            <section className="mercado-filters" style={{ marginTop: '10px', gap: '10px', flexWrap: 'wrap' }}>
                <select 
                    className="filter-pill" 
                    style={{ background: '#222', color: '#fff', border: '1px solid #444' }}
                    value={positionFilter}
                    onChange={(e) => { setPositionFilter(e.target.value); setPage(1); }}
                >
                    <option value="all">Todas as Posições</option>
                    {positionOptions.filter(p => p !== 'all').map(p => <option key={p} value={p}>{p}</option>)}
                </select>

                <select 
                    className="filter-pill" 
                    style={{ background: '#222', color: '#fff', border: '1px solid #444' }}
                    value={ovrFilter}
                    onChange={(e) => { setOvrFilter(e.target.value); setPage(1); }}
                >
                    {OVR_FILTERS.map(f => <option key={f.value} value={f.value}>{f.label}</option>)}
                </select>
            </section>

            <section className="mercado-list">
                {paginatedItems.length === 0 ? (
                    <p className="ligas-empty">Nenhum jogador encontrado.</p>
                ) : (
                    paginatedItems.map((player) => (
                        <article key={player.elencopadrao_id} className="mercado-card">
                            <div className="mercado-card-image">
                                {player.player_face_url ? (
                                    <img src={proxyFaceUrl(player.player_face_url)} alt={player.short_name} />
                                ) : (
                                    <span>{(player.short_name || '—').slice(0, 2).toUpperCase()}</span>
                                )}
                            </div>
                            <div className="mercado-card-body">
                                <div className="mercado-card-title">
                                    <strong>{player.short_name || player.long_name}</strong>
                                    <span>{player.player_positions}</span>
                                </div>
                                <p className="mercado-card-overall">OVR {player.overall}</p>
                                <p className="mercado-card-meta">
                                    V: {formatCurrency(player.value_eur)} · S: {formatCurrency(player.wage_eur)}
                                </p>
                                <p className="mercado-card-club">
                                    {player.club_status === 'livre' ? 'Livre' : player.club_name}
                                </p>
                            </div>
                            <div className="mercado-card-actions">
                                {player.can_buy && (
                                    <button className="btn-primary" onClick={() => handleAction(player, 'comprar')} disabled={!!loadingId}>
                                        {loadingId === player.elencopadrao_id ? '...' : 'Comprar'}
                                    </button>
                                )}
                                {player.can_multa && (
                                    <button className="btn-outline" onClick={() => handleAction(player, 'multa')} disabled={!!loadingId}>
                                        {loadingId === player.elencopadrao_id ? '...' : 'Multa'}
                                    </button>
                                )}
                            </div>
                        </article>
                    ))
                )}
            </section>

            {/* Paginação Simples */}
            {filteredPlayers.length > perPage && (
                <div style={{ display: 'flex', justifyContent: 'center', gap: '20px', padding: '20px' }}>
                    <button 
                        className="btn-outline" 
                        disabled={page === 1} 
                        onClick={() => setPage(p => p - 1)}
                    > Anterior </button>
                    <span style={{ color: '#fff', alignSelf: 'center' }}>Pág {page}</span>
                    <button 
                        className="btn-outline" 
                        disabled={page * perPage >= filteredPlayers.length} 
                        onClick={() => setPage(p => p + 1)}
                    > Próxima </button>
                </div>
            )}

            {feedback && <p className="elenco-feedback">{feedback}</p>}
            <Navbar active="ligas" />
        </main>
    );
}