import { useEffect, useMemo, useState } from 'react';
import Navbar from '../components/app_publico/Navbar';
import PlayerDetailModal from '../components/app_publico/PlayerDetailModal';

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0,
});

const formatCurrency = (value) => {
    if (value === null || typeof value === 'undefined') {
        return '—';
    }
    return currencyFormatter.format(value);
};

const proxyFaceUrl = (url) => {
    if (!url) {
        return null;
    }
    const trimmed = url.replace(/^https?:\/\//, '');
    return `https://images.weserv.nl/?url=${encodeURIComponent(trimmed)}&w=240&h=240`;
};

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getClubeFromWindow = () => window.__CLUBE__ ?? null;
const getMeuElencoFromWindow = () =>
    window.__MEU_ELENCO__ ?? {
        players: [],
        player_count: 0,
        max_players: 0,
        salary_per_round: 0,
    };

const OVERALL_FILTERS = [
    { id: 'all', label: 'OVR: Todos' },
    { id: 'high', label: 'OVR ≥ 90' },
    { id: 'mid', label: 'OVR 85–89' },
    { id: 'low', label: 'OVR ≤ 84' },
];

const SALARY_FILTERS = [
    { id: 'all', label: 'Salário: Todos' },
    { id: 'low', label: '≤ 5K' },
    { id: 'mid', label: '5K–15K' },
    { id: 'high', label: '> 15K' },
];

const STATUS_FILTERS = [
    { id: 'all', label: 'Todos' },
    { id: 'ativo', label: 'Ativos' },
    { id: 'inativo', label: 'Inativos' },
];

const getInitials = (name) => {
    if (!name) return '?';
    const parts = name.split(/\s+/).filter(Boolean);
    if (parts.length === 0) return name.charAt(0).toUpperCase();
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
};

const resolveOvrTone = (overall) => {
    const ovr = Number(overall ?? 0);
    if (ovr >= 80) return 'high';
    if (ovr >= 60) return 'mid';
    return 'low';
};

const TAX_PERCENT = 20;
const PERCENT_OPTIONS = [10, 30, 70, 100];

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

export default function MeuElenco() {
    const liga = getLigaFromWindow();
    const clube = getClubeFromWindow();
    const meuElenco = getMeuElencoFromWindow();
    const [players, setPlayers] = useState(Array.isArray(meuElenco.players) ? meuElenco.players : []);

    // Busca + filtros
    const [q, setQ] = useState('');
    const [filtersOpen, setFiltersOpen] = useState(false);
    const [positionFilter, setPositionFilter] = useState('all');
    const [overallFilter, setOverallFilter] = useState('all');
    const [salaryFilter, setSalaryFilter] = useState('all');
    const [statusFilter, setStatusFilter] = useState('all');

    // Modal de ações
    const [modalPlayer, setModalPlayer] = useState(null);
    const [modalMessage, setModalMessage] = useState('');
    const [modalError, setModalError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [valueModalPlayer, setValueModalPlayer] = useState(null);
    const [valueModalValue, setValueModalValue] = useState('');
    const [valueModalWage, setValueModalWage] = useState('');
    const [valueModalPercent, setValueModalPercent] = useState(PERCENT_OPTIONS[0]);
    const [valueModalError, setValueModalError] = useState('');
    const [isValueSaving, setIsValueSaving] = useState(false);

    // Modal de detalhes do jogador
    const [detailPlayer, setDetailPlayer] = useState(null);
    const [detailExpanded, setDetailExpanded] = useState(false);
    const [detailLoading, setDetailLoading] = useState(false);
    const [detailError, setDetailError] = useState('');
    const [detailCache, setDetailCache] = useState({});

    // Fechar drawer com ESC
    useEffect(() => {
        if (!filtersOpen) return;
        const onKeyDown = (e) => {
            if (e.key === 'Escape') setFiltersOpen(false);
        };
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [filtersOpen]);

    useEffect(() => {
        if (!detailPlayer) return;
        const onKeyDown = (e) => {
            if (e.key === 'Escape') {
                setDetailPlayer(null);
                setDetailExpanded(false);
                setDetailError('');
                setDetailLoading(false);
            }
        };
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [detailPlayer]);

    const positionOptions = useMemo(() => {
        const values = new Set();
        players.forEach((player) => {
            const raw = player?.elencopadrao?.player_positions;
            if (!raw) return;
            raw.split(',').forEach((segment) => {
                const trimmed = segment.trim();
                if (trimmed) values.add(trimmed);
            });
        });
        return ['all', ...Array.from(values)];
    }, [players]);

    const filteredPlayers = useMemo(() => {
        const query = q.trim().toLowerCase();

        return players.filter((entry) => {
            const positionRaw = entry?.elencopadrao?.player_positions ?? '';
            const positions = positionRaw.split(',').map((item) => item.trim().toUpperCase());
            const overall = entry?.elencopadrao?.overall ?? 0;
            const wage = entry?.wage_eur ?? 0;
            const status = entry?.ativo ? 'ativo' : 'inativo';
            const name = (entry?.elencopadrao?.short_name || entry?.elencopadrao?.long_name || '')
                .toString()
                .toLowerCase();

            if (query && !name.includes(query)) return false;
            if (positionFilter !== 'all' && !positions.includes(positionFilter.toUpperCase())) return false;
            if (statusFilter !== 'all' && status !== statusFilter) return false;

            if (overallFilter === 'high' && overall < 90) return false;
            if (overallFilter === 'mid' && (overall < 85 || overall > 89)) return false;
            if (overallFilter === 'low' && overall > 84) return false;

            if (salaryFilter === 'low' && wage > 5000) return false;
            if (salaryFilter === 'mid' && (wage < 5000 || wage > 15000)) return false;
            if (salaryFilter === 'high' && wage <= 15000) return false;

            return true;
        });
    }, [players, q, positionFilter, overallFilter, salaryFilter, statusFilter]);

    const salaryPerRound = useMemo(
        () => players.reduce((total, entry) => total + (entry?.wage_eur ?? 0), 0),
        [players],
    );

    const baseSaleValue = (player) =>
        Number(
            player?.snapshot_value_eur ??
                player?.elencopadrao?.value_eur ??
                0,
        );

    const taxSaleValue = (player) => {
        const base = baseSaleValue(player);
        return Math.round(base * (TAX_PERCENT / 100));
    };

    const netSaleValue = (player) => {
        const base = baseSaleValue(player);
        return Math.max(0, base - taxSaleValue(player));
    };

    const openModal = (player) => {
        setModalPlayer(player);
        setModalMessage('');
        setModalError('');
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setModalPlayer(null);
        setModalError('');
        setModalMessage('');
        setIsSubmitting(false);
    };

    const handleModalSubmit = async () => {
        if (!modalPlayer) return;

        setIsSubmitting(true);
        setModalError('');

        try {
            const route = `/elenco/${modalPlayer.id}/vender-mercado`;

            const { data } = await window.axios.post(route, {});
            setPlayers((prev) => prev.filter((entry) => entry.id !== modalPlayer.id));
            const credit = data?.credit ?? netSaleValue(modalPlayer);
            setModalMessage(
                data?.message ?? `Jogador devolvido ao mercado. Crédito de ${formatCurrency(credit)} aplicado.`,
            );
        } catch (error) {
            setModalError(
                error.response?.data?.message ?? 'Não foi possível completar a operação. Tente novamente.',
            );
        } finally {
            setIsSubmitting(false);
        }
    };

    const clearFilters = () => {
        setPositionFilter('all');
        setOverallFilter('all');
        setSalaryFilter('all');
        setStatusFilter('all');
        setQ('');
    };

    const openValueModal = (entry) => {
        setValueModalPlayer(entry);
        setValueModalValue(entry?.value_eur ?? 0);
        setValueModalWage(entry?.wage_eur ?? 0);
        setValueModalPercent(PERCENT_OPTIONS[0]);
        setValueModalError('');
    };

    const closeValueModal = () => {
        setValueModalPlayer(null);
        setValueModalValue('');
        setValueModalWage('');
        setValueModalPercent(PERCENT_OPTIONS[0]);
        setValueModalError('');
        setIsValueSaving(false);
    };

    const computedValue = useMemo(() => {
        const base = Number(valueModalValue) || 0;
        return Math.round(base * (1 + (valueModalPercent ?? 0) / 100));
    }, [valueModalValue, valueModalPercent]);

    const computedWage = useMemo(() => {
        const base = Number(valueModalWage) || 0;
        return Math.round(base * (1 + (valueModalPercent ?? 0) / 100));
    }, [valueModalWage, valueModalPercent]);

    const handleValueSubmit = async () => {
        if (!valueModalPlayer) return;
        if (!Number.isFinite(computedValue) || computedValue < 0 || !Number.isFinite(computedWage) || computedWage < 0) {
            setValueModalError('Valor inválido. Tente outra porcentagem.');
            return;
        }

        setIsValueSaving(true);
        setValueModalError('');

        try {
            const { data } = await window.axios.patch(`/elenco/${valueModalPlayer.id}/valor`, {
                value_eur: computedValue,
                wage_eur: computedWage,
            });

            setPlayers((prev) =>
                prev.map((entry) =>
                    entry.id === valueModalPlayer.id
                        ? {
                              ...entry,
                              value_eur: data?.value_eur ?? computedValue,
                              wage_eur: data?.wage_eur ?? computedWage,
                          }
                        : entry,
                ),
            );

            closeValueModal();
        } catch (error) {
            setValueModalError(
                error.response?.data?.message ?? 'Não foi possível atualizar o valor. Tente novamente.',
            );
        } finally {
            setIsValueSaving(false);
        }
    };

    const detailPlayerId = detailPlayer?.elencopadrao?.id;
    const detailData = detailPlayerId ? detailCache[detailPlayerId] : null;
    const detailSnapshot = detailPlayer
        ? {
              ...(detailPlayer.elencopadrao ?? {}),
              ...(detailData ?? {}),
              value_eur:
                  detailPlayer.value_eur ??
                  detailData?.value_eur ??
                  detailPlayer.elencopadrao?.value_eur,
              wage_eur:
                  detailPlayer.wage_eur ??
                  detailData?.wage_eur ??
                  detailPlayer.elencopadrao?.wage_eur,
          }
        : null;

    const openDetailModal = (entry) => {
        setDetailPlayer(entry);
        setDetailExpanded(false);
        setDetailError('');
        if (entry?.elencopadrao?.id) {
            void loadDetailData(entry.elencopadrao.id, { expand: false });
        }
    };

    const closeDetailModal = () => {
        setDetailPlayer(null);
        setDetailExpanded(false);
        setDetailError('');
        setDetailLoading(false);
    };

    const loadDetailData = async (playerId, { expand } = { expand: true }) => {
        if (!playerId) return;

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

        await loadDetailData(detailPlayer.elencopadrao?.id, { expand: true });
    };

    const detailStatusLabel = detailPlayer
        ? `Meu clube · ${detailPlayer?.ativo ? 'Ativo' : 'Inativo'}`
        : '';

    if (!liga) {
        return (
            <main className="meu-elenco-screen" aria-label="Meu elenco">
                <p className="ligas-empty">Liga indisponível. Volte para o painel e tente novamente.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    const title = `${clube?.nome ? `ELENCO • ${clube.nome}` : 'MEU ELENCO'}`;
    const esquemaHref = liga?.id ? `/minha_liga/esquema-tatico?liga_id=${liga.id}` : '#';
    const esquemaPreviewUrl = clube?.esquema_tatico_imagem_url ?? null;

    return (
        <main className="meu-elenco-screen">
            <section className="meu-elenco-hero">
                <p className="meu-elenco-eyebrow">MEU</p>
                <h1 className="meu-elenco-title">{title}</h1>
                <div className="meu-elenco-stats">
                    <div>
                        <span>Jogadores</span>
                        <strong>
                            {players.length} / {meuElenco.max_players}
                        </strong>
                    </div>
                    <div>
                        <span>Custo por rodada</span>
                        <strong>{formatCurrency(salaryPerRound)}</strong>
                    </div>
                </div>
                <div className="meu-elenco-actions">
                    <a className="btn-primary" href={esquemaHref}>
                        Esquema tático
                    </a>
                </div>
                {esquemaPreviewUrl && (
                    <div className="meu-elenco-esquema-preview">
                        <img
                            src={esquemaPreviewUrl}
                            alt="Esquema tático salvo"
                            loading="lazy"
                            decoding="async"
                        />
                    </div>
                )}
            </section>

            {/* Barra: busca + filtros */}
            <section className="mercado-filters" aria-label="Busca e filtros do elenco">
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
                        placeholder="Buscar jogador..."
                        aria-label="Buscar jogador"
                    />
                </div>

                <button type="button" className="btn-outline mercado-filters-button" onClick={() => setFiltersOpen(true)}>
                    Filtros
                </button>
            </section>

            {/* Drawer de filtros */}
            {filtersOpen && (
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-label="Filtros do elenco"
                    className="mercado-drawer-backdrop"
                    onMouseDown={(e) => {
                        if (e.target === e.currentTarget) setFiltersOpen(false);
                    }}
                >
                    <div className="mercado-drawer">
                        <div className="mercado-drawer-header">
                            <div>
                                <p className="mercado-drawer-eyebrow">Filtros do elenco</p>
                                <strong>Meu elenco</strong>
                            </div>
                            <button type="button" className="btn-outline" onClick={() => setFiltersOpen(false)}>
                                Fechar
                            </button>
                        </div>

                        <div className="mercado-drawer-body">
                            <div className="filter-pill-row">
                                {positionOptions.map((option) => (
                                    <button
                                        key={option}
                                        type="button"
                                        className={`filter-pill${positionFilter === option ? ' active' : ''}`}
                                        onClick={() => setPositionFilter(option)}
                                    >
                                        {option === 'all' ? 'Todas posições' : option}
                                    </button>
                                ))}
                            </div>

                            <div className="mercado-drawer-grid">
                                <select value={overallFilter} onChange={(e) => setOverallFilter(e.target.value)}>
                                    {OVERALL_FILTERS.map((filter) => (
                                        <option key={filter.id} value={filter.id}>
                                            {filter.label}
                                        </option>
                                    ))}
                                </select>

                                <select value={salaryFilter} onChange={(e) => setSalaryFilter(e.target.value)}>
                                    {SALARY_FILTERS.map((filter) => (
                                        <option key={filter.id} value={filter.id}>
                                            {filter.label}
                                        </option>
                                    ))}
                                </select>

                                <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}>
                                    {STATUS_FILTERS.map((filter) => (
                                        <option key={filter.id} value={filter.id}>
                                            {filter.label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="mercado-drawer-actions">
                                <button type="button" className="btn-outline" onClick={clearFilters}>
                                    Limpar filtros
                                </button>
                                <button type="button" className="btn-primary" onClick={() => setFiltersOpen(false)}>
                                    Ver resultados ({filteredPlayers.length})
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Lista mobile no estilo Mercado */}
            <section className="mercado-table-wrap" aria-label="Tabela do elenco" style={{ marginTop: 20 }}>
                <div className="mercado-list-header">
                    <span>Jogador / OVR</span>
                    <span>Valores / Ação</span>
                </div>
                <div className="mercado-player-list">
                    {filteredPlayers.length === 0 ? (
                        <p className="mercado-no-results">
                            Nenhum jogador encontrado. Ajuste os filtros ou vá ao mercado.
                        </p>
                    ) : (
                        filteredPlayers.map((entry) => {
                            const elencopadrao = entry?.elencopadrao ?? {};
                            const name = elencopadrao.short_name || elencopadrao.long_name || '—';
                            const positions =
                                elencopadrao.player_positions?.split(',').map((pos) => pos.trim()) ?? [];
                            const pos = positions[0] || '—';
                            const ovr = elencopadrao.overall ?? '—';
                            const imageUrl = proxyFaceUrl(elencopadrao.player_face_url);
                            const ovrTone = resolveOvrTone(ovr);
                            const statusLabel = entry?.ativo ? 'Ativo' : 'Inativo';

                            return (
                                <article
                                    key={entry.id}
                                    className={`mercado-player-card status-${entry?.ativo ? 'ativo' : 'inativo'}`}
                                >
                                    <div className="mercado-player-card-content">
                                        <span className={`mercado-ovr-badge ovr-${ovrTone}`}>
                                            {ovr}
                                        </span>
                                        <button
                                            type="button"
                                            className="mercado-player-avatar-button"
                                            onClick={() => openDetailModal(entry)}
                                            aria-label={`Ver ficha completa de ${name}`}
                                        >
                                            <span className="mercado-player-avatar">
                                                <PlayerAvatar
                                                    src={imageUrl}
                                                    alt={name}
                                                    fallback={getInitials(name)}
                                                />
                                                <span className="mercado-player-position">{pos}</span>
                                            </span>
                                        </button>
                                        <div className="mercado-player-info">
                                            <strong>{name}</strong>
                                            <span style={{ color: entry?.ativo ? '#00ff88' : '#ff6b6b' }}>
                                                {statusLabel}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="mercado-player-card-right">
                                        <div className="mercado-player-values">
                                            <button
                                                type="button"
                                                className="mercado-player-value-button"
                                                onClick={() => openValueModal(entry)}
                                                aria-label={`Editar valor de ${name}`}
                                            >
                                                {formatCurrency(entry.value_eur)}
                                            </button>
                                            <span className="mercado-player-salary">
                                                SAL: {formatCurrency(entry.wage_eur)}
                                            </span>
                                        </div>
                                        <div className="mercado-player-action">
                                            <button
                                                type="button"
                                                className="table-action-badge outline"
                                                onClick={() => openModal(entry)}
                                            >
                                                Vender
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            );
                        })
                    )}
                </div>
            </section>

            <section className="meu-elenco-footer">
                <p>⚠️ Ao vender/comprar jogador, ajuste seu time usando o console financeiro.</p>
            </section>

            {isModalOpen && modalPlayer && (
                <div className="meu-elenco-modal-overlay" role="alertdialog" aria-modal="true">
                    <div className="meu-elenco-modal">
                        <h3>Devolver ao mercado</h3>
                        <p className="meu-elenco-modal-description">
                            O crédito é calculado a partir do valor original do jogador menos a taxa de {TAX_PERCENT}%.
                        </p>
                        <div className="modal-field">
                            <span>Valor base</span>
                            <p style={{ fontWeight: 600 }}>{formatCurrency(baseSaleValue(modalPlayer))}</p>
                        </div>
                        <div className="modal-field">
                            <span>Imposto ({TAX_PERCENT}%)</span>
                            <p style={{ fontWeight: 600 }}>
                                {formatCurrency(taxSaleValue(modalPlayer))}
                            </p>
                        </div>
                        <div className="modal-field">
                            <span>Valor líquido a receber</span>
                            <p style={{ fontWeight: 600 }}>{formatCurrency(netSaleValue(modalPlayer))}</p>
                        </div>
                        {modalError && <p className="modal-error">{modalError}</p>}
                        {modalMessage && <p className="modal-success">{modalMessage}</p>}
                        <div className="meu-elenco-modal-actions">
                            <button type="button" className="btn-outline" onClick={closeModal} disabled={isSubmitting}>
                                Cancelar
                            </button>
                            <button
                                type="button"
                                className="btn-primary"
                                onClick={handleModalSubmit}
                                disabled={isSubmitting}
                            >
                                {isSubmitting ? 'Enviando...' : 'Confirmar'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {valueModalPlayer && (
                <div
                    className="meu-elenco-modal-overlay"
                    role="dialog"
                    aria-modal="true"
                    style={{
                        position: 'fixed',
                        top: 0,
                        left: 0,
                        width: '100%',
                        height: '100%',
                        backgroundColor: 'rgba(0,0,0,0.85)',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        zIndex: 1000,
                        backdropFilter: 'blur(4px)',
                        padding: '20px',
                    }}
                >
                    <div
                        className="meu-elenco-modal"
                        style={{
                            background: '#1a1a1a',
                            width: '100%',
                            maxWidth: '420px',
                            borderRadius: '14px',
                            border: '1px solid #333',
                            overflow: 'hidden',
                            fontFamily: 'sans-serif',
                            color: '#fff',
                            boxShadow: '0 20px 40px rgba(0,0,0,0.6)',
                        }}
                    >
                        <div
                            style={{
                                background: 'linear-gradient(90deg, #edff05 0%, #1a1a1a 100%)',
                                height: '4px',
                            }}
                        />
                        <div style={{ padding: '24px' }}>
                            <h3
                                style={{
                                    textTransform: 'uppercase',
                                    fontWeight: 900,
                                    margin: '0 0 10px 0',
                                    fontSize: '18px',
                                    letterSpacing: '1px',
                                }}
                            >
                                Ajuste de Valor de Mercado
                            </h3>
                            <div
                                style={{
                                    backgroundColor: 'rgba(237, 255, 5, 0.1)',
                                    borderLeft: '4px solid #edff05',
                                    padding: '12px',
                                    marginBottom: '20px',
                                    borderRadius: '6px',
                                }}
                            >
                                <p
                                    style={{
                                        margin: 0,
                                        color: '#edff05',
                                        fontSize: '12px',
                                        fontWeight: 800,
                                        textTransform: 'uppercase',
                                        letterSpacing: '0.5px',
                                    }}
                                >
                                    ⚠️ Atenção Irreversível
                                </p>
                                <p
                                    style={{
                                        margin: '5px 0 0 0',
                                        color: '#eee',
                                        fontSize: '13px',
                                        lineHeight: 1.4,
                                    }}
                                >
                                    Ao aumentar o valor, você não poderá revertê-lo futuramente. O novo salário entra em vigor imediatamente.
                                </p>
                            </div>
                            <div className="modal-field" style={{ marginBottom: '20px' }}>
                                <span
                                    style={{
                                        fontSize: '11px',
                                        textTransform: 'uppercase',
                                        color: '#888',
                                        fontWeight: 700,
                                        display: 'block',
                                        marginBottom: '10px',
                                    }}
                                >
                                    Selecione o reajuste
                                </span>
                                <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                                    {PERCENT_OPTIONS.map((pct) => (
                                        <button
                                            key={pct}
                                            type="button"
                                            onClick={() => setValueModalPercent(pct)}
                                            style={{
                                                flex: '1 0 30%',
                                                padding: '12px 5px',
                                                borderRadius: '6px',
                                                border: valueModalPercent === pct ? '2px solid #edff05' : '1px solid #444',
                                                background: valueModalPercent === pct ? '#edff05' : 'transparent',
                                                color: valueModalPercent === pct ? '#000' : '#fff',
                                                fontWeight: 900,
                                                fontSize: '14px',
                                                cursor: 'pointer',
                                                transition: 'all 0.2s',
                                            }}
                                        >
                                            +{pct}%
                                        </button>
                                    ))}
                                </div>
                            </div>
                            <div
                                style={{
                                    display: 'grid',
                                    gridTemplateColumns: '1fr 1fr',
                                    gap: '15px',
                                    background: '#222',
                                    padding: '15px',
                                    borderRadius: '8px',
                                    marginBottom: '20px',
                                }}
                            >
                                <div className="modal-field">
                                    <span
                                        style={{
                                            fontSize: '10px',
                                            color: '#888',
                                            textTransform: 'uppercase',
                                            display: 'block',
                                        }}
                                    >
                                        Valor da Multa
                                    </span>
                                    <p
                                        style={{
                                            fontWeight: 800,
                                            fontSize: '15px',
                                            margin: '5px 0 0 0',
                                            color: '#fff',
                                        }}
                                    >
                                        {formatCurrency(computedValue)}
                                    </p>
                                </div>
                                <div className="modal-field">
                                    <span
                                        style={{
                                            fontSize: '10px',
                                            color: '#888',
                                            textTransform: 'uppercase',
                                            display: 'block',
                                        }}
                                    >
                                        Novo salário
                                    </span>
                                    <p
                                        style={{
                                            fontWeight: 800,
                                            fontSize: '15px',
                                            margin: '5px 0 0 0',
                                            color: '#edff05',
                                        }}
                                    >
                                        {formatCurrency(computedWage)}
                                    </p>
                                </div>
                            </div>
                            {valueModalError && (
                                <p
                                    className="modal-error"
                                    style={{
                                        color: '#ff4d4d',
                                        fontSize: '12px',
                                        textAlign: 'center',
                                        marginBottom: '10px',
                                        fontWeight: 600,
                                    }}
                                >
                                    {valueModalError}
                                </p>
                            )}
                            <div
                                className="meu-elenco-modal-actions"
                                style={{
                                    display: 'flex',
                                    gap: '10px',
                                }}
                            >
                                <button
                                    type="button"
                                    onClick={closeValueModal}
                                    disabled={isValueSaving}
                                    style={{
                                        flex: 1,
                                        padding: '14px',
                                        borderRadius: '6px',
                                        border: '1px solid #444',
                                        background: 'transparent',
                                        color: '#888',
                                        fontWeight: 700,
                                        textTransform: 'uppercase',
                                        fontSize: '12px',
                                        cursor: 'pointer',
                                    }}
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="button"
                                    onClick={handleValueSubmit}
                                    disabled={isValueSaving}
                                    style={{
                                        flex: 2,
                                        padding: '14px',
                                        borderRadius: '6px',
                                        border: 'none',
                                        background: '#edff05',
                                        color: '#000',
                                        fontWeight: 900,
                                        textTransform: 'uppercase',
                                        fontSize: '12px',
                                        cursor: 'pointer',
                                        boxShadow: '0 4px 0 #b3c200',
                                    }}
                                >
                                    {isValueSaving ? 'Salvando...' : 'Confirmar Reajuste'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {detailPlayer && (
                <PlayerDetailModal
                    player={detailSnapshot}
                    snapshot={detailSnapshot}
                    fullData={detailData ? { ...detailData, ...detailSnapshot } : null}
                    expanded={detailExpanded}
                    loading={detailLoading}
                    error={detailError}
                    statusLabel={detailStatusLabel}
                    onClose={closeDetailModal}
                    onToggleDetails={handleToggleDetails}
                />
            )}

            <Navbar active="ligas" />
        </main>
    );
}
