import { useEffect, useMemo, useState } from 'react';
import Navbar from '../components/app_publico/Navbar';
import backgroundDefault from '../../../storage/app/public/app/background/fundopadrao.jpgp';
import backgroundVertical from '../../../storage/app/public/app/background/fundopadrao.jpgp';

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

export default function MeuElenco() {
    const liga = getLigaFromWindow();
    const clube = getClubeFromWindow();
    const meuElenco = getMeuElencoFromWindow();
    const players = Array.isArray(meuElenco.players) ? meuElenco.players : [];

    // Busca + filtros
    const [q, setQ] = useState('');
    const [filtersOpen, setFiltersOpen] = useState(false);
    const [positionFilter, setPositionFilter] = useState('all');
    const [overallFilter, setOverallFilter] = useState('all');
    const [salaryFilter, setSalaryFilter] = useState('all');
    const [statusFilter, setStatusFilter] = useState('all');

    // Modal de ações
    const [modalPlayer, setModalPlayer] = useState(null);
    const [modalMode, setModalMode] = useState('vender');
    const [modalPrice, setModalPrice] = useState('');
    const [modalMessage, setModalMessage] = useState('');
    const [modalError, setModalError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isModalOpen, setIsModalOpen] = useState(false);

    const backgroundStyles = {
        '--mco-cover': `url(${backgroundDefault})`,
        '--mco-cover-mobile': `url(${backgroundVertical})`,
    };

    // Fechar drawer com ESC
    useEffect(() => {
        if (!filtersOpen) return;
        const onKeyDown = (e) => {
            if (e.key === 'Escape') setFiltersOpen(false);
        };
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [filtersOpen]);

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

    const openModal = (player, mode) => {
        setModalPlayer(player);
        setModalMode(mode);
        setModalPrice(mode === 'listar' ? (player?.value_eur ?? '') : '');
        setModalMessage('');
        setModalError('');
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setModalPlayer(null);
        setModalPrice('');
        setModalError('');
        setModalMessage('');
        setIsSubmitting(false);
    };

    const handleModalSubmit = async () => {
        if (!modalPlayer) return;
        if (modalMode === 'listar' && !modalPrice) {
            setModalError('Informe um valor válido.');
            return;
        }

        setIsSubmitting(true);
        setModalError('');

        try {
            const route =
                modalMode === 'listar'
                    ? `/elenco/${modalPlayer.id}/listar-mercado`
                    : `/elenco/${modalPlayer.id}/vender-mercado`;

            const payload = modalMode === 'listar' ? { preco: Number(modalPrice) } : {};

            const { data } = await window.axios.post(route, payload);
            setModalMessage(data?.message ?? 'Operação realizada com sucesso.');
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

    if (!liga) {
        return (
            <main className="meu-elenco-screen" style={backgroundStyles} aria-label="Meu elenco">
                <p className="ligas-empty">Liga indisponível. Volte para o painel e tente novamente.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    const title = `${clube?.nome ? `ELENCO • ${clube.nome}` : 'MEU ELENCO'}`;

    const resolveStatusBadge = (entry) => (
        <span
            className="mercado-pos-badge"
            style={{
                borderColor: entry?.ativo ? 'rgba(46,204,113,0.6)' : 'rgba(255,59,48,0.6)',
                color: entry?.ativo ? '#2ecc71' : '#ff6b6b',
            }}
        >
            {entry?.ativo ? 'Ativo' : 'Inativo'}
        </span>
    );

    return (
        <main className="meu-elenco-screen" style={backgroundStyles}>
            <section className="meu-elenco-hero">
                <p className="meu-elenco-eyebrow">MEU</p>
                <h1 className="meu-elenco-title">{title}</h1>
                <div className="meu-elenco-stats">
                    <div>
                        <span>Jogadores</span>
                        <strong>
                            {meuElenco.player_count} / {meuElenco.max_players}
                        </strong>
                    </div>
                    <div>
                        <span>Custo por rodada</span>
                        <strong>{formatCurrency(meuElenco.salary_per_round)}</strong>
                    </div>
                </div>
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

            {/* Tabela única */}
            <section className="mercado-table-wrap" aria-label="Tabela do elenco">
                <div className="mercado-table-scroll">
                    <table className="mercado-table">
                        <thead>
                            <tr>
                                <th>Jogador</th>
                                <th className="col-compact">OVR</th>
                                <th className="col-compact">POS</th>
                                <th className="numeric">Valor</th>
                                <th className="numeric">Salário</th>
                                <th>Status</th>
                                <th className="col-action">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredPlayers.length === 0 ? (
                                <tr>
                                    <td colSpan={7} style={{ padding: 16, opacity: 0.85 }}>
                                        Nenhum jogador encontrado. Ajuste os filtros ou vá ao mercado.
                                    </td>
                                </tr>
                            ) : (
                                filteredPlayers.map((entry) => {
                                    const elencopadrao = entry?.elencopadrao ?? {};
                                    const name = elencopadrao.short_name || elencopadrao.long_name || '—';
                                    const positions =
                                        elencopadrao.player_positions?.split(',').map((pos) => pos.trim()) ?? [];
                                    const pos = positions[0] || '—';
                                    const ovr = elencopadrao.overall ?? '—';
                                    const imageUrl = proxyFaceUrl(elencopadrao.player_face_url);
                                    const statusBadge = resolveStatusBadge(entry);
                                    const ovrTone = resolveOvrTone(ovr);

                                    return (
                                        <tr key={entry.id}>
                                            <td>
                                                <div className="mercado-player-cell">
                                                    <div className="mercado-avatar-sm">
                                                        {imageUrl ? (
                                                            <img src={imageUrl} alt={name} loading="lazy" decoding="async" />
                                                        ) : (
                                                            <span className="mercado-avatar-fallback">
                                                                {getInitials(name)}
                                                            </span>
                                                        )}
                                                    </div>
                                                    <div className="mercado-player-meta">
                                                        <strong>{name}</strong>
                                                        <span>{positions.filter(Boolean).join(' · ') || 'Sem posição'}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span className={`mercado-ovr-badge ovr-${ovrTone}`}>{ovr}</span>
                                            </td>
                                            <td>
                                                <span className="mercado-pos-badge">{pos}</span>
                                            </td>
                                            <td className="numeric">{formatCurrency(entry.value_eur)}</td>
                                            <td className="numeric">{formatCurrency(entry.wage_eur)}</td>
                                            <td>{statusBadge}</td>
                                            <td>
                                                <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                                                    <button
                                                        type="button"
                                                        className="btn-outline small"
                                                        onClick={() => openModal(entry, 'vender')}
                                                    >
                                                        Vender
                                                    </button>
                                                    <button
                                                        type="button"
                                                        className="btn-primary small"
                                                        onClick={() => openModal(entry, 'listar')}
                                                    >
                                                        Colocar no mercado
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>
            </section>

            <section className="meu-elenco-footer">
                <p>⚠️ Ao vender/comprar jogador, ajuste seu time usando o console financeiro.</p>
            </section>

            {isModalOpen && modalPlayer && (
                <div className="meu-elenco-modal-overlay" role="alertdialog" aria-modal="true">
                    <div className="meu-elenco-modal">
                        <h3>{modalMode === 'listar' ? 'Listar no mercado' : 'Devolver ao mercado'}</h3>
                        <p className="meu-elenco-modal-description">
                            {modalMode === 'listar'
                                ? 'Defina o preço pedido; a liga cobra 10%.'
                                : 'Recebe 80% do valor pago e o jogador volta ao mercado.'}
                        </p>
                        {modalMode === 'listar' && (
                            <label className="modal-field">
                                <span>Preço pedido</span>
                                <input
                                    type="number"
                                    min="0"
                                    value={modalPrice}
                                    onChange={(event) => setModalPrice(event.target.value)}
                                    placeholder="Ex.: 120000"
                                />
                            </label>
                        )}
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

            <Navbar active="ligas" />
        </main>
    );
}
