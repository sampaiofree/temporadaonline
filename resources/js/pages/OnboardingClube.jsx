import { useEffect, useMemo, useState } from 'react';
import Navbar from '../components/app_publico/Navbar';
import Alert from '../components/app_publico/Alert';

const AGGRESSIVE_CLIP = 'polygon(16px 0, 100% 0, 100% calc(100% - 16px), calc(100% - 16px) 100%, 0 100%, 0 16px)';

const getOnboardingData = () => window.__CLUBE_ONBOARDING__ ?? {};

const getStepFromUrl = (totalSteps) => {
    const value = Number(new URLSearchParams(window.location.search).get('step'));
    if (Number.isInteger(value) && value >= 1 && value <= totalSteps) {
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

const buildOnboardingUrl = (ligaId, params = {}, basePath = '/minha_liga/onboarding-clube') => {
    const searchParams = new URLSearchParams();
    searchParams.set('liga_id', String(ligaId));

    Object.entries(params).forEach(([key, value]) => {
        if (value === undefined || value === null || value === '') {
            return;
        }
        searchParams.set(key, String(value));
    });

    return `${basePath}?${searchParams.toString()}`;
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

const LegacyButton = ({ children, onClick, disabled = false, variant = 'primary', className = '', type = 'button' }) => {
    const base = 'px-5 py-3 text-[10px] font-black uppercase italic tracking-[0.2em] transition-all active:translate-y-[1px] disabled:opacity-40 disabled:cursor-not-allowed';
    const variantClass = variant === 'outline'
        ? 'bg-[#121212] text-[#FFD700] border border-[#FFD700]/60'
        : 'bg-[#FFD700] text-[#121212]';

    return (
        <button
            type={type}
            onClick={onClick}
            disabled={disabled}
            className={`${base} ${variantClass} ${className}`.trim()}
            style={{ clipPath: AGGRESSIVE_CLIP }}
        >
            {children}
        </button>
    );
};

export default function OnboardingClube() {
    const data = getOnboardingData();
    const routes = data.routes ?? {};
    const liga = data.liga ?? null;
    const clube = data.clube ?? null;
    const confederacaoNome = data.confederacao_nome ?? liga?.confederacao_nome ?? 'Não definida';
    const escudos = data.escudos ?? {};
    const paises = data.paises ?? [];
    const ligasEscudos = data.ligas_escudos ?? [];
    const usedEscudos = new Set((data.used_escudos ?? []).map((id) => Number(id)));
    const initialFilters = getInitialFilters(data.filters);
    const selectedFromUrl = getPickedEscudoFromUrl();
    const onboardingBasePath = routes.onboarding_base_path || '/minha_liga/onboarding-clube';
    const storeClubeUrl = routes.store_clube_url || '/minha_liga/clubes';
    const stepMode = routes.step_mode === 'club_only' ? 'club_only' : 'full';
    const isClubOnlyMode = stepMode === 'club_only';
    const totalSteps = isClubOnlyMode ? 2 : 4;
    const confederacaoStep = 2;
    const escudoStep = isClubOnlyMode ? 1 : 3;
    const reviewStep = isClubOnlyMode ? 2 : 4;
    const selectUniverseUrl = routes.select_universe_url || onboardingBasePath;
    const showNavbar = routes.show_navbar !== false;

    const [step, setStep] = useState(getStepFromUrl(totalSteps));
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
            setStep(getStepFromUrl(totalSteps));
            const nextEscudoId = getPickedEscudoFromUrl();
            if (nextEscudoId !== null) {
                setSelectedEscudoId(nextEscudoId);
            }
        };

        window.addEventListener('popstate', onPopState);
        return () => window.removeEventListener('popstate', onPopState);
    }, [totalSteps]);

    if (!liga) {
        return (
            <main className="mco-screen club-onboarding-screen">
                <p className="ligas-empty">Liga não encontrada.</p>
                {showNavbar && <Navbar active="liga" />}
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
        const normalized = Math.max(1, Math.min(totalSteps, nextStep));
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
            step: escudoStep,
            pick_escudo_id: selectedEscudoId || '',
        }, onboardingBasePath);

        window.navigateWithLoader(url);
    };

    const clearFilters = () => {
        setFilters(getInitialFilters({}));
        const url = buildOnboardingUrl(liga.id, {
            step: escudoStep,
            pick_escudo_id: selectedEscudoId || '',
        }, onboardingBasePath);
        window.navigateWithLoader(url);
    };

    const goToPage = (url) => {
        if (!url) return;
        const target = new URL(url, window.location.origin);
        target.searchParams.set('step', String(escudoStep));
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

            const { data: response } = await window.axios.post(storeClubeUrl, payload);
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
            goToStep(reviewStep);
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
    const buildLigaUrlFromTemplate = (template, fallback) => {
        if (!template) {
            return fallback;
        }

        return template.includes('{liga_id}')
            ? template.replace('{liga_id}', String(liga.id))
            : template;
    };

    const meuClubeHref = buildLigaUrlFromTemplate(routes.meu_clube_url, `/minha_liga/clube?liga_id=${liga.id}`);
    const meuElencoHref = feedbackCta || buildLigaUrlFromTemplate(routes.meu_elenco_url, `/minha_liga/meu-elenco?liga_id=${liga.id}`);
    const minhaLigaHref = buildLigaUrlFromTemplate(routes.home_url, `/minha_liga?liga_id=${liga.id}`);
    const progress = (step / totalSteps) * 100;
    const introSubtitle = isClubOnlyMode
        ? 'Universo definido (confederação e liga). Agora escolha escudo e nome do clube.'
        : 'Fluxo guiado com dados reais. Liga e confederação são informativas para este contexto.';

    if (isClubOnlyMode) {
        return (
            <div className="min-h-screen bg-[#121212] px-6 py-20 relative overflow-hidden">
                <div className="absolute top-0 left-0 w-full h-full opacity-[0.03] pointer-events-none" style={{ backgroundImage: 'repeating-linear-gradient(45deg, rgba(255,215,0,0.35) 0, rgba(255,215,0,0.35) 1px, transparent 0, transparent 10px)' }} />
                <div className="absolute -top-20 -right-20 w-64 h-64 bg-[#FFD700] blur-[120px] opacity-10" />
                <div className="fixed top-0 left-0 w-full h-1 bg-white/5 z-[100]">
                    <div className="h-full bg-[#FFD700] shadow-[0_0_15px_#FFD700] transition-all duration-500" style={{ width: `${progress}%` }} />
                </div>

                <div className="max-w-3xl mx-auto w-full relative z-10 space-y-6">
                    <header className="space-y-3">
                        <p className="text-[10px] text-[#FFD700] font-black uppercase italic tracking-[0.35em]">Legacy XI</p>
                        <h1 className="text-4xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">
                            Configure seu clube
                        </h1>
                        <p className="text-[10px] text-white/40 font-bold uppercase italic tracking-[0.14em]">
                            Etapa {step} de {totalSteps}: {step === escudoStep ? 'escudo' : 'nome e revisão'}
                        </p>
                    </header>

                    {feedback ? (
                        <div className="bg-[#1E1E1E] border-l-[6px] border-[#008000] p-5 space-y-3" style={{ clipPath: AGGRESSIVE_CLIP }}>
                            <p className="text-[10px] text-[#FFD700] font-black uppercase italic tracking-[0.18em]">Concluído</p>
                            <p className="text-[12px] font-bold text-white/90">{feedback}</p>
                            <div className="flex flex-col sm:flex-row gap-3">
                                <a className="block w-full" href={meuClubeHref}>
                                    <LegacyButton variant="outline" className="w-full">Meu clube</LegacyButton>
                                </a>
                                <a className="block w-full" href={meuElencoHref}>
                                    <LegacyButton className="w-full">Meu elenco</LegacyButton>
                                </a>
                            </div>
                        </div>
                    ) : null}

                    {step === escudoStep && (
                        <section className="bg-[#1E1E1E] border-l-[6px] border-[#FFD700] p-6 md:p-8 space-y-6" style={{ clipPath: AGGRESSIVE_CLIP }}>
                            <div className="space-y-2">
                                <h2 className="text-2xl font-black italic uppercase font-heading text-white">1. Escudo do clube</h2>
                                <p className="text-[10px] text-white/45 font-bold uppercase italic tracking-[0.12em]">
                                    Liga: {liga.nome} • Confederação: {confederacaoNome || 'Não definida'}
                                </p>
                            </div>

                            <article className="bg-[#121212] p-4 border border-[#FFD700]/35 flex items-center gap-4" style={{ clipPath: AGGRESSIVE_CLIP }}>
                                <div className="w-14 h-14 bg-[#1E1E1E] border border-[#FFD700]/35 flex items-center justify-center overflow-hidden">
                                    {selectedEscudoImage ? (
                                        <img src={selectedEscudoImage} alt={selectedEscudoPreview?.clube_nome || 'Escudo selecionado'} className="w-full h-full object-contain p-1" />
                                    ) : clubEscudoImage ? (
                                        <img src={clubEscudoImage} alt={savedEscudoPreview?.clube_nome || 'Escudo atual'} className="w-full h-full object-contain p-1" />
                                    ) : (
                                        <span className="text-[#FFD700] font-black italic">{getLeagueInitials(clubeNomeAtual)}</span>
                                    )}
                                </div>
                                <div>
                                    <p className="text-[9px] text-[#FFD700] font-black uppercase italic tracking-[0.2em]">Selecionado</p>
                                    <strong className="block text-[13px] text-white font-black uppercase italic mt-1">
                                        {selectedEscudoPreview?.clube_nome || savedEscudoPreview?.clube_nome || 'Nenhum'}
                                    </strong>
                                </div>
                            </article>

                            <form onSubmit={applyFilters} className="space-y-3">
                                <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                                    <input
                                        type="search"
                                        value={filters.search}
                                        placeholder="Buscar escudos..."
                                        onChange={(event) => setFilters((prev) => ({ ...prev, search: event.target.value }))}
                                        className="md:col-span-2 bg-[#121212] border border-[#FFD700]/25 text-white p-3 text-[10px] font-black italic uppercase outline-none"
                                        style={{ clipPath: AGGRESSIVE_CLIP }}
                                    />
                                    <select
                                        value={filters.escudo_pais_id}
                                        onChange={(event) => setFilters((prev) => ({ ...prev, escudo_pais_id: event.target.value }))}
                                        className="bg-[#121212] border border-[#FFD700]/25 text-white p-3 text-[10px] font-black italic uppercase outline-none"
                                        style={{ clipPath: AGGRESSIVE_CLIP }}
                                    >
                                        <option value="">País</option>
                                        {paises.map((pais) => (
                                            <option key={pais.id} value={pais.id}>{pais.nome}</option>
                                        ))}
                                    </select>
                                    <select
                                        value={filters.escudo_liga_id}
                                        onChange={(event) => setFilters((prev) => ({ ...prev, escudo_liga_id: event.target.value }))}
                                        className="bg-[#121212] border border-[#FFD700]/25 text-white p-3 text-[10px] font-black italic uppercase outline-none"
                                        style={{ clipPath: AGGRESSIVE_CLIP }}
                                    >
                                        <option value="">Liga de origem</option>
                                        {ligasEscudos.map((ligaEscudo) => (
                                            <option key={ligaEscudo.id} value={ligaEscudo.id}>{ligaEscudo.liga_nome}</option>
                                        ))}
                                    </select>
                                </div>
                                <label className="flex items-center gap-2 text-[10px] font-black uppercase italic text-white/70">
                                    <input
                                        type="checkbox"
                                        checked={filters.only_available}
                                        onChange={(event) => setFilters((prev) => ({ ...prev, only_available: event.target.checked }))}
                                    />
                                    Somente disponíveis
                                </label>
                                <div className="flex flex-col sm:flex-row gap-3">
                                    <LegacyButton type="button" variant="outline" className="w-full" onClick={clearFilters}>Limpar</LegacyButton>
                                    <LegacyButton type="submit" className="w-full">Aplicar filtros</LegacyButton>
                                </div>
                            </form>

                            <div className="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3">
                                <button
                                    type="button"
                                    onClick={() => handleEscudoSelection('')}
                                    className={`p-3 border text-center ${selectedEscudoId ? 'bg-[#121212] border-white/15' : 'bg-[#FFD700] border-[#FFD700] text-[#121212]'}`}
                                    style={{ clipPath: AGGRESSIVE_CLIP }}
                                >
                                    <span className="block text-lg font-black italic">—</span>
                                    <span className="text-[8px] font-black uppercase italic">Nenhum</span>
                                </button>
                                {escudoList.map((escudo) => {
                                    const isSelected = String(escudo.id) === String(selectedEscudoId);
                                    const isDisabled = isEscudoDisabled(escudo.id);
                                    return (
                                        <button
                                            type="button"
                                            key={escudo.id}
                                            className={`p-2 border relative ${isSelected ? 'bg-[#FFD700] border-[#FFD700]' : 'bg-[#121212] border-white/15'} ${isDisabled ? 'opacity-40 cursor-not-allowed' : ''}`}
                                            style={{ clipPath: AGGRESSIVE_CLIP }}
                                            onClick={() => handleEscudoSelection(String(escudo.id))}
                                            disabled={isDisabled}
                                            title={escudo.clube_nome}
                                        >
                                            {escudo.clube_imagem ? (
                                                <img src={`/storage/${escudo.clube_imagem}`} alt={escudo.clube_nome} className="w-full h-12 object-contain" />
                                            ) : (
                                                <div className="h-12 flex items-center justify-center text-[10px] font-black italic">{getLeagueInitials(escudo.clube_nome)}</div>
                                            )}
                                            {isDisabled ? <span className="absolute top-1 right-1 text-[8px] px-1 bg-[#B22222] text-white">Uso</span> : null}
                                        </button>
                                    );
                                })}
                            </div>

                            <div className="bg-[#121212] border border-[#FFD700]/25 p-3 space-y-3" style={{ clipPath: AGGRESSIVE_CLIP }}>
                                <div className="flex items-center justify-between text-[10px] font-black uppercase italic">
                                    <span className="text-white/70">{totalEscudos.toLocaleString('pt-BR')} escudos</span>
                                    <span className="text-[#FFD700]">Página {currentPage}/{totalPages}</span>
                                </div>
                                <div className="flex gap-3">
                                    <LegacyButton type="button" variant="outline" className="w-full" onClick={() => goToPage(pagination.prev)} disabled={!pagination.prev}>
                                        ◀ Voltar
                                    </LegacyButton>
                                    <LegacyButton type="button" variant="outline" className="w-full" onClick={() => goToPage(pagination.next)} disabled={!pagination.next}>
                                        Próxima ▶
                                    </LegacyButton>
                                </div>
                            </div>

                            <div className="flex flex-col sm:flex-row gap-3">
                                <LegacyButton type="button" variant="outline" className="w-full" onClick={() => window.navigateWithLoader(selectUniverseUrl)}>
                                    Voltar para ligas
                                </LegacyButton>
                                <LegacyButton type="button" className="w-full" onClick={() => goToStep(reviewStep)}>
                                    Revisar nome
                                </LegacyButton>
                            </div>
                        </section>
                    )}

                    {step === reviewStep && (
                        <section className="bg-[#1E1E1E] border-l-[6px] border-[#FFD700] p-6 md:p-8 space-y-6" style={{ clipPath: AGGRESSIVE_CLIP }}>
                            <div className="space-y-2">
                                <h2 className="text-2xl font-black italic uppercase font-heading text-white">2. Nome e revisão</h2>
                                <p className="text-[10px] text-white/45 font-bold uppercase italic tracking-[0.12em]">
                                    Confirme os dados antes de salvar.
                                </p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="bg-[#121212] p-4 border border-[#FFD700]/25" style={{ clipPath: AGGRESSIVE_CLIP }}>
                                    <label htmlFor="club-onboarding-name-legacy" className="block text-[9px] text-[#FFD700] font-black uppercase italic tracking-[0.2em] mb-2">
                                        Nome do clube
                                    </label>
                                    <input
                                        id="club-onboarding-name-legacy"
                                        type="text"
                                        value={clubName}
                                        onChange={(event) => setClubName(event.target.value)}
                                        placeholder="Ex.: Furia FC"
                                        maxLength={150}
                                        className="w-full bg-[#1E1E1E] border border-white/15 text-white p-3 text-[11px] font-black italic uppercase outline-none"
                                        style={{ clipPath: AGGRESSIVE_CLIP }}
                                    />
                                </div>
                                <div className="bg-[#121212] p-4 border border-[#FFD700]/25 space-y-2" style={{ clipPath: AGGRESSIVE_CLIP }}>
                                    <p className="text-[9px] text-[#FFD700] font-black uppercase italic tracking-[0.2em]">Resumo</p>
                                    <p className="text-[10px] text-white/70 uppercase italic"><strong className="text-white">Liga:</strong> {liga.nome}</p>
                                    <p className="text-[10px] text-white/70 uppercase italic"><strong className="text-white">Confederação:</strong> {confederacaoNome || 'Não definida'}</p>
                                    <p className="text-[10px] text-white/70 uppercase italic"><strong className="text-white">Escudo:</strong> {selectedEscudoPreview?.clube_nome || savedEscudoPreview?.clube_nome || 'Nenhum'}</p>
                                    <p className="text-[10px] text-white/70 uppercase italic"><strong className="text-white">Saldo:</strong> {formatCurrency(currentClub?.saldo)}</p>
                                </div>
                            </div>

                            {errors.length > 0 ? (
                                <div className="bg-[#B22222]/25 border border-[#B22222] p-3 text-[10px] font-black uppercase italic tracking-[0.13em]" style={{ clipPath: AGGRESSIVE_CLIP }}>
                                    {errors.join(' ')}
                                </div>
                            ) : null}

                            <div className="flex flex-col sm:flex-row gap-3">
                                <LegacyButton type="button" variant="outline" className="w-full" onClick={() => goToStep(escudoStep)}>
                                    Voltar para escudo
                                </LegacyButton>
                                <LegacyButton type="button" className="w-full" onClick={handleSubmit} disabled={saving || completed}>
                                    {saving ? 'Salvando...' : 'Salvar clube'}
                                </LegacyButton>
                            </div>
                        </section>
                    )}
                </div>
            </div>
        );
    }

    return (
        <main className="mco-screen club-onboarding-screen" aria-label="Onboarding do clube">
            <div className="club-onboarding-bg-pattern" aria-hidden="true" />
            <div className="club-onboarding-bg-glow" aria-hidden="true" />

            <section className="club-onboarding-shell">
                <header className="club-onboarding-header">
                    <p className="club-onboarding-eyebrow">Onboarding do clube</p>
                    <h1 className="club-onboarding-title">Configure seu clube nesta liga</h1>
                    <p className="club-onboarding-subtitle">
                        {introSubtitle}
                    </p>
                    <div className="club-onboarding-progress-track" role="progressbar" aria-valuemin={0} aria-valuemax={100} aria-valuenow={progress}>
                        <div className="club-onboarding-progress-bar" style={{ width: `${progress}%` }} />
                    </div>
                    <p className="club-onboarding-step-label">Passo {step} de {totalSteps}</p>
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

                {!isClubOnlyMode && step === 1 && (
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
                            <button type="button" className="btn-primary" onClick={() => goToStep(confederacaoStep)}>
                                Continuar
                            </button>
                        </div>
                    </section>
                )}

                {!isClubOnlyMode && step === confederacaoStep && (
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
                            <button type="button" className="btn-primary" onClick={() => goToStep(escudoStep)}>
                                Escolher escudo
                            </button>
                        </div>
                    </section>
                )}

                {step === escudoStep && (
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
                            {isClubOnlyMode ? (
                                <button
                                    type="button"
                                    className="btn-outline"
                                    onClick={() => window.navigateWithLoader(selectUniverseUrl)}
                                >
                                    Voltar para ligas
                                </button>
                            ) : (
                                <button type="button" className="btn-outline" onClick={() => goToStep(confederacaoStep)}>
                                    Voltar
                                </button>
                            )}
                            <button type="button" className="btn-primary" onClick={() => goToStep(reviewStep)}>
                                Revisar nome
                            </button>
                        </div>
                    </section>
                )}

                {step === reviewStep && (
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
                                    <button type="button" className="btn-outline" onClick={() => goToStep(escudoStep)}>
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

            {showNavbar && <Navbar active="liga" />}
        </main>
    );
}
