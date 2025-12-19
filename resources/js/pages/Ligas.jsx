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

export default function Ligas() {
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
            <section className="mercado-table-scroll" aria-label="Tabela de ligas">
                {displayedLigas.length === 0 ? (
                    <p className="ligas-empty">{emptyMessage}</p>
                ) : (
                    <table className="mercado-table">
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
            {activeLiga && (
                <div className="ligas-modal-overlay" role="presentation" onClick={closeModal}>
                    <div
                        className="ligas-modal"
                        role="dialog"
                        aria-modal="true"
                        aria-label={`Detalhes da liga ${activeLiga.nome}`}
                        onClick={(event) => event.stopPropagation()}
                    >
                        <div className="ligas-modal-image">
                            {activeLiga.imagem ? (
                                <img src={`/storage/${activeLiga.imagem}`} alt={`Escudo da ${activeLiga.nome}`} />
                            ) : (
                                <span>Sem imagem</span>
                            )}
                        </div>
                        <div className="ligas-modal-header">
                            <p className="ligas-modal-status">{modalStatusLabel}</p>
                            <h2>{activeLiga.nome}</h2>
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
                            <div className="liga-modal-section">
                                <span className="liga-modal-label">Tipo de liga</span>
                                <p className="liga-modal-value">{TYPE_LABELS[activeLiga.tipo]}</p>
                            </div>
                            <div className="liga-modal-section">
                                <span className="liga-modal-label">Status atual</span>
                                <p className="liga-modal-value">{modalStatusLabel}</p>
                            </div>
                            <div className="liga-modal-section">
                                <span className="liga-modal-label">Capacidade máxima</span>
                                <p className="liga-modal-value">
                                    {activeLiga.max_times}
                                    {' '}times podem participar desta liga.
                                </p>
                            </div>
                            <div className="liga-modal-section">
                                <span className="liga-modal-label">Jogo</span>
                                <p className="liga-modal-value">{activeLiga.jogo || 'Não informado'}</p>
                            </div>
                            <div className="liga-modal-section">
                                <span className="liga-modal-label">Geração</span>
                                <p className="liga-modal-value">{activeLiga.geracao || 'Não informada'}</p>
                            </div>
                            <div className="liga-modal-section">
                                <span className="liga-modal-label">Plataforma</span>
                                <p className="liga-modal-value">{activeLiga.plataforma || 'Não informada'}</p>
                            </div>
                        </div>
                        <div className="ligas-modal-actions">
                            <button type="button" className="ligas-modal-button ligas-modal-button--ghost" onClick={closeModal}>
                                Fechar
                            </button>
                            {activeLiga.status !== 'encerrada' && !activeLiga.registered && (
                                <button
                                    type="button"
                                    className="ligas-modal-button ligas-modal-button--primary"
                                    onClick={handleJoin}
                                    disabled={isJoining}
                                >
                                    {isJoining ? 'Entrando...' : 'Entrar na liga'}
                                </button>
                            )}
                        </div>
                        {activeLiga.registered && (
                            <p className="ligas-modal-registered">Você já é membro desta liga.</p>
                        )}
                        {joinError && <p className="ligas-modal-error">{joinError}</p>}
                    </div>
                </div>
            )}
            <Navbar active="ligas" />
        </main>
    );
}
