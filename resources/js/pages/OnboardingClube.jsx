import { useEffect, useMemo, useState } from 'react';
import Navbar from '../components/app_publico/Navbar';
import Alert from '../components/app_publico/Alert';

const TOTAL_STEPS = 4;

const getOnboardingData = () => window.__CLUBE_ONBOARDING__ ?? {};

const getStepFromUrl = () => {
    const value = Number(new URLSearchParams(window.location.search).get('step'));
    if (Number.isInteger(value) && value >= 1 && value <= TOTAL_STEPS) {
        return value;
    }

    return 1;
};

const getPickedEscudoFromUrl = () => {
    const value = new URLSearchParams(window.location.search).get('pick_escudo_id');
    return value === null ? null : value;
};

const getInitialFilters = (filters) => ({
    search: filters?.search ?? '',
    escudo_pais_id: filters?.escudo_pais_id ?? '',
    escudo_liga_id: filters?.escudo_liga_id ?? '',
    only_available: Boolean(filters?.only_available ?? false),
});

const buildOnboardingUrl = (ligaId, params = {}) => {
    const searchParams = new URLSearchParams();
    searchParams.set('liga_id', String(ligaId));

    Object.entries(params).forEach(([key, value]) => {
        if (value === undefined || value === null || value === '') {
            return;
        }
        searchParams.set(key, String(value));
    });

    return `/minha_liga/onboarding-clube?${searchParams.toString()}`;
};

const getLeagueInitials = (name) => {
    if (!name) return 'FC';
    const parts = name.split(/\s+/).filter(Boolean);
    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }
    return `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase();
};

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0,
});

const formatCurrency = (value) => {
    if (value === null || value === undefined) return '—';
    return currencyFormatter.format(value);
};

export default function OnboardingClube() {
    const data = getOnboardingData();
    const liga = data.liga ?? null;
    const clube = data.clube ?? null;
    const confederacaoNome = data.confederacao_nome ?? liga?.confederacao_nome ?? 'Não definida';
    const escudos = data.escudos ?? {};
    const paises = data.paises ?? [];
    const ligasEscudos = data.ligas_escudos ?? [];
    const usedEscudos = new Set((data.used_escudos ?? []).map((id) => Number(id)));
    const initialFilters = getInitialFilters(data.filters);
    const selectedFromUrl = getPickedEscudoFromUrl();

    const [step, setStep] = useState(getStepFromUrl());
    const [clubSnapshot, setClubSnapshot] = useState(clube);
    const [clubName, setClubName] = useState(clube?.nome ?? '');
    const [selectedEscudoId, setSelectedEscudoId] = useState(
        selectedFromUrl !== null ? selectedFromUrl : (clube?.escudo_id ? String(clube.escudo_id) : ''),
    );
    const [filters, setFilters] = useState(initialFilters);
    const [filtersOpen, setFiltersOpen] = useState(false);
    const [errors, setErrors] = useState([]);
    const [saving, setSaving] = useState(false);
    const [feedback, setFeedback] = useState('');
    const [feedbackCta, setFeedbackCta] = useState('');
    const [completed, setCompleted] = useState(false);

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
        if (match) {
            return match;
        }

        if (savedEscudoPreview && String(savedEscudoPreview.id) === String(selectedEscudoId)) {
            return savedEscudoPreview;
        }

        return null;
    }, [selectedEscudoId, escudoList, savedEscudoPreview]);

    useEffect(() => {
        const onPopState = () => {
            setStep(getStepFromUrl());
            const nextEscudoId = getPickedEscudoFromUrl();
            if (nextEscudoId !== null) {
                setSelectedEscudoId(nextEscudoId);
            }
        };

        window.addEventListener('popstate', onPopState);
        return () => window.removeEventListener('popstate', onPopState);
    }, []);

    if (!liga) {
        return (
            <main className="mco-screen club-onboarding-screen">
                <p className="ligas-empty">Liga não encontrada.</p>
                <Navbar active="liga" />
            </main>
        );
    }

    const updateUrlState = (updates, replace = true) => {
        const params = new URLSearchParams(window.location.search);

        if (!params.get('liga_id')) {
            params.set('liga_id', String(liga.id));
        }

        Object.entries(updates).forEach(([key, value]) => {
            if (value === undefined || value === null || value === '') {
                params.delete(key);
                return;
            }

            params.set(key, String(value));
        });

        const query = params.toString();
        const nextUrl = `${window.location.pathname}${query ? `?${query}` : ''}`;
        window.history[replace ? 'replaceState' : 'pushState'](null, '', nextUrl);
    };

    const goToStep = (nextStep) => {
        const normalized = Math.max(1, Math.min(TOTAL_STEPS, nextStep));
        setStep(normalized);
        updateUrlState({ step: normalized }, false);
    };

    const handleEscudoSelection = (value) => {
        setSelectedEscudoId(value);
        updateUrlState({ pick_escudo_id: value || null });
    };

    const isEscudoDisabled = (escudoId) =>
        usedEscudos.has(Number(escudoId)) && String(escudoId) !== String(selectedEscudoId);

    const applyFilters = (event) => {
        if (event) event.preventDefault();

        const url = buildOnboardingUrl(liga.id, {
            search: filters.search,
            escudo_pais_id: filters.escudo_pais_id,
            escudo_liga_id: filters.escudo_liga_id,
            only_available: filters.only_available ? '1' : '',
            step: 3,
            pick_escudo_id: selectedEscudoId || '',
        });

        window.navigateWithLoader(url);
    };

    const clearFilters = () => {
        setFilters(getInitialFilters({}));
        const url = buildOnboardingUrl(liga.id, {
            step: 3,
            pick_escudo_id: selectedEscudoId || '',
        });
        window.navigateWithLoader(url);
    };

    const goToPage = (url) => {
        if (!url) return;
        const target = new URL(url, window.location.origin);
        target.searchParams.set('step', '3');
        if (selectedEscudoId) {
            target.searchParams.set('pick_escudo_id', selectedEscudoId);
        }
        window.navigateWithLoader(`${target.pathname}?${target.searchParams.toString()}`);
    };

    const handleSubmit = async (event) => {
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
            setCompleted(true);
            goToStep(4);
        } catch (error) {
            const message =
                error.response?.data?.message ??
                'Não foi possível salvar o clube. Tente novamente.';
            setErrors([message]);
        } finally {
            setSaving(false);
        }
    };

    const clubEscudoImage = savedEscudoPreview?.clube_imagem
        ? `/storage/${savedEscudoPreview.clube_imagem}`
        : null;
    const selectedEscudoImage = selectedEscudoPreview?.clube_imagem
        ? `/storage/${selectedEscudoPreview.clube_imagem}`
        : null;
    const clubeNomeAtual = currentClub?.nome ?? 'Ainda não criado';
    const meuClubeHref = `/minha_liga/clube?liga_id=${liga.id}`;
    const meuElencoHref = feedbackCta || `/minha_liga/meu-elenco?liga_id=${liga.id}`;
    const minhaLigaHref = `/minha_liga?liga_id=${liga.id}`;
    const progress = (step / TOTAL_STEPS) * 100;

    return (
        <main className="mco-screen club-onboarding-screen" aria-label="Onboarding do clube">
            <div className="club-onboarding-bg-pattern" aria-hidden="true" />
            <div className="club-onboarding-bg-glow" aria-hidden="true" />

            <section className="club-onboarding-shell">
                <header className="club-onboarding-header">
                    <p className="club-onboarding-eyebrow">Onboarding do clube</p>
                    <h1 className="club-onboarding-title">Configure seu clube nesta liga</h1>
                    <p className="club-onboarding-subtitle">
                        Fluxo guiado com dados reais. Liga e confederação são informativas para este contexto.
                    </p>
                    <div className="club-onboarding-progress-track" role="progressbar" aria-valuemin={0} aria-valuemax={100} aria-valuenow={progress}>
                        <div className="club-onboarding-progress-bar" style={{ width: `${progress}%` }} />
                    </div>
                    <p className="club-onboarding-step-label">Passo {step} de {TOTAL_STEPS}</p>
                </header>

                {feedback && (
                    <Alert
                        variant="success"
                        description={feedback}
                        onClose={() => {
                            setFeedback('');
                            setFeedbackCta('');
                        }}
                        actions={(
                            <>
                                <a className="btn-outline" href={meuClubeHref}>
                                    Meu clube
                                </a>
                                <a className="btn-primary" href={meuElencoHref}>
                                    Meu elenco
                                </a>
                            </>
                        )}
                    />
                )}

                {step === 1 && (
                    <section className="club-onboarding-card" aria-label="Passo da liga">
                        <h2 className="club-onboarding-card-title">Liga selecionada</h2>
                        <p className="club-onboarding-card-subtitle">
                            O contexto da liga vem de `liga_id` e não pode ser alterado neste fluxo.
                        </p>

                        <article className="club-onboarding-highlight">
                            <div className="club-onboarding-badge">
                                {liga.imagem ? (
                                    <img src={`/storage/${liga.imagem}`} alt={`Escudo da liga ${liga.nome}`} />
                                ) : (
                                    <span>{getLeagueInitials(liga.nome)}</span>
                                )}
                            </div>
                            <div className="club-onboarding-highlight-body">
                                <p className="club-onboarding-highlight-label">Liga ativa</p>
                                <strong>{liga.nome}</strong>
                                <span>Resumo do ambiente competitivo atual.</span>
                            </div>
                        </article>

                        <div className="club-onboarding-meta-grid">
                            <div>
                                <span>Jogo</span>
                                <strong>{liga.jogo || '—'}</strong>
                            </div>
                            <div>
                                <span>Geração</span>
                                <strong>{liga.geracao || '—'}</strong>
                            </div>
                            <div>
                                <span>Plataforma</span>
                                <strong>{liga.plataforma || '—'}</strong>
                            </div>
                            <div>
                                <span>Status clube</span>
                                <strong>{currentClub?.id ? 'Existente' : 'Novo'}</strong>
                            </div>
                        </div>

                        <div className="club-onboarding-actions">
                            <button type="button" className="btn-outline" onClick={() => window.navigateWithLoader(minhaLigaHref)}>
                                Voltar para liga
                            </button>
                            <button type="button" className="btn-primary" onClick={() => goToStep(2)}>
                                Continuar
                            </button>
                        </div>
                    </section>
                )}

                {step === 2 && (
                    <section className="club-onboarding-card" aria-label="Passo da confederação">
                        <h2 className="club-onboarding-card-title">Confederação da liga</h2>
                        <p className="club-onboarding-card-subtitle">
                            Esta informação também é definida pela liga atual e aparece aqui para referência.
                        </p>

                        <article className="club-onboarding-highlight club-onboarding-highlight-confed">
                            <div className="club-onboarding-confed-icon" aria-hidden="true">◆</div>
                            <div className="club-onboarding-highlight-body">
                                <p className="club-onboarding-highlight-label">Confederação</p>
                                <strong>{confederacaoNome || 'Não definida'}</strong>
                                <span>Escudos e disponibilidade de jogadores respeitam este escopo.</span>
                            </div>
                        </article>

                        <div className="club-onboarding-actions">
                            <button type="button" className="btn-outline" onClick={() => goToStep(1)}>
                                Voltar
                            </button>
                            <button type="button" className="btn-primary" onClick={() => goToStep(3)}>
                                Escolher escudo
                            </button>
                        </div>
                    </section>
                )}

                {step === 3 && (
                    <section className="club-onboarding-card" aria-label="Passo de escudo">
                        <h2 className="club-onboarding-card-title">Escolha o escudo</h2>
                        <p className="club-onboarding-card-subtitle">
                            Escudos marcados como “Em uso” já pertencem a outro clube nesta confederação.
                        </p>

                        <div className="club-onboarding-selected">
                            <div className="club-onboarding-selected-preview">
                                {selectedEscudoImage ? (
                                    <img src={selectedEscudoImage} alt={selectedEscudoPreview?.clube_nome || 'Escudo selecionado'} />
                                ) : clubEscudoImage ? (
                                    <img src={clubEscudoImage} alt={savedEscudoPreview?.clube_nome || 'Escudo atual'} />
                                ) : (
                                    <span>{getLeagueInitials(clubeNomeAtual)}</span>
                                )}
                            </div>
                            <div className="club-onboarding-selected-body">
                                <span>Escudo selecionado</span>
                                <strong>{selectedEscudoPreview?.clube_nome || savedEscudoPreview?.clube_nome || 'Nenhum'}</strong>
                            </div>
                        </div>

                        <section className="club-onboarding-filters">
                            <form onSubmit={applyFilters} className="club-onboarding-filter-form">
                                <div className="club-onboarding-search-row">
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
                                        className="club-onboarding-filter-toggle"
                                        onClick={() => setFiltersOpen((open) => !open)}
                                        aria-expanded={filtersOpen}
                                        aria-controls="club-onboarding-filters-panel"
                                    >
                                        Filtros
                                    </button>
                                </div>

                                {filtersOpen && (
                                    <div className="club-onboarding-filters-panel" id="club-onboarding-filters-panel">
                                        <div className="club-onboarding-filter-grid">
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

                                        <label className="club-onboarding-toggle">
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
                                        <div className="club-onboarding-filter-actions">
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

                        <section className="club-onboarding-grid">
                            <button
                                type="button"
                                className={`club-onboarding-card-option club-onboarding-card-none${selectedEscudoId ? '' : ' is-selected'}`}
                                onClick={() => handleEscudoSelection('')}
                                aria-pressed={!selectedEscudoId}
                            >
                                <span className="club-onboarding-card-image">—</span>
                                <span className="club-onboarding-card-name">Nenhum</span>
                            </button>

                            {escudoList.length === 0 && (
                                <p className="club-onboarding-empty">Nenhum escudo encontrado.</p>
                            )}

                            {escudoList.map((escudo) => {
                                const isSelected = String(escudo.id) === String(selectedEscudoId);
                                const isDisabled = isEscudoDisabled(escudo.id);

                                return (
                                    <button
                                        type="button"
                                        key={escudo.id}
                                        className={`club-onboarding-card-option${isSelected ? ' is-selected' : ''}${isDisabled ? ' is-disabled' : ''}`}
                                        onClick={() => handleEscudoSelection(String(escudo.id))}
                                        aria-pressed={isSelected}
                                        disabled={isDisabled}
                                        aria-label={escudo.clube_nome}
                                        title={escudo.clube_nome}
                                    >
                                        {escudo.clube_imagem ? (
                                            <img
                                                src={`/storage/${escudo.clube_imagem}`}
                                                alt={escudo.clube_nome}
                                                className="club-onboarding-card-image"
                                            />
                                        ) : (
                                            <span className="club-onboarding-card-image">
                                                {getLeagueInitials(escudo.clube_nome)}
                                            </span>
                                        )}
                                        {isDisabled && <span className="club-onboarding-card-status">Em uso</span>}
                                    </button>
                                );
                            })}
                        </section>

                        <section className="club-onboarding-pagination" aria-label="Resumo e paginação de escudos">
                            <span className="club-onboarding-pagination-count">
                                <strong>{totalEscudos.toLocaleString('pt-BR')}</strong> escudos encontrados
                            </span>
                            <div className="club-onboarding-pagination-controls">
                                <button
                                    type="button"
                                    className="btn-outline club-onboarding-pagination-button"
                                    onClick={() => goToPage(pagination.prev)}
                                    disabled={!pagination.prev}
                                >
                                    ◀ Voltar
                                </button>
                                <div className="club-onboarding-pagination-label">
                                    <span>Página</span>
                                    <strong>
                                        <span>{currentPage}</span> / {totalPages}
                                    </strong>
                                </div>
                                <button
                                    type="button"
                                    className="btn-outline club-onboarding-pagination-button"
                                    onClick={() => goToPage(pagination.next)}
                                    disabled={!pagination.next}
                                >
                                    Próxima ▶
                                </button>
                            </div>
                            <div className="club-onboarding-pagination-progress">
                                <div
                                    className="club-onboarding-pagination-progress-bar"
                                    style={{ width: `${paginationProgress}%` }}
                                />
                            </div>
                        </section>

                        <div className="club-onboarding-actions">
                            <button type="button" className="btn-outline" onClick={() => goToStep(2)}>
                                Voltar
                            </button>
                            <button type="button" className="btn-primary" onClick={() => goToStep(4)}>
                                Revisar nome
                            </button>
                        </div>
                    </section>
                )}

                {step === 4 && (
                    <section className="club-onboarding-card" aria-label="Passo final">
                        <h2 className="club-onboarding-card-title">Nome e revisão final</h2>
                        <p className="club-onboarding-card-subtitle">
                            Confirme os dados e salve no backend real da liga.
                        </p>

                        {completed ? (
                            <article className="club-onboarding-success">
                                <h3>Clube salvo com sucesso</h3>
                                <p>Seu clube já está sincronizado com o projeto e pronto para os próximos módulos.</p>
                                <div className="club-onboarding-actions">
                                    <a className="btn-outline" href={meuClubeHref}>
                                        Abrir meu clube
                                    </a>
                                    <a className="btn-primary" href={meuElencoHref}>
                                        Abrir meu elenco
                                    </a>
                                </div>
                            </article>
                        ) : (
                            <form className="club-onboarding-form" onSubmit={handleSubmit}>
                                <div className="club-onboarding-profile">
                                    <div className="club-onboarding-preview">
                                        {selectedEscudoImage ? (
                                            <img
                                                src={selectedEscudoImage}
                                                alt={selectedEscudoPreview?.clube_nome || 'Escudo selecionado'}
                                            />
                                        ) : (
                                            <span>{getLeagueInitials(clubName || liga.nome)}</span>
                                        )}
                                    </div>
                                    <div className="club-onboarding-input">
                                        <label htmlFor="club-onboarding-name">Nome do clube</label>
                                        <input
                                            id="club-onboarding-name"
                                            type="text"
                                            value={clubName}
                                            onChange={(event) => setClubName(event.target.value)}
                                            placeholder="Ex.: Furia FC"
                                            maxLength={150}
                                        />
                                    </div>
                                </div>

                                <div className="club-onboarding-review-grid">
                                    <div>
                                        <span>Liga</span>
                                        <strong>{liga.nome}</strong>
                                    </div>
                                    <div>
                                        <span>Confederação</span>
                                        <strong>{confederacaoNome || 'Não definida'}</strong>
                                    </div>
                                    <div>
                                        <span>Escudo</span>
                                        <strong>{selectedEscudoPreview?.clube_nome || 'Nenhum'}</strong>
                                    </div>
                                    <div>
                                        <span>Saldo atual</span>
                                        <strong>{formatCurrency(currentClub?.saldo)}</strong>
                                    </div>
                                </div>

                                {errors.length > 0 && (
                                    <ul className="club-onboarding-errors">
                                        {errors.map((error) => (
                                            <li key={error}>{error}</li>
                                        ))}
                                    </ul>
                                )}

                                <div className="club-onboarding-actions">
                                    <button type="button" className="btn-outline" onClick={() => goToStep(3)}>
                                        Voltar
                                    </button>
                                    <button type="submit" className="btn-primary" disabled={saving}>
                                        {saving ? 'Salvando...' : 'Salvar clube'}
                                    </button>
                                </div>
                            </form>
                        )}
                    </section>
                )}
            </section>

            <Navbar active="liga" />
        </main>
    );
}
