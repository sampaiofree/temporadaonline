import { useEffect, useMemo, useState } from 'react';
import Navbar from '../components/app_publico/Navbar';

const TYPE_LABELS = {
    publica: 'Liga pública · aberta para todos os jogadores',
    privada: 'Liga privada · acesso somente por convite',
};

const STATUS_LABELS = {
    ativa: 'Liga ativa · partidas acontecendo agora',
    encerrada: 'Liga encerrada · inscrições e jogos finalizados',
    aguardando: 'Liga aguardando · inscrições em breve',
};

const PLATFORM_BADGES = [
    { match: ['pc'], label: 'PC', className: 'platform-pc' },
    { match: ['ps5', 'playstation 5', 'ps'], label: 'PS5', className: 'platform-ps' },
    { match: ['xbox'], label: 'XBOX', className: 'platform-xbox' },
];

const getPlatformBadge = (plataforma) => {
    if (!plataforma) {
        return { label: '—', className: 'platform-default' };
    }
    const normalized = plataforma.toString().toLowerCase();
    const match = PLATFORM_BADGES.find((item) =>
        item.match.some((token) => normalized.includes(token)),
    );
    if (match) {
        return { label: match.label, className: match.className };
    }
    const short = plataforma.toString().toUpperCase().slice(0, 4);
    return { label: short, className: 'platform-default' };
};

const getStatusClass = (status) => {
    if (!status) return 'liga-status-default';
    return `liga-status-${status}`;
};

const getAllLigasFromWindow = () => {
    if (Array.isArray(window.__ALL_LIGAS__)) {
        return window.__ALL_LIGAS__;
    }
    return [];
};

const getMyLigasFromWindow = () => {
    if (Array.isArray(window.__MY_LIGAS__)) {
        return window.__MY_LIGAS__;
    }
    return [];
};

const shouldRequireProfileCompletion = Boolean(window.__REQUIRE_PROFILE_COMPLETION__ ?? false);
const PROFILE_URL = window.__PROFILE_URL__ || '/perfil';
const PROFILE_HORARIOS_URL = window.__PROFILE_HORARIOS_URL__ || `${PROFILE_URL}#horarios`;

export default function Ligas() {
    const [blocked] = useState(shouldRequireProfileCompletion);
    const [activeLiga, setActiveLiga] = useState(null);
    const [isJoining, setIsJoining] = useState(false);
    const [joinError, setJoinError] = useState('');
    const allLigas = getAllLigasFromWindow();
    const myLigas = getMyLigasFromWindow();
    const [filter, setFilter] = useState(() => (myLigas.length > 0 ? 'mine' : 'all'));
    const [filtersOpen, setFiltersOpen] = useState(false);
    const [selectedJogo, setSelectedJogo] = useState('');
    const [selectedPlataforma, setSelectedPlataforma] = useState('');
    const ligas = filter === 'mine' ? myLigas : allLigas;
    const jogos = useMemo(
        () =>
            Array.from(
                new Set(
                    allLigas
                        .map((liga) => liga.jogo)
                        .filter((value) => value && value.toString().trim() !== ''),
                ),
            ),
        [allLigas],
    );
    const plataformas = useMemo(
        () =>
            Array.from(
                new Set(
                    allLigas
                        .map((liga) => liga.plataforma)
                        .filter((value) => value && value.toString().trim() !== ''),
                ),
            ),
        [allLigas],
    );
    const displayedLigas = useMemo(() => {
        return ligas.filter((liga) => {
            if (selectedJogo && liga.jogo !== selectedJogo) {
                return false;
            }
            if (selectedPlataforma && liga.plataforma !== selectedPlataforma) {
                return false;
            }
            return true;
        });
    }, [ligas, selectedJogo, selectedPlataforma]);

    const openModal = (liga) => {
        setJoinError('');
        setActiveLiga(liga);
    };
    const closeModal = () => setActiveLiga(null);

    const handleCardKeyDown = (event, liga) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            handleLeagueSelect(liga);
        }
    };

    const handleLeagueSelect = (liga) => {
        if (liga.registered) {
            window.location.href = `/minha_liga?liga_id=${liga.id}`;
            return;
        }

        if (blocked) {
            return;
        }

        openModal(liga);
    };

    useEffect(() => {
        if (!activeLiga) {
            return undefined;
        }

        const handleKeyDown = (event) => {
            if (event.key === 'Escape') {
                closeModal();
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [activeLiga]);

    const modalStatusLabel = activeLiga ? STATUS_LABELS[activeLiga.status] ?? 'Status indefinido' : '';
    const emptyMessage =
        filter === 'mine'
            ? 'Você ainda não participa de nenhuma liga. Use “Todas as ligas” para encontrar novas competições.'
            : 'Nenhuma liga encontrada no momento. Volte mais tarde!';

    const handleJoin = async () => {
        if (!activeLiga || activeLiga.registered || isJoining) {
            return;
        }

        setIsJoining(true);
        setJoinError('');

        try {
            const { data } = await window.axios.post(`/ligas/${activeLiga.id}/entrar`);
            window.location.href = data.redirect;
        } catch (error) {
            const message =
                error.response?.data?.message ??
                'Não foi possível entrar na liga. Tente novamente.';
            setJoinError(message);
        } finally {
            setIsJoining(false);
        }
    };

    return (
        <main className="mco-screen" aria-label="Lista de ligas">
            <section className="ligas-hero" aria-label="Resumo">
                <p className="ligas-eyebrow">LIGAS</p>
                <h1 className="ligas-title">Escolha sua próxima competição</h1>
                <p className="ligas-subtitle">
                    Explore todas as ligas disponíveis, compare regras e plataformas e acesse detalhes completos
                    antes de entrar.
                </p>
            </section>
            <div className="ligas-filter-row">
                <button
                    type="button"
                    className={`filter-button${filter === 'mine' ? ' active' : ''} btn-primary`}
                    onClick={() => setFilter('mine')}
                >
                    Minhas ligas
                </button>
                <button
                    type="button"
                    className={`filter-button${filter === 'all' ? ' active' : ''} btn-primary`}
                    onClick={() => setFilter('all')}
                >
                    Todas as ligas
                </button>
                <button
                    type="button"
                    className="filter-button btn-primary"
                    onClick={() => setFiltersOpen(true)}
                >
                    Filtrar
                </button>
            </div>
            <section className="mercado-table-scroll ligas-table-wrap" aria-label="Tabela de ligas">
                {blocked ? (
                    <div className="ligas-blocked">
                        <p className="ligas-blocked-eyebrow">Acesso restrito</p>
                        <h3>Complete seu perfil e adicione um horário disponível</h3>
                        <p>
                            Você precisa cadastrar plataforma, jogo, nickname e geração e ter pelo menos um horário de
                            disponibilidade registrado para desbloquear as ligas.
                        </p>
                        <div className="ligas-blocked-actions">
                            <a className="ligas-modal-button ligas-modal-button--primary" href={PROFILE_URL}>
                                Completar perfil
                            </a>
                            <a className="ligas-modal-button ligas-modal-button--ghost" href={PROFILE_HORARIOS_URL}>
                                Registrar horário
                            </a>
                        </div>
                    </div>
                ) : displayedLigas.length === 0 ? (
                    <p className="ligas-empty">{emptyMessage}</p>
                ) : (
                    <>
                        <div className="ligas-mobile-list">
                            {displayedLigas.map((liga) => {
                                const platformBadge = getPlatformBadge(liga.plataforma);
                                const statusLabel = STATUS_LABELS[liga.status] ?? 'Status indefinido';
                                const statusClass = getStatusClass(liga.status);
                                const actionLabel = liga.registered ? 'Ir para minha liga' : 'Entrar';

                                return (
                                    <article key={liga.id} className="liga-mobile-card">
                                        <div className="liga-mobile-head">
                                            <div className="liga-mobile-logo">
                                                {liga.imagem ? (
                                                    <img src={`/storage/${liga.imagem}`} alt={`Escudo da ${liga.nome}`} />
                                                ) : (
                                                    <span>{liga.nome?.slice(0, 2).toUpperCase() || 'LG'}</span>
                                                )}
                                            </div>
                                            <div className="liga-mobile-title">
                                                <div className="liga-mobile-title-row">
                                                    <strong>{liga.nome}</strong>
                                                    <span className={`liga-platform-badge ${platformBadge.className}`}>
                                                        {platformBadge.label}
                                                    </span>
                                                </div>
                                                <div className={`liga-mobile-status ${statusClass}`}>
                                                    <span className="liga-mobile-status-dot" />
                                                    {statusLabel}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="liga-mobile-meta">
                                            <div className="liga-mobile-info">
                                                <span>Jogo · Geração</span>
                                                <strong>
                                                    {[liga.jogo || '—', liga.geracao || '—'].join(' · ')}
                                                </strong>
                                            </div>
                                            <button
                                                type="button"
                                                className="liga-mobile-action"
                                                onClick={() => {
                                                    if (liga.registered) {
                                                        window.location.href = `/minha_liga?liga_id=${liga.id}`;
                                                    } else {
                                                        openModal(liga);
                                                    }
                                                }}
                                            >
                                                {actionLabel}
                                            </button>
                                        </div>
                                    </article>
                                );
                            })}
                        </div>
                        <table className="mercado-table ligas-table">
                            <thead>
                                <tr>
                                    <th>Imagem</th>
                                    <th>Nome</th>
                                    <th>Jogo · Geração</th>
                                    <th>Plataforma</th>
                                    <th className="col-action">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                {displayedLigas.map((liga) => (
                                    <tr key={liga.id}>
                                        <td className="mercado-player-cell">
                                            <div className="mercado-avatar-sm">
                                                {liga.imagem ? (
                                                    <img src={`/storage/${liga.imagem}`} alt={`Escudo da ${liga.nome}`} />
                                                ) : (
                                                    <span>{liga.nome?.slice(0, 2).toUpperCase() || 'LG'}</span>
                                                )}
                                            </div>
                                        </td>
                                        <td>
                                            <div className="mercado-player-meta">
                                                <strong>{liga.nome}</strong>
                                                <span>{STATUS_LABELS[liga.status] ?? 'Status indefinido'}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div className="mercado-player-meta">
                                                <strong>{liga.jogo || '—'}</strong>
                                                <span>{liga.geracao || '—'}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span>{liga.plataforma || '—'}</span>
                                        </td>
                                        <td className="col-action">
                                            {liga.registered ? (
                                                <button
                                                    type="button"
                                                    className="table-action-badge primary "
                                                    onClick={() => (window.location.href = `/minha_liga?liga_id=${liga.id}`)}
                                                >
                                                    Ir para minha liga
                                                </button>
                                            ) : (
                                                <button
                                                    type="button"
                                                    className="table-action-badge primary "
                                                    onClick={() => openModal(liga)}
                                                >
                                                    Entrar
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </>
                )}
            </section>
            {filtersOpen && (
                <div
                    className="mercado-drawer-backdrop"
                    role="presentation"
                    onMouseDown={(event) => {
                        if (event.target === event.currentTarget) {
                            setFiltersOpen(false);
                        }
                    }}
                >
                    <div className="mercado-drawer" role="dialog" aria-modal="true" aria-label="Filtros de ligas">
                        <div className="mercado-drawer-header">
                            <div>
                                <p className="mercado-drawer-eyebrow">Filtrar ligas</p>
                                <strong>Defina jogo e plataforma</strong>
                            </div>
                            <button type="button" className="btn-outline" onClick={() => setFiltersOpen(false)}>
                                Fechar
                            </button>
                        </div>
                        <div className="mercado-drawer-body">
                            <div className="mercado-drawer-grid">
                                <label htmlFor="filter-jogo" className="mercado-drawer-label">
                                    Jogo
                                </label>
                                <select
                                    id="filter-jogo"
                                    value={selectedJogo}
                                    onChange={(event) => setSelectedJogo(event.target.value)}
                                >
                                    <option value="">Todos</option>
                                    {jogos.map((item) => (
                                        <option key={item} value={item}>
                                            {item}
                                        </option>
                                    ))}
                                </select>
                                <label htmlFor="filter-plataforma" className="mercado-drawer-label">
                                    Plataforma
                                </label>
                                <select
                                    id="filter-plataforma"
                                    value={selectedPlataforma}
                                    onChange={(event) => setSelectedPlataforma(event.target.value)}
                                >
                                    <option value="">Todas</option>
                                    {plataformas.map((item) => (
                                        <option key={item} value={item}>
                                            {item}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="mercado-drawer-actions">
                                <button
                                    type="button"
                                    className="btn-outline"
                                    onClick={() => {
                                        setSelectedJogo('');
                                        setSelectedPlataforma('');
                                    }}
                                >
                                    Limpar
                                </button>
                                <button type="button" className="btn-primary" onClick={() => setFiltersOpen(false)}>
                                    Aplicar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
            {blocked && (
                <div className="ligas-modal-overlay" role="presentation">
                    <div
                        className="ligas-modal ligas-modal--blocked"
                        role="dialog"
                        aria-modal="true"
                        aria-label="Completar perfil e registrar disponibilidade"
                    >
                        <div className="ligas-modal-blocked-icon">
                            <span>🔒</span>
                        </div>
                        <div className="ligas-modal-header">
                            <p className="ligas-modal-status">Acesso bloqueado</p>
                            <h2>Finalize seu perfil para desbloquear as ligas</h2>
                        </div>
                        <div className="ligas-modal-body">
                            <p>
                                Detectamos dados pendentes. Cadastre{' '}
                                <span>plataforma, jogo, nickname</span> e ao menos um{' '}
                                <span>horário disponível</span> para competir.
                            </p>
                        </div>
                        <div className="ligas-modal-actions">
                            <a
                                className="ligas-modal-button ligas-modal-button--primary"
                                href={PROFILE_HORARIOS_URL}
                            >
                                Registrar horário
                            </a>
                            <a
                                className="ligas-modal-button ligas-modal-button--ghost"
                                href={PROFILE_URL}
                            >
                                Completar perfil
                            </a>
                        </div>
                    </div>
                </div>
            )}
            {activeLiga && (
                <div className="ligas-modal-overlay" role="presentation" onClick={closeModal}>
                    <div
                        className="ligas-modal"
                        role="dialog"
                        aria-modal="true"
                        aria-label={`Detalhes da liga ${activeLiga.nome}`}
                        onClick={(event) => event.stopPropagation()}
                    >
                        <div className="ligas-modal-hero">
                            <div className="ligas-modal-image">
                                {activeLiga.imagem ? (
                                    <img src={`/storage/${activeLiga.imagem}`} alt={`Escudo da ${activeLiga.nome}`} />
                                ) : (
                                    <span>Sem imagem</span>
                                )}
                            </div>
                            <div className="ligas-modal-hero-text">
                                <p className="ligas-modal-status">{modalStatusLabel}</p>
                                <h2>{activeLiga.nome}</h2>
                            </div>
                        </div>
                        <div className="ligas-modal-body">
                            <div className="liga-modal-section">
                                <span className="liga-modal-label">Descrição geral</span>
                                <p className="liga-modal-value">
                                    {activeLiga.descricao || 'Essa liga ainda não descreveu a proposta.'}
                                </p>
                            </div>
                            <div className="liga-modal-section">
                                <span className="liga-modal-label">Regras resumidas</span>
                                <p className="liga-modal-value">
                                    {activeLiga.regras || 'As regras ainda serão definidas. Aguarde novidades.'}
                                </p>
                            </div>
                            <div className="liga-modal-grid">
                                <div>
                                    <span>Jogo</span>
                                    <strong>{activeLiga.jogo || '—'}</strong>
                                </div>
                                <div>
                                    <span>Geração</span>
                                    <strong>{activeLiga.geracao || '—'}</strong>
                                </div>
                                <div>
                                    <span>Plataforma</span>
                                    <strong>{activeLiga.plataforma || '—'}</strong>
                                </div>
                                <div>
                                    <span>Vagas</span>
                                    <strong>{activeLiga.max_times ?? '—'} times</strong>
                                </div>
                            </div>
                            <div className="liga-modal-period">
                                <span>Períodos de partidas</span>
                                {activeLiga.periodos && activeLiga.periodos.length > 0 ? (
                                    <div className="liga-modal-period-list">
                                        {activeLiga.periodos.map((periodo) => (
                                            <div key={periodo.codigo} className="liga-modal-period-item">
                                                <span>
                                                    {periodo.inicio_label || '—'} <strong>→</strong>{' '}
                                                    {periodo.fim_label || '—'}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <strong className="liga-modal-period-empty">Período não informado</strong>
                                )}
                            </div>
                        </div>
                        <div className="ligas-modal-actions">
                            <button
                                type="button"
                                className="ligas-modal-button ligas-modal-button--ghost"
                                onClick={closeModal}
                                disabled={isJoining}
                            >
                                Fechar
                            </button>
                            {!activeLiga.registered && activeLiga.status !== 'encerrada' && (
                                <button
                                    type="button"
                                    className="ligas-modal-button ligas-modal-button--primary"
                                    onClick={handleJoin}
                                    disabled={isJoining}
                                >
                                    {isJoining ? 'Entrando...' : 'Entrar'}
                                </button>
                            )}
                        </div>
                        {(activeLiga.registered || joinError) && (
                            <div className="ligas-modal-meta">
                                {activeLiga.registered && (
                                    <p className="ligas-modal-registered">Você já é membro desta liga.</p>
                                )}
                                {joinError && <p className="ligas-modal-error">{joinError}</p>}
                            </div>
                        )}
                    </div>
                </div>
            )}
            <Navbar active="ligas" />
        </main>
    );
}
