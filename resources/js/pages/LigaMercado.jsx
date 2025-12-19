import { useMemo, useState, useEffect } from 'react';
import Navbar from '../components/app_publico/Navbar';
import Alert from '../components/app_publico/Alert';

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

const MODAL_MODES = {
    BUY: 'buy',
    MULTA: 'multa',
};

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

function PlayerAvatar({ src, alt, fallback }) {
    const [failed, setFailed] = useState(false);

    if (!src || failed) {
        return <span className="mercado-avatar-fallback">{fallback}</span>;
    }

    return (
        <img
            src={src}
            alt={alt}
            loading="lazy"
            decoding="async"
            onError={() => setFailed(true)}
        />
    );
}

/* =========================
   Componente
========================= */

export default function LigaMercado() {
    const liga = getLigaFromWindow();
    const clube = getClubeFromWindow();
    const mercado = getMercadoFromWindow();

    const [playersData, setPlayersData] = useState(mercado.players || []);
    const [clubBalance, setClubBalance] = useState(clube?.saldo ?? 0);
    const [clubSalaryPerRound, setClubSalaryPerRound] = useState(clube?.salary_per_round ?? 0);

    const [modalPlayer, setModalPlayer] = useState(null);
    const [modalMode, setModalMode] = useState(null);
    const [isModalSubmitting, setIsModalSubmitting] = useState(false);
    const [modalError, setModalError] = useState('');

    const [feedback, setFeedback] = useState('');

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

    // Sync club context when it changes
    useEffect(() => {
        setClubBalance(clube?.saldo ?? 0);
        setClubSalaryPerRound(clube?.salary_per_round ?? 0);
    }, [clube]);

    // Sync players when payload changes
    useEffect(() => {
        setPlayersData(mercado.players || []);
    }, [mercado.players]);

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
        playersData.forEach((p) => normalizePositions(p.player_positions).forEach((pos) => set.add(pos)));
        return ['all', ...Array.from(set).sort()];
    }, [playersData]);

    const clubOptions = useMemo(() => {
        const set = new Set();
        playersData.forEach((p) => {
            if (p.club_name) set.add(p.club_name);
        });
        return ['all', ...Array.from(set).sort()];
    }, [playersData]);

    const matchesOvr = (overall) => {
        const ovr = Number(overall ?? 0);
        if (ovrFilter === 'all') return true;
        if (ovrFilter === '90+') return ovr >= 90;
        const [min, max] = ovrFilter.split('-').map(Number);
        return Number.isFinite(min) && Number.isFinite(max) ? ovr >= min && ovr <= max : true;
    };

    const filtered = useMemo(() => {
        const query = q.trim().toLowerCase();

        const base = playersData.filter((p) => {
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
    }, [playersData, q, statusFilter, positionFilter, ovrFilter, clubFilter, sortKey, sortDir]);

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

    const openMarketModal = (player, mode) => {
        if (!clube || !liga) {
            setFeedback('Você precisa criar um clube antes de operar no mercado.');
            return;
        }

        setModalPlayer(player);
        setModalMode(mode);
        setModalError('');
        setIsModalSubmitting(false);
    };

    const openPurchaseModal = (player) => openMarketModal(player, MODAL_MODES.BUY);
    const openMultaModal = (player) => openMarketModal(player, MODAL_MODES.MULTA);

    const closeModal = () => {
        setModalPlayer(null);
        setModalMode(null);
        setModalError('');
        setIsModalSubmitting(false);
    };

    const applyPlayerToMyClub = (playerId) => {
        setPlayersData((prev) =>
            prev.map((entry) =>
                entry.elencopadrao_id === playerId
                    ? {
                          ...entry,
                          club_status: 'meu',
                          club_name: clube?.nome ?? 'Meu clube',
                          club_id: clube?.id ?? null,
                          is_free_agent: false,
                      }
                    : entry,
            ),
        );
    };

    const getModalPaymentAmount = () => {
        if (!modalPlayer || !modalMode) return 0;
        const baseValue = Number(modalPlayer.value_eur ?? 0);
        if (modalMode === MODAL_MODES.BUY) {
            return baseValue;
        }

        const multiplier = Number(liga?.multa_multiplicador ?? 2) || 2;
        return Math.round(baseValue * multiplier);
    };

    const handleModalConfirm = async () => {
        if (!modalPlayer || !liga || !clube || !modalMode) return;

        setIsModalSubmitting(true);
        setModalError('');

        const isBuy = modalMode === MODAL_MODES.BUY;
        const endpoint = isBuy ? 'comprar' : 'multa';
        const paymentAmount = getModalPaymentAmount();

        try {
            const { data } = await window.axios.post(
                `/api/ligas/${liga.id}/clubes/${clube.id}/${endpoint}`,
                {
                    elencopadrao_id: modalPlayer.elencopadrao_id,
                },
            );

            applyPlayerToMyClub(modalPlayer.elencopadrao_id);
            setClubBalance((prev) => prev - paymentAmount);
            setClubSalaryPerRound((prev) => prev + (modalPlayer.wage_eur ?? 0));
            setFeedback(
                data?.message ??
                    (isBuy
                        ? 'Jogador comprado com sucesso.'
                        : 'Multa paga e jogador transferido com sucesso.'),
            );
            setPage(1);
            closeModal();
        } catch (error) {
            setModalError(
                error.response?.data?.message ??
                    (isBuy ? 'Não foi possível completar a compra.' : 'Não foi possível pagar a multa.'),
            );
        } finally {
            setIsModalSubmitting(false);
        }
    };

    const renderAction = (player) => {
        const isPlayerModalActive =
            modalPlayer && modalPlayer.elencopadrao_id === player.elencopadrao_id;
        const isBuyActive = modalMode === MODAL_MODES.BUY && isPlayerModalActive;
        const isMultaActive = modalMode === MODAL_MODES.MULTA && isPlayerModalActive;

        if (player.club_status === 'livre') {
            const isDisabled = !clube;
            return (
                <button
                    type="button"
                    className={`table-action-badge primary${isDisabled ? ' disabled' : ''}`}
                    onClick={() => openPurchaseModal(player)}
                    disabled={isDisabled || (isBuyActive && isModalSubmitting)}
                >
                    {isBuyActive && isModalSubmitting
                        ? 'Operando...'
                        : isDisabled
                        ? 'Crie seu clube'
                        : 'Comprar'}
                </button>
            );
        }

        if (player.club_status === 'outro') {
            return (
                <button
                    type="button"
                    className={`table-action-badge outline${isMultaActive && isModalSubmitting ? ' disabled' : ''}`}
                    onClick={() => openMultaModal(player)}
                    disabled={isMultaActive && isModalSubmitting}
                >
                    {isMultaActive && isModalSubmitting ? 'Operando...' : 'Roubar (multa)'}
                </button>
            );
        }

        if (player.club_status === 'meu') {
            return (
                <span className="table-action-badge neutral" aria-label="Jogador já pertence ao seu clube">
                    No clube
                </span>
            );
        }

        return <span>—</span>;
    };

    if (!liga) {
        return (
            <main className="liga-mercado-screen">
                <p className="ligas-empty">Liga indisponível.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    const modalPaymentAmount = getModalPaymentAmount();
    const modalTitle =
        modalMode === MODAL_MODES.MULTA
            ? `Pagar multa por ${modalPlayer?.short_name || 'jogador'}`
            : `Comprar ${modalPlayer?.short_name || 'jogador'}`;
    const modalDescription =
        modalMode === MODAL_MODES.MULTA
            ? 'Veja o impacto financeiro antes de pagar a cláusula de rescisão.'
            : 'Veja o impacto financeiro antes de confirmar a compra.';

    return (
        <main className="liga-mercado-screen">
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
            <section className="mercado-table-wrap" aria-label="Tabela do mercado" style={{ marginTop: 20 }}>
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
                                                        <PlayerAvatar
                                                            src={proxyFaceUrl(p.player_face_url)}
                                                            alt={name}
                                                            fallback={name.slice(0, 2).toUpperCase()}
                                                        />
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

            {modalPlayer && (
                <div className="meu-elenco-modal-overlay" role="dialog" aria-modal="true">
                    <div className="meu-elenco-modal">
                        <h3>{modalTitle}</h3>
                        <p className="meu-elenco-modal-description">{modalDescription}</p>
                        <div className="modal-field">
                            <span>Valor que será pago</span>
                            <p style={{ fontWeight: 600 }}>{formatCurrency(modalPaymentAmount)}</p>
                        </div>
                        <div className="modal-field">
                            <span>Salário do jogador</span>
                            <p style={{ fontWeight: 600 }}>{formatCurrency(modalPlayer.wage_eur)}</p>
                        </div>
                        <div className="modal-field">
                            <span>Saldo atual da carteira</span>
                            <p style={{ fontWeight: 600 }}>{formatCurrency(clubBalance)}</p>
                        </div>
                        <div className="modal-field">
                            <span>Saldo após a operação</span>
                            <p style={{ fontWeight: 600 }}>{formatCurrency(clubBalance - modalPaymentAmount)}</p>
                        </div>
                        <div className="modal-field">
                            <span>Custo por rodada atual</span>
                            <p style={{ fontWeight: 600 }}>{formatCurrency(clubSalaryPerRound)}</p>
                        </div>
                        <div className="modal-field">
                            <span>Novo custo por rodada</span>
                            <p style={{ fontWeight: 600 }}>
                                {formatCurrency(clubSalaryPerRound + (modalPlayer.wage_eur || 0))}
                            </p>
                        </div>
                        {modalError && <p className="modal-error">{modalError}</p>}
                        <div className="meu-elenco-modal-actions">
                            <button
                                type="button"
                                className="btn-outline"
                                onClick={closeModal}
                                disabled={isModalSubmitting}
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                className="btn-primary"
                                onClick={handleModalConfirm}
                                disabled={isModalSubmitting}
                            >
                                {isModalSubmitting
                                    ? 'Operando...'
                                    : modalMode === MODAL_MODES.MULTA
                                    ? 'Confirmar multa'
                                    : 'Confirmar compra'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

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
