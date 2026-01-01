import { useMemo, useState, useEffect } from 'react';
import Navbar from '../components/app_publico/Navbar';
import Alert from '../components/app_publico/Alert';
import PlayerDetailModal from '../components/app_publico/PlayerDetailModal';

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

const countFormatter = new Intl.NumberFormat('pt-BR');

const formatCount = (value) => countFormatter.format(value ?? 0);

const parseMillionsInput = (value) => {
    if (value === null || value === undefined) return null;
    const cleaned = value.toString().replace(',', '.').replace(/[^\d.]/g, '');
    if (!cleaned) return null;
    const num = Number(cleaned);
    if (!Number.isFinite(num)) return null;
    return num * 1_000_000;
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
        .map((p) => p.trim().toUpperCase())
        .filter(Boolean);
};

const getRadarOnlyFromQuery = () => {
    const params = new URLSearchParams(window.location.search);
    const value = (params.get('radar') || '').toLowerCase();
    return value === '1' || value === 'true' || value === 'sim';
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
    { value: 'outro', label: 'Rivais' },
];

const MODAL_MODES = {
    BUY: 'buy',
    MULTA: 'multa',
};

const OVR_FILTERS = [
    { value: 'all', label: 'Todos' },
    { value: '90+', label: 'Elite 90+' },
    { value: '85-89', label: 'Ouro 85–89' },
    { value: '80-84', label: 'Prata 80–84' },
];

const POSITION_GROUPS = [
    {
        sector: 'goleiros',
        label: 'Goleiros',
        items: [{ code: 'GK', ptbr: 'GOL', label: 'Goleiro' }],
    },
    {
        sector: 'defensores',
        label: 'Defensores',
        items: [
            { code: 'RB', ptbr: 'LD', label: 'Lateral Direito' },
            { code: 'CB', ptbr: 'ZAG', label: 'Zagueiro' },
            { code: 'LB', ptbr: 'LE', label: 'Lateral Esquerdo' },
        ],
    },
    {
        sector: 'meio-campistas',
        label: 'Meio Campistas',
        items: [
            { code: 'CDM', ptbr: 'VOL', label: 'Volante' },
            { code: 'CM', ptbr: 'MC', label: 'Meia Central' },
            { code: 'CAM', ptbr: 'MEI', label: 'Meia Ofensivo' },
            { code: 'RM', ptbr: 'MD', label: 'Meia Direito' },
            { code: 'LM', ptbr: 'ME', label: 'Meia Esquerda' },
        ],
    },
    {
        sector: 'atacantes',
        label: 'Atacantes',
        items: [
            { code: 'RW', ptbr: 'PD', label: 'Ponta Direita' },
            { code: 'ST', ptbr: 'ATA', label: 'Atacante' },
            { code: 'LW', ptbr: 'PE', label: 'Ponta Esquerda' },
        ],
    },
];

const POSITION_SECTORS = POSITION_GROUPS.map((group) => ({
    value: group.sector,
    label: group.label,
}));

const POSITION_SECTOR_BY_CODE = POSITION_GROUPS.reduce((acc, group) => {
    group.items.forEach((item) => {
        acc[item.code] = group.sector;
    });
    return acc;
}, {});

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
    const marketClosed = Boolean(mercado?.closed);
    const closedPeriod = mercado?.period ?? null;
    const closedPeriodLabel =
        closedPeriod?.inicio_label && closedPeriod?.fim_label
            ? `O mercado está fechado durante o período de partidas (${closedPeriod.inicio_label} até ${closedPeriod.fim_label}).`
            : 'O mercado está fechado durante o período de partidas.';

    const [playersData, setPlayersData] = useState(mercado.players || []);
    const [radarIds, setRadarIds] = useState(
        () => new Set(Array.isArray(mercado?.radar_ids) ? mercado.radar_ids : []),
    );
    const [radarBusyIds, setRadarBusyIds] = useState(() => new Set());
    const [clubBalance, setClubBalance] = useState(clube?.saldo ?? 0);
    const [clubSalaryPerRound, setClubSalaryPerRound] = useState(clube?.salary_per_round ?? 0);

    const [modalPlayer, setModalPlayer] = useState(null);
    const [modalMode, setModalMode] = useState(null);
    const [isModalSubmitting, setIsModalSubmitting] = useState(false);
    const [modalError, setModalError] = useState('');

    const [detailPlayer, setDetailPlayer] = useState(null);
    const [detailExpanded, setDetailExpanded] = useState(false);
    const [detailLoading, setDetailLoading] = useState(false);
    const [detailError, setDetailError] = useState('');
    const [detailCache, setDetailCache] = useState({});

    const [feedback, setFeedback] = useState('');

    // Top bar
    const [q, setQ] = useState('');
    const [filtersOpen, setFiltersOpen] = useState(false);

    // Filters
    const [statusFilter, setStatusFilter] = useState('all');
    const [positionSectorFilter, setPositionSectorFilter] = useState('all');
    const [positionFilter, setPositionFilter] = useState('all');
    const [ovrFilter, setOvrFilter] = useState('all');
    const [radarOnly, setRadarOnly] = useState(getRadarOnlyFromQuery);
    const [clubFilter, setClubFilter] = useState('all');
    const [minValue, setMinValue] = useState('');
    const [maxValue, setMaxValue] = useState('');

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

    const positionGroupsForUI = useMemo(() => {
        if (positionSectorFilter === 'all') {
            return POSITION_GROUPS;
        }
        const group = POSITION_GROUPS.find((item) => item.sector === positionSectorFilter);
        return group ? [group] : [];
    }, [positionSectorFilter]);

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
        const minValueEur = parseMillionsInput(minValue);
        const maxValueEur = parseMillionsInput(maxValue);

        const base = playersData.filter((p) => {
            // Search
            if (query) {
                const name = getPlayerName(p).toLowerCase();
                const clubName = (p.club_name || '').toString().toLowerCase();
                if (!name.includes(query) && !clubName.includes(query)) return false;
            }

            // Status
            if (statusFilter !== 'all' && p.club_status !== statusFilter) return false;

            // Radar
            if (radarOnly && !radarIds.has(p.elencopadrao_id)) return false;

            // Position
            if (positionSectorFilter !== 'all') {
                const pos = normalizePositions(p.player_positions);
                const matchesSector = pos.some(
                    (code) => POSITION_SECTOR_BY_CODE[code] === positionSectorFilter,
                );
                if (!matchesSector) return false;
            }

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

            const valueEur = Number(p?.value_eur ?? 0);
            if (Number.isFinite(minValueEur) && minValueEur !== null && valueEur < minValueEur) {
                return false;
            }
            if (Number.isFinite(maxValueEur) && maxValueEur !== null && valueEur > maxValueEur) {
                return false;
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
    }, [
        playersData,
        q,
        statusFilter,
        radarOnly,
        radarIds,
        positionSectorFilter,
        positionFilter,
        ovrFilter,
        clubFilter,
        minValue,
        maxValue,
        sortKey,
        sortDir,
    ]);

    const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
    const safePage = Math.min(Math.max(1, page), totalPages);
    const pageItems = filtered.slice((safePage - 1) * perPage, safePage * perPage);

    useEffect(() => {
        // se filtros mudarem, volta pra página 1
        setPage(1);
    }, [q, statusFilter, radarOnly, positionSectorFilter, positionFilter, ovrFilter, clubFilter, minValue, maxValue]);

    useEffect(() => {
        if (!detailPlayer) return;

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                setDetailPlayer(null);
                setDetailExpanded(false);
                setDetailError('');
            }
        };

        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [detailPlayer]);

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
        setPositionSectorFilter('all');
        setPositionFilter('all');
        setOvrFilter('all');
        setRadarOnly(false);
        setClubFilter('all');
        setMinValue('');
        setMaxValue('');
    };

    /* =========================
       Ações
========================= */

    const openMarketModal = (player, mode) => {
        if (!clube || !liga) {
            setFeedback('Você precisa criar um clube antes de operar no mercado.');
            return;
        }

        if (marketClosed) {
            setFeedback(closedPeriodLabel);
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

    const detailData = detailPlayer ? detailCache[detailPlayer.elencopadrao_id] : null;
    const detailSnapshot = detailData ?? detailPlayer;

    const openDetailModal = (player) => {
        setDetailPlayer(player);
        setDetailExpanded(false);
        setDetailError('');
        if (player?.elencopadrao_id) {
            void loadDetailData(player.elencopadrao_id, { expand: false });
        }
    };

    const closeDetailModal = () => {
        setDetailPlayer(null);
        setDetailExpanded(false);
        setDetailError('');
        setDetailLoading(false);
    };

    const loadDetailData = async (playerId, { expand } = { expand: true }) => {
        if (!playerId) {
            return;
        }

        if (detailCache[playerId]) {
            if (expand) {
                setDetailExpanded(true);
            }
            return;
        }

        setDetailLoading(true);
        setDetailError('');

        try {
            const { data } = await window.axios.get(`/api/elencopadrao/${playerId}`);
            const payload = data?.player ?? data ?? null;

            if (payload) {
                setDetailCache((prev) => ({ ...prev, [playerId]: payload }));
                if (expand) {
                    setDetailExpanded(true);
                }
            } else {
                setDetailError('Não foi possível carregar a ficha completa.');
            }
        } catch (error) {
            setDetailError(
                error.response?.data?.message ?? 'Não foi possível carregar a ficha completa.',
            );
        } finally {
            setDetailLoading(false);
        }
    };

    const handleToggleDetails = async () => {
        if (!detailPlayer) return;

        if (detailExpanded) {
            setDetailExpanded(false);
            return;
        }

        await loadDetailData(detailPlayer.elencopadrao_id, { expand: true });
    };

    const detailAction = detailPlayer
        ? (() => {
              if (marketClosed) {
                  return { label: 'Mercado fechado', disabled: true, action: null };
              }

              if (!clube) {
                  return { label: 'Crie seu clube', disabled: true, action: null };
              }

              if (detailPlayer.club_status === 'livre') {
                  return { label: 'Contratar jogador', disabled: false, action: MODAL_MODES.BUY };
              }

              if (detailPlayer.club_status === 'outro') {
                  return { label: 'Pagar multa', disabled: false, action: MODAL_MODES.MULTA };
              }

              if (detailPlayer.club_status === 'meu') {
                  return { label: 'No clube', disabled: true, action: null };
              }

              return { label: 'Indisponível', disabled: true, action: null };
          })()
        : null;

    const detailStatusLabel = detailPlayer
        ? detailPlayer.club_status === 'livre'
            ? 'Livre'
            : detailPlayer.club_status === 'meu'
            ? 'Meu clube'
            : detailPlayer.club_name || 'Rivais'
        : '';

    const detailPrimaryAction = detailAction
        ? {
              label: detailAction.label,
              disabled: detailAction.disabled,
              onClick: () => {
                  if (detailAction.disabled) {
                      return;
                  }

                  closeDetailModal();
                  if (detailAction.action === MODAL_MODES.BUY) {
                      openPurchaseModal(detailPlayer);
                  } else if (detailAction.action === MODAL_MODES.MULTA) {
                      openMultaModal(detailPlayer);
                  }
              },
          }
        : null;

    const detailScoutAction = detailPlayer
        ? {
              label: radarIds.has(detailPlayer.elencopadrao_id) ? 'Remover do radar' : 'Enviar olheiro',
              disabled: radarBusyIds.has(detailPlayer.elencopadrao_id),
              onClick: () => toggleRadar(detailPlayer),
          }
        : null;

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
    const baseValue = Number(
        (modalMode === MODAL_MODES.MULTA ? modalPlayer.entry_value_eur : null) ??
            modalPlayer.value_eur ??
            0,
    );

    if (modalMode === MODAL_MODES.BUY) {
        return baseValue;
    }

    const multiplier = Number(liga?.multa_multiplicador ?? 2) || 2;
    return Math.round(baseValue * multiplier);
    };

    const handleModalConfirm = async () => {
        if (!modalPlayer || !liga || !clube || !modalMode) return;
        if (marketClosed) {
            setModalError(closedPeriodLabel);
            return;
        }

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

    const toggleRadar = async (player) => {
        if (!liga) return;

        const playerId = player?.elencopadrao_id;
        if (!playerId) return;

        setRadarBusyIds((prev) => {
            const next = new Set(prev);
            next.add(playerId);
            return next;
        });

        try {
            const { data } = await window.axios.post(`/api/ligas/${liga.id}/favoritos`, {
                elencopadrao_id: playerId,
            });

            setRadarIds((prev) => {
                const next = new Set(prev);
                if (data?.status === 'removed') {
                    next.delete(playerId);
                } else {
                    next.add(playerId);
                }
                return next;
            });

            const name = getPlayerName(player) || 'Jogador';
            if (data?.status === 'removed') {
                setFeedback(`Olheiro removeu ${name} do radar.`);
            } else {
                setFeedback(`O olheiro iniciou o monitoramento de ${name}.`);
            }
        } catch (error) {
            setFeedback(
                error.response?.data?.message ?? 'Nao foi possivel atualizar o radar.',
            );
        } finally {
            setRadarBusyIds((prev) => {
                const next = new Set(prev);
                next.delete(playerId);
                return next;
            });
        }
    };

    const renderAction = (player) => {
        const isPlayerModalActive =
            modalPlayer && modalPlayer.elencopadrao_id === player.elencopadrao_id;
        const isBuyActive = modalMode === MODAL_MODES.BUY && isPlayerModalActive;
        const isMultaActive = modalMode === MODAL_MODES.MULTA && isPlayerModalActive;

        if (player.club_status === 'livre') {
            const isDisabled = !clube;
            if (marketClosed) {
                return (
                    <span className="table-action-badge neutral" aria-label="Mercado fechado">
                        Fechado
                    </span>
                );
            }
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
            if (marketClosed) {
                return (
                    <span className="table-action-badge neutral" aria-label="Mercado fechado">
                        Fechado
                    </span>
                );
            }
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
            {marketClosed && (
                <Alert
                    variant="warning"
                    title="Mercado fechado"
                    description={closedPeriodLabel}
                />
            )}

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
                    <div className="mercado-drawer mercado-drawer-scout">
                        <div className="mercado-drawer-header">
                            <div>
                                <p className="mercado-drawer-eyebrow">Central de scouting</p>
                                <strong>Painel de transferências</strong>
                            </div>
                            <button type="button" className="btn-outline" onClick={() => setFiltersOpen(false)}>
                                Fechar
                            </button>
                        </div>

                        <div className="mercado-drawer-body mercado-drawer-body-scout">
                            {/* STATUS (pills) */}
                            <div className="filter-pill-row filter-pill-row-scout">
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

                            <div className="mercado-drawer-grid mercado-drawer-grid-scout">
                                <div className="mercado-drawer-field mercado-drawer-field-full">
                                    <span className="mercado-drawer-label">Radar</span>
                                    <div className="filter-pill-row filter-pill-row-scout filter-pill-row-compact">
                                        <button
                                            type="button"
                                            className={`filter-pill${!radarOnly ? ' active' : ''}`}
                                            onClick={() => setRadarOnly(false)}
                                        >
                                            Todos
                                        </button>
                                        <button
                                            type="button"
                                            className={`filter-pill${radarOnly ? ' active' : ''}`}
                                            onClick={() => setRadarOnly(true)}
                                        >
                                            Apenas no radar
                                        </button>
                                    </div>
                                </div>
                                <div className="mercado-drawer-field">
                                    <label className="mercado-drawer-label" htmlFor="filtro-setor">
                                        Setor
                                    </label>
                                    <select
                                        id="filtro-setor"
                                        className="mercado-drawer-select"
                                        value={positionSectorFilter}
                                        onChange={(e) => {
                                            const value = e.target.value;
                                            setPositionSectorFilter(value);
                                            if (value !== 'all') {
                                                const group = POSITION_GROUPS.find(
                                                    (item) => item.sector === value,
                                                );
                                                const allowed = group?.items ?? [];
                                                if (!allowed.some((item) => item.code === positionFilter)) {
                                                    setPositionFilter('all');
                                                }
                                            }
                                        }}
                                    >
                                        <option value="all">Todos</option>
                                        {POSITION_SECTORS.map((sector) => (
                                            <option key={sector.value} value={sector.value}>
                                                {sector.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="mercado-drawer-field">
                                    <label className="mercado-drawer-label" htmlFor="filtro-posicao">
                                        Posição
                                    </label>
                                    <select
                                        id="filtro-posicao"
                                        className="mercado-drawer-select"
                                        value={positionFilter}
                                        onChange={(e) => setPositionFilter(e.target.value)}
                                    >
                                        <option value="all">Todas</option>
                                        {positionGroupsForUI.map((group) => (
                                            <optgroup key={group.sector} label={group.label}>
                                                {group.items.map((item) => (
                                                    <option key={item.code} value={item.code}>
                                                        {item.ptbr} · {item.label}
                                                    </option>
                                                ))}
                                            </optgroup>
                                        ))}
                                    </select>
                                </div>

                                <div className="mercado-drawer-field">
                                    <span className="mercado-drawer-label">Qualidade (OVR)</span>
                                    <div className="filter-pill-row filter-pill-row-scout filter-pill-row-compact">
                                        {OVR_FILTERS.map((o) => (
                                            <button
                                                key={o.value}
                                                type="button"
                                                className={`filter-pill${ovrFilter === o.value ? ' active' : ''}`}
                                                onClick={() => setOvrFilter(o.value)}
                                            >
                                                {o.label}
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                <div className="mercado-drawer-field mercado-drawer-field-full">
                                    <span className="mercado-drawer-label">Valor de mercado (M)</span>
                                    <div className="mercado-drawer-range">
                                        <input
                                            className="mercado-drawer-input"
                                            type="text"
                                            inputMode="decimal"
                                            placeholder="Mín (M)"
                                            value={minValue}
                                            onChange={(e) => setMinValue(e.target.value)}
                                            aria-label="Valor mínimo em milhões"
                                        />
                                        <span className="mercado-drawer-range-separator">até</span>
                                        <input
                                            className="mercado-drawer-input"
                                            type="text"
                                            inputMode="decimal"
                                            placeholder="Máx (M)"
                                            value={maxValue}
                                            onChange={(e) => setMaxValue(e.target.value)}
                                            aria-label="Valor máximo em milhões"
                                        />
                                    </div>
                                </div>

                                <div className="mercado-drawer-field mercado-drawer-field-full">
                                    <label className="mercado-drawer-label" htmlFor="filtro-clube">
                                        Vínculo com clube
                                    </label>
                                    <select
                                        id="filtro-clube"
                                        className="mercado-drawer-select"
                                        value={clubFilter}
                                        onChange={(e) => setClubFilter(e.target.value)}
                                    >
                                        {clubOptions.map((c) => (
                                            <option key={c} value={c}>
                                                {c === 'all' ? 'Qualquer clube' : c}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="mercado-drawer-field mercado-drawer-field-full">
                                    <label className="mercado-drawer-label" htmlFor="filtro-ordenacao">
                                        Ordenar resultados
                                    </label>
                                    <select
                                        id="filtro-ordenacao"
                                        className="mercado-drawer-select mercado-drawer-select-highlight"
                                        value={`${sortKey}:${sortDir}`}
                                        onChange={(e) => {
                                            const [k, d] = e.target.value.split(':');
                                            setSortKey(k);
                                            setSortDir(d);
                                        }}
                                    >
                                        <option value="overall:desc">OVR (maior)</option>
                                        <option value="overall:asc">OVR (menor)</option>
                                        <option value="value_eur:desc">Valor (maior)</option>
                                        <option value="value_eur:asc">Valor (menor)</option>
                                        <option value="wage_eur:desc">Salário (maior)</option>
                                        <option value="wage_eur:asc">Salário (menor)</option>
                                        <option value="name:asc">Nome (A–Z)</option>
                                        <option value="name:desc">Nome (Z–A)</option>
                                    </select>
                                </div>
                            </div>

                            {/* Ações do modal */}
                            <div className="mercado-drawer-actions mercado-drawer-actions-scout">
                                <button type="button" className="btn-outline mercado-scout-clear" onClick={clearFilters}>
                                    Limpar filtros
                                </button>

                                <button type="button" className="btn-primary mercado-scout-apply" onClick={() => setFiltersOpen(false)}>
                                    Aplicar Scouting <span className="mercado-scout-count">({formatCount(filtered.length)})</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            

            {/* LISTA MOBILE */}
            <section className="mercado-table-wrap" aria-label="Resultados do mercado" style={{ marginTop: 20 }}>
                <div className="mercado-list-header">
                    <span>Jogador / OVR</span>
                    <span>Valores / Ação</span>
                </div>
                <div className="mercado-player-list">
                    {pageItems.length === 0 ? (
                        <p className="mercado-no-results">Nenhum jogador encontrado.</p>
                    ) : (
                        pageItems.map((p) => {
                            const name = getPlayerName(p) || '—';
                            const positionBadge = normalizePositions(p.player_positions)[0] || '—';
                            const ovrTone = getOvrTone(p.overall);
                            const statusLabel =
                                p.club_status === 'livre'
                                    ? 'Livre'
                                    : p.club_status === 'meu'
                                    ? 'Meu clube'
                                    : p.club_name || 'Outro clube';
                            const isRadar = radarIds.has(p.elencopadrao_id);
                            const isRadarBusy = radarBusyIds.has(p.elencopadrao_id);

                            return (
                                <article key={p.elencopadrao_id} className={`mercado-player-card status-${p.club_status}`}>
                                    {isRadar && (
                                        <span className="mercado-player-radar-badge">
                                            📡 No radar
                                        </span>
                                    )}
                                    <div className="mercado-player-card-content">
                                        <span className={`mercado-ovr-badge ovr-${ovrTone}`}>
                                            {p.overall ?? '—'}
                                        </span>
                                        <button
                                            type="button"
                                            className="mercado-player-avatar-button"
                                            onClick={() => openDetailModal(p)}
                                            aria-label={`Ver ficha completa de ${name}`}
                                        >
                                            <span className="mercado-player-avatar">
                                                <PlayerAvatar
                                                    src={proxyFaceUrl(p.player_face_url)}
                                                    alt={name}
                                                    fallback={name.slice(0, 2).toUpperCase()}
                                                />
                                                <span className="mercado-player-position">{positionBadge}</span>
                                            </span>
                                        </button>
                                        <div className="mercado-player-info">
                                            <strong>{name}</strong>
                                            <span>{statusLabel}</span>
                                        </div>
                                    </div>
                                    <div className="mercado-player-card-right">
                                        <div className="mercado-player-values">
                                            <div className="mercado-player-value-row">
                                                <span className="mercado-player-value">{formatShortMoney(p.value_eur)}</span>
                                                <button
                                                    type="button"
                                                    className={`mercado-player-scout-button${isRadar ? ' active' : ''}`}
                                                    onClick={() => toggleRadar(p)}
                                                    disabled={isRadarBusy}
                                                    aria-label={isRadar ? 'Remover do radar' : 'Enviar olheiro'}
                                                    title={isRadar ? 'Remover do radar' : 'Enviar olheiro'}
                                                >
                                                    🔭
                                                </button>
                                            </div>
                                            <span className="mercado-player-salary">
                                                SAL: {formatShortMoney(p.wage_eur)}
                                            </span>
                                        </div>
                                        <div className="mercado-player-action">{renderAction(p)}</div>
                                    </div>
                                </article>
                            );
                        })
                    )}
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
                        <div className="meu-elenco-modal-actions" style={{marginTop: 10}}>
                            <button
                                type="button"
                                className="btn-outline"
                                onClick={closeModal}
                                disabled={isModalSubmitting}

                                style={{ marginRight: 8 }}
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

            {detailPlayer && (
                <PlayerDetailModal
                    player={detailPlayer}
                    snapshot={detailSnapshot}
                    fullData={detailData}
                    expanded={detailExpanded}
                    loading={detailLoading}
                    error={detailError}
                    statusLabel={detailStatusLabel}
                    onClose={closeDetailModal}
                    onToggleDetails={handleToggleDetails}
                    primaryAction={detailPrimaryAction}
                    secondaryAction={detailScoutAction}
                />
            )}

            {/* RESULTADOS + PAGINAÇÃO TOPO */}
            <section className="mco-pagination" aria-label="Resumo e paginação do mercado">
                <span className="mco-pagination-count">
                    <strong>{filtered.length.toLocaleString('pt-BR')}</strong> jogadores encontrados
                </span>
                <div className="mco-pagination-controls">
                    <button
                        type="button"
                        className="btn-outline mco-pagination-button"
                        onClick={() => setPage((p) => Math.max(1, p - 1))}
                        disabled={safePage <= 1}
                    >
                        ◀ Voltar
                    </button>
                    <div className="mco-pagination-label">
                        <span>Página</span>
                        <strong>
                            <span>{safePage}</span> / {totalPages}
                        </strong>
                    </div>
                    <button
                        type="button"
                        className="btn-outline mco-pagination-button"
                        onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                        disabled={safePage >= totalPages}
                    >
                        Próxima ▶
                    </button>
                </div>
                <div className="mco-pagination-progress">
                    <div
                        className="mco-pagination-progress-bar"
                        style={{ width: `${Math.min(100, (safePage / totalPages) * 100)}%` }}
                    />
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
