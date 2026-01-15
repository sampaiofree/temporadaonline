import { useEffect, useMemo, useState } from 'react';
import Navbar from '../components/app_publico/Navbar';
import Alert from '../components/app_publico/Alert';

const getEditorData = () => window.__CLUBE_EDITOR__ ?? {};

const getModalFromUrl = () => {
    const modal = new URLSearchParams(window.location.search).get('modal');
    if (modal === 'nome' || modal === 'escudo') {
        return modal;
    }
    return null;
};

const updateModalInUrl = (modal, ligaId) => {
    const params = new URLSearchParams(window.location.search);
    if (modal) {
        params.set('modal', modal);
    } else {
        params.delete('modal');
    }
    if (!params.get('liga_id') && ligaId) {
        params.set('liga_id', String(ligaId));
    }
    const query = params.toString();
    const nextUrl = `${window.location.pathname}${query ? `?${query}` : ''}`;
    window.history.pushState(null, '', nextUrl);
};

const buildUrl = (ligaId, params, modal) => {
    const searchParams = new URLSearchParams();
    searchParams.set('liga_id', ligaId);
    Object.entries(params).forEach(([key, value]) => {
        if (value === undefined || value === null || value === '') {
            return;
        }
        searchParams.set(key, value);
    });
    if (modal) {
        searchParams.set('modal', modal);
    }

    return `/minha_liga/clube?${searchParams.toString()}`;
};

const getInitialFilters = (filters) => ({
    search: filters?.search ?? '',
    escudo_pais_id: filters?.escudo_pais_id ?? '',
    escudo_liga_id: filters?.escudo_liga_id ?? '',
    only_available: Boolean(filters?.only_available ?? false),
});

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0,
});

const formatCurrency = (value) => {
    if (value === null || value === undefined) return '—';
    return currencyFormatter.format(value);
};

const formatFans = (value) => {
    if (value === null || value === undefined) return '0';
    return Number(value).toLocaleString('pt-BR');
};

const currencySymbol = currencyFormatter.formatToParts(0).find((part) => part.type === 'currency')?.value ?? '';
const ABBREVIATION_LEVELS = [
    { value: 1_000_000_000_000, suffix: 'T' },
    { value: 1_000_000_000, suffix: 'B' },
    { value: 1_000_000, suffix: 'M' },
    { value: 1_000, suffix: 'K' },
];

const formatAbbreviatedCurrency = (value) => {
    if (value === null || value === undefined) {
        return '—';
    }

    const normalized = Number(value);
    if (!Number.isFinite(normalized) || normalized < 0) {
        return formatCurrency(normalized);
    }

    for (const level of ABBREVIATION_LEVELS) {
        if (normalized >= level.value) {
            const scaled = normalized / level.value;
            const rounded = scaled >= 10 ? Math.round(scaled) : Math.round(scaled * 10) / 10;
            const display = Number.isInteger(rounded) ? `${rounded}` : rounded.toFixed(1);
            return `${currencySymbol}${display}${level.suffix}`;
        }
    }

    return formatCurrency(normalized);
};

const getLeagueInitials = (name) => {
    if (!name) return 'FC';
    const parts = name.split(/\s+/).filter(Boolean);
    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }
    return `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase();
};

export default function MinhaLigaClube() {
    const data = getEditorData();
    const liga = data.liga;
    const clube = data.clube;
    const escudos = data.escudos ?? {};
    const paises = data.paises ?? [];
    const ligasEscudos = data.ligas_escudos ?? [];
    const usedEscudos = new Set((data.used_escudos ?? []).map((id) => Number(id)));
    const initialFilters = getInitialFilters(data.filters);

    const [clubSnapshot, setClubSnapshot] = useState(clube);
    const [clubName, setClubName] = useState(clube?.nome ?? '');
    const [selectedEscudoId, setSelectedEscudoId] = useState(
        clube?.escudo_id ? String(clube.escudo_id) : '',
    );
    const [filters, setFilters] = useState(initialFilters);
    const [filtersOpen, setFiltersOpen] = useState(false);
    const [errors, setErrors] = useState([]);
    const [saving, setSaving] = useState(false);
    const [feedback, setFeedback] = useState('');
    const [feedbackCta, setFeedbackCta] = useState('');
    const [activeModal, setActiveModal] = useState(getModalFromUrl());

    useEffect(() => {
        const onPopState = () => {
            setActiveModal(getModalFromUrl());
        };

        window.addEventListener('popstate', onPopState);
        return () => window.removeEventListener('popstate', onPopState);
    }, []);

    if (!liga) {
        return (
            <main className="mco-screen club-editor-screen">
                <p className="ligas-empty">Liga não encontrada.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    const escudoList = escudos?.data ?? [];
    const pagination = {
        current: escudos?.current_page ?? 1,
        last: escudos?.last_page ?? 1,
        total: escudos?.total ?? escudoList.length,
        next: escudos?.next_page_url ?? null,
        prev: escudos?.prev_page_url ?? null,
    };
    const totalPages = Math.max(1, Number(pagination.last) || 1);
    const currentPage = Math.min(Math.max(1, Number(pagination.current) || 1), totalPages);
    const totalEscudos = Number(pagination.total) || 0;
    const paginationProgress = Math.min(100, (currentPage / totalPages) * 100);

    const currentClub = clubSnapshot ?? clube;
    const savedEscudoPreview = currentClub?.escudo ?? null;
    const selectedEscudoPreview = useMemo(() => {
        if (!selectedEscudoId) {
            return null;
        }
        const match = escudoList.find((escudo) => String(escudo.id) === String(selectedEscudoId));
        if (match) return match;
        if (savedEscudoPreview && String(savedEscudoPreview.id) === String(selectedEscudoId)) {
            return savedEscudoPreview;
        }
        return null;
    }, [selectedEscudoId, escudoList, savedEscudoPreview]);

    const isEscudoDisabled = (escudoId) =>
        usedEscudos.has(Number(escudoId)) && String(escudoId) !== String(selectedEscudoId);

    useEffect(() => {
        if (!activeModal) {
            return;
        }
        const nextName = currentClub?.nome ?? '';
        const nextEscudoId = currentClub?.escudo_id ? String(currentClub.escudo_id) : '';
        setClubName(nextName);
        setSelectedEscudoId(nextEscudoId);
        setErrors([]);
    }, [activeModal, currentClub]);

    const openModal = (type) => {
        setErrors([]);
        setFeedback('');
        setActiveModal(type);
        updateModalInUrl(type, liga.id);
    };

    const closeModal = () => {
        setErrors([]);
        setActiveModal(null);
        updateModalInUrl(null, liga.id);
    };

    const handleSaveClub = async (event) => {
        if (event) {
            event.preventDefault();
        }

        const formErrors = [];
        const trimmedName = clubName.trim();
        if (!trimmedName) {
            formErrors.push('Informe um nome para o clube.');
        }

        if (formErrors.length > 0) {
            setErrors(formErrors);
            return;
        }

        setSaving(true);
        setErrors([]);
        setFeedback('');

        try {
            const payload = new FormData();
            payload.append('nome', trimmedName);
            payload.append('liga_id', liga.id);
            if (selectedEscudoId) {
                payload.append('escudo_id', selectedEscudoId);
            }

            const { data: response } = await window.axios.post('/minha_liga/clubes', payload);
            const snapshot = currentClub ?? {};
            const selectedPreview = selectedEscudoId && selectedEscudoPreview
                ? {
                    id: selectedEscudoPreview.id,
                    clube_nome: selectedEscudoPreview.clube_nome,
                    clube_imagem: selectedEscudoPreview.clube_imagem,
                }
                : null;

            const apiClub = response?.clube ?? {};
            const initialCount = response?.initial_roster_count || 0;

            setClubSnapshot({
                ...snapshot,
                ...apiClub,
                nome: trimmedName,
                escudo_id: selectedEscudoId ? Number(selectedEscudoId) : apiClub?.escudo_id ?? null,
                escudo: selectedPreview ?? apiClub?.escudo ?? null,
                saldo: response?.financeiro?.saldo ?? apiClub?.saldo ?? snapshot.saldo,
                elenco_count: initialCount || apiClub?.elenco_count || snapshot.elenco_count || 0,
            });
            const baseMessage = response?.message ?? 'Clube atualizado com sucesso.';
            const rosterMessage = response?.initial_roster_message ?? '';
            setFeedback([baseMessage, rosterMessage].filter(Boolean).join(' '));
            setFeedbackCta(response?.initial_roster_cta ?? '');
            closeModal();
        } catch (error) {
            const message =
                error.response?.data?.message ??
                'Não foi possível salvar o clube. Tente novamente.';
            setErrors([message]);
        } finally {
            setSaving(false);
        }
    };

    const applyFilters = (event) => {
        if (event) event.preventDefault();
        const url = buildUrl(liga.id, {
            search: filters.search,
            escudo_pais_id: filters.escudo_pais_id,
            escudo_liga_id: filters.escudo_liga_id,
            only_available: filters.only_available ? '1' : '',
        }, activeModal === 'escudo' ? 'escudo' : null);
        window.navigateWithLoader(url);
    };

    const clearFilters = () => {
        setFilters(getInitialFilters({}));
        window.navigateWithLoader(buildUrl(liga.id, {}, activeModal === 'escudo' ? 'escudo' : null));
    };

    const goToPage = (url) => {
        if (!url) return;
        if (activeModal !== 'escudo') {
            window.navigateWithLoader(url);
            return;
        }
        const target = new URL(url, window.location.origin);
        target.searchParams.set('modal', 'escudo');
        window.navigateWithLoader(`${target.pathname}?${target.searchParams.toString()}`);
    };

    const financeiroHref = `/minha_liga/financeiro?liga_id=${liga.id}`;
    const meuElencoHref = `/minha_liga/meu-elenco?liga_id=${liga.id}`;
    const conquistasHref = `/minha_liga/clube/conquistas?liga_id=${liga.id}`;
    const patrociniosHref = `/minha_liga/clube/patrocinio?liga_id=${liga.id}`;
    const clubDisplayName = currentClub?.nome || 'Meu clube';
    const clubEscudoImage = savedEscudoPreview?.clube_imagem
        ? `/storage/${savedEscudoPreview.clube_imagem}`
        : null;
    const hasClub = Boolean(currentClub?.id);

    return (
        <main className="mco-screen club-home-screen" aria-label="Meu clube">
            <section className="club-editor-hero">
                <h1 className="club-editor-title">Meu clube</h1>
                <p className="club-editor-subtitle">
                    Gerencie nome, escudo, elenco e financeiro do seu clube.
                </p>
            </section>

            <section className="club-summary gold-card club-summary-custom">
                <article className="club-summary-card-inner">
                    <div className="club-summary-banner">
                        <div className="club-summary-badge">
                            {clubEscudoImage ? (
                                <img src={clubEscudoImage} alt={`Escudo do ${clubDisplayName}`} />
                            ) : (
                                <span>{getLeagueInitials(clubDisplayName || liga.nome)}</span>
                            )}
                        </div>
                        <div className="club-summary-info">
                            <p className="club-summary-title">{clubDisplayName}</p>
                            <div className="club-summary-status">
                                <span className="club-summary-dot" />
                                <span>Manager ativo · nível 1</span>
                            </div>
                        </div>
                    </div>
                    <div className="club-summary-stats club-summary-stats-custom">
                        <div>
                            <span>Elenco</span>
                            <strong>{currentClub?.elenco_count ?? 0} jogadores</strong>
                        </div>
                        <div>
                            <span>Saldo</span>
                            <strong>{formatAbbreviatedCurrency(currentClub?.saldo)}</strong>
                        </div>
                        <div>
                            <span>Partidas</span>
                            <strong>{currentClub?.partidas_jogadas ?? 0}</strong>
                        </div>
                        <div>
                            <span>Gols marcados</span>
                            <strong>{currentClub?.gols_marcados ?? 0}</strong>
                        </div>
                        <div>
                            <span>Vitórias</span>
                            <strong>{currentClub?.vitorias ?? 0}</strong>
                        </div>
                        <div>
                            <span>Fans</span>
                            <strong>{formatFans(currentClub?.fans)}</strong>
                        </div>
                    </div>
                </article>
            </section>

            <section className="league-actions league-actions-custom">
                <button type="button" className="control-card" onClick={() => openModal('nome')}>
                    <span className="inner-content">
                        <svg className="card-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71L12 2z" fill="currentColor" stroke="none" />
                        </svg>
                        <span className="control-card-title">
                            {hasClub ? `Nome do clube: ${clubDisplayName}` : 'Criar clube'}
                        </span>
                    </span>
                </button>
                <button
                    type="button"
                    className={`control-card${hasClub ? '' : ' is-disabled'}`}
                    onClick={() => openModal('escudo')}
                    disabled={!hasClub}
                >
                    <span className="inner-content">
                        <svg className="card-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z" fill="currentColor" stroke="none" />
                        </svg>
                        <span className="control-card-title">Editar escudo</span>
                    </span>
                </button>
                <a
                    className={`control-card${hasClub ? '' : ' is-disabled'}`}
                    href={hasClub ? financeiroHref : '#'}
                    onClick={(event) => {
                        if (!hasClub) {
                            event.preventDefault();
                        }
                    }}
                >
                    <span className="inner-content">
                        <svg className="card-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z" fill="currentColor" stroke="none" />
                        </svg>
                        <span className="control-card-title">Financeiro</span>
                    </span>
                </a>
                <a
                    className={`control-card${hasClub ? '' : ' is-disabled'}`}
                    href={hasClub ? meuElencoHref : '#'}
                    onClick={(event) => {
                        if (!hasClub) {
                            event.preventDefault();
                        }
                    }}
                >
                    <span className="inner-content">
                        <svg className="card-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" fill="currentColor" stroke="none" />
                        </svg>
                        <span className="control-card-title">Meu elenco</span>
                    </span>
                </a>
                <a
                    className={`control-card${hasClub ? '' : ' is-disabled'}`}
                    href={hasClub ? conquistasHref : '#'}
                    onClick={(event) => {
                        if (!hasClub) {
                            event.preventDefault();
                        }
                    }}
                >
                    <span className="inner-content">
                        <svg className="card-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94.63 1.5 1.98 2.63 3.61 2.96V19H7v2h10v-2h-4v-3.1c1.63-.33 2.98-1.46 3.61-2.96C19.08 10.63 21 8.55 21 8V7c0-1.1-.9-2-2-2zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z" fill="currentColor" stroke="none" />
                        </svg>
                        <span className="control-card-title">Conquistas</span>
                    </span>
                </a>
                <a
                    className={`control-card${hasClub ? '' : ' is-disabled'}`}
                    href={hasClub ? patrociniosHref : '#'}
                    onClick={(event) => {
                        if (!hasClub) {
                            event.preventDefault();
                        }
                    }}
                >
                    <span className="inner-content">
                        <svg className="card-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M4 5h16v4H4zM4 11h16v6H4z"
                                fill="currentColor"
                                stroke="none"
                            />
                        </svg>
                        <span className="control-card-title">Patrocínios</span>
                    </span>
                </a>
            </section>

            {feedback && (
                <Alert
                    variant="success"
                    description={feedback}
                    floating
                    onClose={() => {
                        setFeedback('');
                        setFeedbackCta('');
                    }}
                    actions={
                        feedbackCta ? (
                            <a className="btn-primary" href={feedbackCta}>
                                Ver meu elenco
                            </a>
                        ) : null
                    }
                />
            )}

            {activeModal === 'nome' && (
                <div className="club-modal-overlay" role="dialog" aria-modal="true" aria-label="Editar nome do clube">
                    <div className="club-modal">
                        <main className="club-editor-screen" aria-label="Editar nome do clube">
                            <section className="club-editor-hero">
                                <div className="club-modal-header">
                                    <button type="button" className="btn-outline" onClick={closeModal}>
                                        Fechar
                                    </button>
                                </div>
                                <h1 className="club-editor-title">Nome do clube</h1>
                                <p className="club-editor-subtitle">Atualize o nome oficial do clube.</p>
                            </section>

                            <form className="club-editor-form" onSubmit={handleSaveClub}>
                                <div className="club-editor-profile">
                                    <div className="club-editor-preview">
                                        {selectedEscudoPreview?.clube_imagem ? (
                                            <img
                                                src={`/storage/${selectedEscudoPreview.clube_imagem}`}
                                                alt={selectedEscudoPreview.clube_nome}
                                            />
                                        ) : (
                                            <span>{getLeagueInitials(clubName || liga.nome)}</span>
                                        )}
                                    </div>
                                    <div className="club-editor-input">
                                        <label htmlFor="club-editor-name">Nome do clube</label>
                                        <input
                                            id="club-editor-name"
                                            type="text"
                                            value={clubName}
                                            onChange={(event) => setClubName(event.target.value)}
                                            placeholder="Ex.: Furia FC"
                                        />
                                    </div>
                                </div>

                                {errors.length > 0 && (
                                    <ul className="club-editor-errors">
                                        {errors.map((error) => (
                                            <li key={error}>{error}</li>
                                        ))}
                                    </ul>
                                )}
                            </form>

                            <div className="club-editor-actions">
                                <button type="button" className="btn-outline" onClick={closeModal}>
                                    Cancelar
                                </button>
                                <button type="button" className="btn-primary" onClick={handleSaveClub} disabled={saving}>
                                    {saving ? 'Salvando...' : 'Salvar nome'}
                                </button>
                            </div>
                        </main>
                    </div>
                </div>
            )}

            {activeModal === 'escudo' && (
                <div className="club-modal-overlay" role="dialog" aria-modal="true" aria-label="Editar escudo">
                    <div className="club-modal">
                        <main className="club-editor-screen" aria-label="Editar escudo">
                            <section className="club-editor-hero">
                                <div className="club-modal-header">
                                    <button type="button" className="btn-outline" onClick={closeModal}>
                                        Fechar
                                    </button>
                                </div>
                                <h1 className="club-editor-title">Editar escudo</h1>
                                <p className="club-editor-subtitle">
                                    Selecione o escudo para o clube {clubDisplayName}.
                                </p>
                            </section>

                            <section className="club-editor-filters">
                                <form onSubmit={applyFilters} className="club-editor-filter-form">
                                    <div className="club-editor-search-row">
                                        <input
                                            type="search"
                                            value={filters.search}
                                            placeholder="Buscar escudos..."
                                            onChange={(event) =>
                                                setFilters((prev) => ({ ...prev, search: event.target.value }))
                                            }
                                        />
                                        <button
                                            type="button"
                                            className="club-editor-filter-toggle"
                                            onClick={() => setFiltersOpen((open) => !open)}
                                            aria-expanded={filtersOpen}
                                            aria-controls="club-editor-filters-panel"
                                        >
                                            Filtros
                                        </button>
                                    </div>

                                    {filtersOpen && (
                                        <div className="club-editor-filters-panel" id="club-editor-filters-panel">
                                            <div className="club-editor-filter-grid">
                                                <select
                                                    value={filters.escudo_pais_id}
                                                    onChange={(event) =>
                                                        setFilters((prev) => ({
                                                            ...prev,
                                                            escudo_pais_id: event.target.value,
                                                        }))
                                                    }
                                                >
                                                    <option value="">País</option>
                                                    {paises.map((pais) => (
                                                        <option key={pais.id} value={pais.id}>
                                                            {pais.nome}
                                                        </option>
                                                    ))}
                                                </select>
                                                <select
                                                    value={filters.escudo_liga_id}
                                                    onChange={(event) =>
                                                        setFilters((prev) => ({
                                                            ...prev,
                                                            escudo_liga_id: event.target.value,
                                                        }))
                                                    }
                                                >
                                                    <option value="">Liga de origem</option>
                                                    {ligasEscudos.map((ligaEscudo) => (
                                                        <option key={ligaEscudo.id} value={ligaEscudo.id}>
                                                            {ligaEscudo.liga_nome}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>

                                            <label className="club-editor-toggle">
                                                <input
                                                    type="checkbox"
                                                    checked={filters.only_available}
                                                    onChange={(event) =>
                                                        setFilters((prev) => ({
                                                            ...prev,
                                                            only_available: event.target.checked,
                                                        }))
                                                    }
                                                />
                                                Somente disponíveis
                                            </label>
                                            <div className="club-editor-filter-actions">
                                                <button type="button" className="btn-outline" onClick={clearFilters}>
                                                    Limpar
                                                </button>
                                                <button type="submit" className="btn-primary">
                                                    Aplicar filtros
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </form>
                            </section>

                            <section className="club-editor-grid">
                                <button
                                    type="button"
                                    className={`club-editor-card club-editor-card-none${selectedEscudoId ? '' : ' is-selected'}`}
                                    onClick={() => setSelectedEscudoId('')}
                                    aria-pressed={!selectedEscudoId}
                                >
                                    <span className="club-editor-card-image">—</span>
                                    <span className="club-editor-card-name">Nenhum</span>
                                </button>

                                {escudoList.length === 0 && (
                                    <p className="club-editor-empty">Nenhum escudo encontrado.</p>
                                )}

                                {escudoList.map((escudo) => {
                                    const isSelected = String(escudo.id) === String(selectedEscudoId);
                                    const isDisabled = isEscudoDisabled(escudo.id);
                                    return (
                                        <button
                                            type="button"
                                            key={escudo.id}
                                            className={`club-editor-card${isSelected ? ' is-selected' : ''}${isDisabled ? ' is-disabled' : ''}`}
                                            onClick={() => setSelectedEscudoId(String(escudo.id))}
                                            aria-pressed={isSelected}
                                            disabled={isDisabled}
                                            aria-label={escudo.clube_nome}
                                            title={escudo.clube_nome}
                                        >
                                            {escudo.clube_imagem ? (
                                                <img
                                                    src={`/storage/${escudo.clube_imagem}`}
                                                    alt={escudo.clube_nome}
                                                    className="club-editor-card-image"
                                                />
                                            ) : (
                                                <span className="club-editor-card-image">
                                                    {getLeagueInitials(escudo.clube_nome)}
                                                </span>
                                            )}
                                            {isDisabled && <span className="club-editor-card-status">Em uso</span>}
                                        </button>
                                    );
                                })}
                            </section>

                            <section className="mco-pagination" aria-label="Resumo e paginação de escudos">
                                <span className="mco-pagination-count">
                                    <strong>{totalEscudos.toLocaleString('pt-BR')}</strong> escudos encontrados
                                </span>
                                <div className="mco-pagination-controls">
                                    <button
                                        type="button"
                                        className="btn-outline mco-pagination-button"
                                        onClick={() => goToPage(pagination.prev)}
                                        disabled={!pagination.prev}
                                    >
                                        ◀ Voltar
                                    </button>
                                    <div className="mco-pagination-label">
                                        <span>Página</span>
                                        <strong>
                                            <span>{currentPage}</span> / {totalPages}
                                        </strong>
                                    </div>
                                    <button
                                        type="button"
                                        className="btn-outline mco-pagination-button"
                                        onClick={() => goToPage(pagination.next)}
                                        disabled={!pagination.next}
                                    >
                                        Próxima ▶
                                    </button>
                                </div>
                                <div className="mco-pagination-progress">
                                    <div
                                        className="mco-pagination-progress-bar"
                                        style={{ width: `${paginationProgress}%` }}
                                    />
                                </div>
                            </section>

                            {errors.length > 0 && (
                                <ul className="club-editor-errors">
                                    {errors.map((error) => (
                                        <li key={error}>{error}</li>
                                    ))}
                                </ul>
                            )}

                            <div className="club-editor-actions">
                                <button type="button" className="btn-outline" onClick={closeModal}>
                                    Cancelar
                                </button>
                                <button type="button" className="btn-primary" onClick={handleSaveClub} disabled={saving}>
                                    {saving ? 'Salvando...' : 'Salvar escudo'}
                                </button>
                            </div>
                        </main>
                    </div>
                </div>
            )}

            <Navbar active="ligas" />
        </main>
    );
}
