import { useMemo, useState } from 'react';
import Navbar from '../components/app_publico/Navbar';
import backgroundDefault from '../../../storage/app/public/app/background/fundopadrao.webp';
import backgroundVertical from '../../../storage/app/public/app/background/fundo_vertical.webp';

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
    { id: 'all', label: 'Todos' },
    { id: 'high', label: 'OVR ≥ 90' },
    { id: 'mid', label: 'OVR 85–89' },
    { id: 'low', label: 'OVR ≤ 84' },
];

const SALARY_FILTERS = [
    { id: 'all', label: 'Todos' },
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
    if (!name) {
        return '?';
    }

    const parts = name.split(/\s+/).filter(Boolean);
    if (parts.length === 0) {
        return name.charAt(0).toUpperCase();
    }

    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }

    return (parts[0][0] + parts[1][0]).toUpperCase();
};

export default function MeuElenco() {
    const liga = getLigaFromWindow();
    const clube = getClubeFromWindow();
    const meuElenco = getMeuElencoFromWindow();
    const players = Array.isArray(meuElenco.players) ? meuElenco.players : [];

    const [positionFilter, setPositionFilter] = useState('all');
    const [overallFilter, setOverallFilter] = useState('all');
    const [salaryFilter, setSalaryFilter] = useState('all');
    const [statusFilter, setStatusFilter] = useState('all');

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

    const positionOptions = useMemo(() => {
        const values = new Set();
        players.forEach((player) => {
            const raw = player?.elencopadrao?.player_positions;
            if (!raw) {
                return;
            }
            raw.split(',').forEach((segment) => {
                const trimmed = segment.trim();
                if (trimmed) {
                    values.add(trimmed);
                }
            });
        });
        return ['all', ...Array.from(values).slice(0, 6)];
    }, [players]);

    const filteredPlayers = useMemo(() => {
        return players.filter((entry) => {
            const positionRaw = entry?.elencopadrao?.player_positions ?? '';
            const positions = positionRaw.split(',').map((item) => item.trim().toUpperCase());
            const overall = entry?.elencopadrao?.overall ?? 0;
            const wage = entry?.wage_eur ?? 0;
            const status = entry?.ativo ? 'ativo' : 'inativo';

            if (positionFilter !== 'all' && !positions.includes(positionFilter.toUpperCase())) {
                return false;
            }

            if (statusFilter !== 'all' && status !== statusFilter) {
                return false;
            }

            if (overallFilter === 'high' && overall < 90) {
                return false;
            }
            if (overallFilter === 'mid' && (overall < 85 || overall > 89)) {
                return false;
            }
            if (overallFilter === 'low' && overall > 84) {
                return false;
            }

            if (salaryFilter === 'low' && wage > 5000) {
                return false;
            }
            if (salaryFilter === 'mid' && (wage < 5000 || wage > 15000)) {
                return false;
            }
            if (salaryFilter === 'high' && wage <= 15000) {
                return false;
            }

            return true;
        });
    }, [players, positionFilter, overallFilter, salaryFilter, statusFilter]);

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
        if (!modalPlayer) {
            return;
        }

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

    if (!liga) {
        return (
            <main className="meu-elenco-screen" style={backgroundStyles} aria-label="Meu elenco">
                <p className="ligas-empty">Liga indisponível. Volte para o painel e tente novamente.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    const title = `${clube?.nome ? `ELENCO • ${clube.nome}` : 'MEU ELENCO'}`;

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

            <section className="meu-elenco-filters">
                <div className="filter-group">
                    <span>Posição</span>
                    <div className="filter-pill-row">
                        {positionOptions.map((option) => (
                            <button
                                key={option}
                                type="button"
                                className={`filter-pill${positionFilter === option ? ' active' : ''}`}
                                onClick={() => setPositionFilter(option)}
                            >
                                {option === 'all' ? 'Todas' : option}
                            </button>
                        ))}
                    </div>
                </div>
                <div className="filter-group">
                    <span>Overall</span>
                    <div className="filter-pill-row">
                        {OVERALL_FILTERS.map((filter) => (
                            <button
                                type="button"
                                key={filter.id}
                                className={`filter-pill${overallFilter === filter.id ? ' active' : ''}`}
                                onClick={() => setOverallFilter(filter.id)}
                            >
                                {filter.label}
                            </button>
                        ))}
                    </div>
                </div>
                <div className="filter-group">
                    <span>Salário</span>
                    <div className="filter-pill-row">
                        {SALARY_FILTERS.map((filter) => (
                            <button
                                type="button"
                                key={filter.id}
                                className={`filter-pill${salaryFilter === filter.id ? ' active' : ''}`}
                                onClick={() => setSalaryFilter(filter.id)}
                            >
                                {filter.label}
                            </button>
                        ))}
                    </div>
                </div>
                <div className="filter-group">
                    <span>Status</span>
                    <div className="filter-pill-row">
                        {STATUS_FILTERS.map((filter) => (
                            <button
                                type="button"
                                key={filter.id}
                                className={`filter-pill${statusFilter === filter.id ? ' active' : ''}`}
                                onClick={() => setStatusFilter(filter.id)}
                            >
                                {filter.label}
                            </button>
                        ))}
                    </div>
                </div>
            </section>

            <section className="meu-elenco-list" aria-label="Lista do elenco">
                {filteredPlayers.length === 0 ? (
                    <p className="ligas-empty">
                        Nenhum jogador encontra-se neste momento. Ajuste os filtros ou traga o mercado.
                    </p>
                ) : (
                    filteredPlayers.map((entry) => {
                        const elencopadrao = entry?.elencopadrao ?? {};
                        const positions = elencopadrao.player_positions?.split(',').map((pos) => pos.trim()) ?? [];
                        const statusLabel = entry.ativo ? '🟢 Ativo' : '🔴 Inativo (salário não pago)';
                        const imageUrl = proxyFaceUrl(elencopadrao.player_face_url);

                        return (
                            <article key={entry.id} className="meu-elenco-card">
                                <div className="meu-elenco-card-image" aria-hidden="true">
                                    {imageUrl ? (
                                        <img src={imageUrl} alt={elencopadrao.short_name} loading="lazy" />
                                    ) : (
                                        <span>{getInitials(elencopadrao.short_name || elencopadrao.long_name)}</span>
                                    )}
                                </div>
                                <div className="meu-elenco-card-body">
                                    <div className="meu-elenco-card-line">
                                        <p className="meu-elenco-card-name">{elencopadrao.short_name || elencopadrao.long_name}</p>
                                        <span className="meu-elenco-card-meta">
                                            {positions.filter(Boolean).join(' · ')} · OVR {elencopadrao.overall ?? '—'} 
                                        </span>
                                    </div>
                                    <div className="meu-elenco-card-stats">
                                        <p>
                                            <strong>{formatCurrency(entry.wage_eur)}</strong>
                                            <span> Salário / rodada</span>
                                        </p>
                                        <p>
                                            <strong>{formatCurrency(entry.value_eur)}</strong>
                                            <span> Valor mercado</span>
                                        </p>
                                    </div>
                                    <p className="meu-elenco-status">{statusLabel}</p>
                                    <div className="meu-elenco-actions">
                                        <button type="button" className="btn-outline" onClick={() => openModal(entry, 'vender')}>
                                            Vender
                                        </button>
                                        <button type="button" className="btn-primary" onClick={() => openModal(entry, 'listar')}>
                                            Colocar no mercado
                                        </button>
                                    </div>
                                </div>
                            </article>
                        );
                    })
                )}
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
