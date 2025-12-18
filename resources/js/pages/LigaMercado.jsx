import { useMemo, useState, useEffect } from 'react';
import Navbar from '../components/app_publico/Navbar';
import Alert from '../components/app_publico/Alert';
import backgroundDefault from '../../../storage/app/public/app/background/fundopadrao.jpgp';
import backgroundVertical from '../../../storage/app/public/app/background/fundopadrao.jpgp';

/* =========================
   Helpers
========================= */

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0,
});

const formatCurrency = (value) => {
    if (value === null || value === undefined) return '—';
    return currencyFormatter.format(value);
};

const formatShortMoney = (value) => {
    if (value === null || value === undefined) return '—';
    const n = Number(value);
    if (!Number.isFinite(n)) return '—';

    const abs = Math.abs(n);

    if (abs >= 1_000_000_000) return `${(n / 1_000_000_000).toFixed(1).replace('.0', '')}B`;
    if (abs >= 1_000_000) return `${(n / 1_000_000).toFixed(1).replace('.0', '')}M`;
    if (abs >= 1_000) return `${Math.round(n / 1_000)}K`;
    return String(Math.round(n));
};

const proxyFaceUrl = (url) => {
    if (!url) return null;
    const trimmed = url.replace(/^https?:\/\//, '');
    return `https://images.weserv.nl/?url=${encodeURIComponent(trimmed)}&w=80&h=80`;
};

const normalizePositions = (positions) => {
    if (!positions) return [];
    return String(positions)
        .split(',')
        .map((p) => p.trim())
        .filter(Boolean);
};

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getClubeFromWindow = () => window.__CLUBE__ ?? null;
const getMercadoFromWindow = () => window.__MERCADO__ ?? { players: [] };

/* =========================
   Constantes UI
========================= */

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

const getPlayerName = (p) => (p?.short_name || p?.long_name || '').toString().trim();

const getOvrTone = (overall) => {
    const ovr = Number(overall ?? 0);
    if (ovr >= 80) return 'high';
    if (ovr >= 60) return 'mid';
    return 'low';
};

const resolveFeedbackVariant = (message) => {
    if (!message) return 'info';
    const lower = message.toLowerCase();
    if (lower.includes('sucesso') || lower.includes('conclu')) return 'success';
    if (lower.includes('erro') || lower.includes('insuficiente') || lower.includes('negativo')) return 'danger';
    return 'warning';
};

/* =========================
   Componente
========================= */

export default function LigaMercado() {
    const liga = getLigaFromWindow();
    const clube = getClubeFromWindow();
    const mercado = getMercadoFromWindow();

    const players = mercado.players || [];

    const [feedback, setFeedback] = useState('');
    const [loadingId, setLoadingId] = useState(null);

    // Top bar
    const [q, setQ] = useState('');
    const [filtersOpen, setFiltersOpen] = useState(false);

    // Filters
    const [statusFilter, setStatusFilter] = useState('all');
    const [positionFilter, setPositionFilter] = useState('all');
    const [ovrFilter, setOvrFilter] = useState('all');
    const [clubFilter, setClubFilter] = useState('all');

    // Sort + paging
    const [sortKey, setSortKey] = useState('overall'); // overall | value_eur | wage_eur | name
    const [sortDir, setSortDir] = useState('desc');
    const [page, setPage] = useState(1);
    const perPage = 25;

    const backgroundStyles = {
        '--mco-cover': `url(${backgroundDefault})`,
        '--mco-cover-mobile': `url(${backgroundVertical})`,
    };

    // Close modal on ESC
    useEffect(() => {
        if (!filtersOpen) return;

        const onKeyDown = (e) => {
            if (e.key === 'Escape') setFiltersOpen(false);
        };

        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [filtersOpen]);

    const positionOptions = useMemo(() => {
        const set = new Set();
        players.forEach((p) => normalizePositions(p.player_positions).forEach((pos) => set.add(pos)));
        return ['all', ...Array.from(set).sort()];
    }, [players]);

    const clubOptions = useMemo(() => {
        const set = new Set();
        players.forEach((p) => {
            if (p.club_name) set.add(p.club_name);
        });
        return ['all', ...Array.from(set).sort()];
    }, [players]);

    const matchesOvr = (overall) => {
        const ovr = Number(overall ?? 0);
        if (ovrFilter === 'all') return true;
        if (ovrFilter === '90+') return ovr >= 90;
        const [min, max] = ovrFilter.split('-').map(Number);
        return Number.isFinite(min) && Number.isFinite(max) ? ovr >= min && ovr <= max : true;
    };

    const filtered = useMemo(() => {
        const query = q.trim().toLowerCase();

        const base = players.filter((p) => {
            // Search
            if (query) {
                const name = getPlayerName(p).toLowerCase();
                const clubName = (p.club_name || '').toString().toLowerCase();
                if (!name.includes(query) && !clubName.includes(query)) return false;
            }

            // Status
            if (statusFilter !== 'all' && p.club_status !== statusFilter) return false;

            // Position
            if (positionFilter !== 'all') {
                const pos = normalizePositions(p.player_positions);
                if (!pos.includes(positionFilter)) return false;
            }

            // OVR
            if (!matchesOvr(p.overall)) return false;

            // Club (only if player has it)
            if (clubFilter !== 'all') {
                const clubName = (p.club_name || '').toString();
                if (clubName !== clubFilter) return false;
            }

            return true;
        });

        const dir = sortDir === 'asc' ? 1 : -1;

        base.sort((a, b) => {
            if (sortKey === 'name') {
                const an = getPlayerName(a).toLowerCase();
                const bn = getPlayerName(b).toLowerCase();
                return an.localeCompare(bn) * dir;
            }

            const av = Number(a?.[sortKey] ?? -1);
            const bv = Number(b?.[sortKey] ?? -1);
            if (av === bv) {
                const an = getPlayerName(a).toLowerCase();
                const bn = getPlayerName(b).toLowerCase();
                return an.localeCompare(bn);
            }
            return (av - bv) * dir;
        });

        return base;
    }, [players, q, statusFilter, positionFilter, ovrFilter, clubFilter, sortKey, sortDir]);

    const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
    const safePage = Math.min(Math.max(1, page), totalPages);
    const pageItems = filtered.slice((safePage - 1) * perPage, safePage * perPage);

    useEffect(() => {
        // se filtros mudarem, volta pra página 1
        setPage(1);
    }, [q, statusFilter, positionFilter, ovrFilter, clubFilter]);

    const toggleSort = (key) => {
        if (key === sortKey) {
            setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
        } else {
            setSortKey(key);
            setSortDir(key === 'name' ? 'asc' : 'desc');
        }
    };

    const clearFilters = () => {
        setStatusFilter('all');
        setPositionFilter('all');
        setOvrFilter('all');
        setClubFilter('all');
    };

    /* =========================
       Ações
========================= */

    const handleAction = async (player, type) => {
        if (!liga || !clube) {
            setFeedback('Você precisa criar um clube antes de operar no mercado.');
            return;
        }

        if (loadingId) return;

        setLoadingId(player.elencopadrao_id);
        setFeedback('');

        const baseUrl = `/api/ligas/${liga.id}/clubes/${clube.id}`;
        const endpoint = type === 'multa' ? 'multa' : 'comprar';

        try {
            const { data } = await window.axios.post(`${baseUrl}/${endpoint}`, {
                elencopadrao_id: player.elencopadrao_id,
            });
            setFeedback(data?.message ?? 'Operação concluída.');
        } catch (e) {
            setFeedback(e.response?.data?.message ?? 'Erro na operação.');
        } finally {
            setLoadingId(null);
        }
    };

    const renderAction = (player) => {
        // Livre => comprar
        if (player.club_status === 'livre') {
            return (
                <button
                    type="button"
                    className="btn-primary"
                    onClick={() => handleAction(player, 'comprar')}
                    disabled={loadingId === player.elencopadrao_id}
                >
                    {loadingId === player.elencopadrao_id ? 'Operando...' : 'Comprar'}
                </button>
            );
        }

        // Outro => multa/roubar
        if (player.club_status === 'outro') {
            return (
                <button
                    type="button"
                    className="btn-outline"
                    onClick={() => handleAction(player, 'multa')}
                    disabled={loadingId === player.elencopadrao_id}
                >
                    {loadingId === player.elencopadrao_id ? 'Operando...' : 'Roubar (multa)'}
                </button>
            );
        }

        // Meu => por enquanto vai para Meu Elenco (falta elenco_id no payload do mercado)
        if (player.club_status === 'meu') {
            return (
                <a className="btn-outline" href={`/minha_liga/meu-elenco?liga_id=${liga.id}`}>
                    Meu elenco
                </a>
            );
        }

        return <span>—</span>;
    };

    if (!liga) {
        return (
            <main className="liga-mercado-screen" style={backgroundStyles}>
                <p className="ligas-empty">Liga indisponível.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    return (
        <main className="liga-mercado-screen" style={backgroundStyles}>
            {/* HERO */}
            <section className="liga-dashboard-hero">
                <p className="ligas-eyebrow">MERCADO</p>
                <h1 className="ligas-title">Jogadores da liga</h1>
                <p className="ligas-subtitle">
                    {clube ? `Operando como ${clube.nome}` : 'Crie seu clube para negociar no mercado.'}
                </p>
            </section>

            {/* TOP BAR: Busca + Botão Filtros */}
            <section className="mercado-filters" aria-label="Busca e filtros do mercado">
                <div className="mercado-search">
                    <span className="mercado-search-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                            <path
                                fill="currentColor"
                                d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 1 0-.71.71l.27.28v.79l4.25 4.25 1.5-1.5L15.5 14zm-6 0a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9z"
                            />
                        </svg>
                    </span>
                    <input
                        className="mercado-search-input"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Buscar jogador ou clube..."
                        aria-label="Buscar jogador ou clube"
                    />
                </div>

                <button type="button" className="btn-outline mercado-filters-button" onClick={() => setFiltersOpen(true)}>
                    Filtros
                </button>
            </section>

            {/* MODAL CENTRAL DE FILTROS */}
            {filtersOpen && (
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-label="Filtros do mercado"
                    className="mercado-drawer-backdrop"
                    onMouseDown={(e) => {
                        if (e.target === e.currentTarget) setFiltersOpen(false);
                    }}
                >
                    <div className="mercado-drawer">
                        <div className="mercado-drawer-header">
                            <div>
                                <p className="mercado-drawer-eyebrow">Ajuste os filtros</p>
                                <strong>Mercado de jogadores</strong>
                            </div>
                            <button type="button" className="btn-outline" onClick={() => setFiltersOpen(false)}>
                                Fechar
                            </button>
                        </div>

                        <div className="mercado-drawer-body">
                            {/* STATUS (pills) */}
                            <div className="filter-pill-row">
                                {STATUS_FILTERS.map((f) => (
                                    <button
                                        key={f.value}
                                        type="button"
                                        className={`filter-pill${statusFilter === f.value ? ' active' : ''}`}
                                        onClick={() => setStatusFilter(f.value)}
                                    >
                                        {f.label}
                                    </button>
                                ))}
                            </div>

                            {/* SELECTS */}
                            <div className="mercado-drawer-grid">
                                <select value={positionFilter} onChange={(e) => setPositionFilter(e.target.value)}>
                                    {positionOptions.map((p) => (
                                        <option key={p} value={p}>
                                            {p === 'all' ? 'Posição (todas)' : p}
                                        </option>
                                    ))}
                                </select>

                                <select value={ovrFilter} onChange={(e) => setOvrFilter(e.target.value)}>
                                    {OVR_FILTERS.map((o) => (
                                        <option key={o.value} value={o.value}>
                                            {o.label}
                                        </option>
                                    ))}
                                </select>

                                <select value={clubFilter} onChange={(e) => setClubFilter(e.target.value)}>
                                    {clubOptions.map((c) => (
                                        <option key={c} value={c}>
                                            {c === 'all' ? 'Clube (todos)' : c}
                                        </option>
                                    ))}
                                </select>

                                <select
                                    value={`${sortKey}:${sortDir}`}
                                    onChange={(e) => {
                                        const [k, d] = e.target.value.split(':');
                                        setSortKey(k);
                                        setSortDir(d);
                                    }}
                                >
                                    <option value="overall:desc">Ordenar: OVR (maior)</option>
                                    <option value="overall:asc">Ordenar: OVR (menor)</option>
                                    <option value="value_eur:desc">Ordenar: Valor (maior)</option>
                                    <option value="value_eur:asc">Ordenar: Valor (menor)</option>
                                    <option value="wage_eur:desc">Ordenar: Salário (maior)</option>
                                    <option value="wage_eur:asc">Ordenar: Salário (menor)</option>
                                    <option value="name:asc">Ordenar: Nome (A–Z)</option>
                                    <option value="name:desc">Ordenar: Nome (Z–A)</option>
                                </select>
                            </div>

                            {/* Ações do modal */}
                            <div className="mercado-drawer-actions">
                                <button type="button" className="btn-outline" onClick={clearFilters}>
                                    Limpar filtros
                                </button>

                                <button type="button" className="btn-primary" onClick={() => setFiltersOpen(false)}>
                                    Ver resultados ({filtered.length})
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            

            {/* TABELA (DESKTOP + MOBILE) */}
            <section className="mercado-table-wrap" aria-label="Tabela do mercado">
                <div className="mercado-table-scroll">
                    <table className="mercado-table">
                        <thead>
                            <tr>
                                <th onClick={() => toggleSort('name')} className="sortable">
                                    Jogador {sortKey === 'name' ? (sortDir === 'asc' ? '↑' : '↓') : ''}
                                </th>
                                <th onClick={() => toggleSort('overall')} className="sortable col-compact">
                                    OVR {sortKey === 'overall' ? (sortDir === 'asc' ? '↑' : '↓') : ''}
                                </th>
                                <th className="col-compact">POS</th>
                                <th onClick={() => toggleSort('value_eur')} className="sortable col-compact numeric">
                                    Valor {sortKey === 'value_eur' ? (sortDir === 'asc' ? '↑' : '↓') : ''}
                                </th>
                                <th onClick={() => toggleSort('wage_eur')} className="sortable col-compact numeric">
                                    Salário {sortKey === 'wage_eur' ? (sortDir === 'asc' ? '↑' : '↓') : ''}
                                </th>
                                <th className="col-action">Ação</th>
                            </tr>
                        </thead>

                        <tbody>
                            {pageItems.length === 0 ? (
                                <tr>
                                    <td colSpan={6} style={{ padding: 16, opacity: 0.85 }}>
                                        Nenhum jogador encontrado.
                                    </td>
                                </tr>
                            ) : (
                                pageItems.map((p) => {
                                    const name = getPlayerName(p) || '—';
                                    const pos = normalizePositions(p.player_positions)[0] || '—';

                                    const clubMini =
                                        p.club_status === 'livre'
                                            ? 'Livre'
                                            : p.club_status === 'meu'
                                            ? 'Meu clube'
                                            : p.club_name
                                            ? p.club_name
                                            : 'Outro clube';
                                    const ovrTone = getOvrTone(p.overall);

                                    return (
                                        <tr key={p.elencopadrao_id}>
                                            {/* Jogador */}
                                            <td>
                                                <div className="mercado-player-cell">
                                                    <div className="mercado-avatar-sm">
                                                        {p.player_face_url ? (
                                                            <img
                                                                src={proxyFaceUrl(p.player_face_url)}
                                                                alt={name}
                                                                loading="lazy"
                                                                decoding="async"
                                                            />
                                                        ) : (
                                                            <span className="mercado-avatar-fallback">
                                                                {name.slice(0, 2).toUpperCase()}
                                                            </span>
                                                        )}
                                                    </div>

                                                    <div className="mercado-player-meta">
                                                        <strong>{name}</strong>
                                                        <span>{clubMini}</span>
                                                    </div>
                                                </div>
                                            </td>

                                            {/* OVR */}
                                            <td>
                                                <span className={`mercado-ovr-badge ovr-${ovrTone}`}>
                                                    {p.overall ?? '—'}
                                                </span>
                                            </td>

                                            {/* POS */}
                                            <td>
                                                <span className="mercado-pos-badge">{pos}</span>
                                            </td>

                                            {/* Valor */}
                                            <td className="numeric">{formatShortMoney(p.value_eur)}</td>

                                            {/* Salário */}
                                            <td className="numeric">{formatShortMoney(p.wage_eur)}</td>

                                            {/* Ação */}
                                            <td>{renderAction(p)}</td>
                                        </tr>
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>
            </section>

            {/* RESULTADOS + PAGINAÇÃO TOPO */}
            <section
                className="mercado-pagination"
                aria-label="Resumo e paginação do mercado"
                style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 12 }}
            >
                <span style={{ opacity: 0.9 }}>
                    {filtered.length === 0 ? 'Nenhum resultado' : `${filtered.length} jogador(es)`}
                </span>

                <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                    <button
                        type="button"
                        className="btn-outline"
                        onClick={() => setPage((p) => Math.max(1, p - 1))}
                        disabled={safePage <= 1}
                    >
                        Voltar
                    </button>
                    <span>
                        Página {safePage} / {totalPages}
                    </span>
                    <button
                        type="button"
                        className="btn-outline"
                        onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                        disabled={safePage >= totalPages}
                    >
                        Próxima
                    </button>
                </div>
            </section>

            {/* FEEDBACK */}
            {feedback && (
                <Alert
                    variant={resolveFeedbackVariant(feedback)}
                    title="Aviso"
                    floating
                    onClose={() => setFeedback('')}
                >
                    {feedback}
                </Alert>
            )}

            <Navbar active="ligas" />
        </main>
    );
}
