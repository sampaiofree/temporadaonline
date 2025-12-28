import { useMemo, useState } from 'react';
import Navbar from '../components/app_publico/Navbar';

const getEditorData = () => window.__CLUBE_EDITOR__ ?? {};

const buildUrl = (ligaId, params) => {
    const searchParams = new URLSearchParams();
    searchParams.set('liga_id', ligaId);
    Object.entries(params).forEach(([key, value]) => {
        if (value === undefined || value === null || value === '') {
            return;
        }
        searchParams.set(key, value);
    });

    return `/minha_liga/clube?${searchParams.toString()}`;
};

const getInitialFilters = (filters) => ({
    search: filters?.search ?? '',
    escudo_pais_id: filters?.escudo_pais_id ?? '',
    escudo_liga_id: filters?.escudo_liga_id ?? '',
    only_available: Boolean(filters?.only_available ?? false),
});

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

    const [clubName, setClubName] = useState(clube?.nome ?? '');
    const [selectedEscudoId, setSelectedEscudoId] = useState(
        clube?.escudo_id ? String(clube.escudo_id) : '',
    );
    const [filters, setFilters] = useState(initialFilters);
    const [filtersOpen, setFiltersOpen] = useState(false);
    const [errors, setErrors] = useState([]);
    const [saving, setSaving] = useState(false);
    const [feedback, setFeedback] = useState('');

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

    const selectedEscudoPreview = useMemo(() => {
        if (!selectedEscudoId) return clube?.escudo ?? null;
        const match = escudoList.find((escudo) => String(escudo.id) === String(selectedEscudoId));
        if (match) return match;
        if (clube?.escudo && String(clube.escudo.id) === String(selectedEscudoId)) {
            return clube.escudo;
        }
        return null;
    }, [selectedEscudoId, escudoList, clube]);

    const isEscudoDisabled = (escudoId) =>
        usedEscudos.has(Number(escudoId)) && String(escudoId) !== String(selectedEscudoId);

    const handleSubmit = async (event) => {
        event.preventDefault();

        const formErrors = [];
        if (!clubName.trim()) {
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
            payload.append('nome', clubName.trim());
            payload.append('liga_id', liga.id);
            if (selectedEscudoId) {
                payload.append('escudo_id', selectedEscudoId);
            }

            const { data: response } = await window.axios.post('/minha_liga/clubes', payload);
            setFeedback(response?.message ?? 'Clube atualizado com sucesso.');
            window.navigateWithLoader(`/minha_liga?liga_id=${liga.id}`);
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
        });
        window.navigateWithLoader(url);
    };

    const clearFilters = () => {
        setFilters(getInitialFilters({}));
        window.navigateWithLoader(buildUrl(liga.id, {}));
    };

    const goToPage = (url) => {
        if (!url) return;
        window.navigateWithLoader(url);
    };

    return (
        <main className="mco-screen club-editor-screen" aria-label="Meu clube">
            <section className="club-editor-hero">
                <h1 className="club-editor-title">Identidade do clube</h1>
                <p className="club-editor-subtitle">Escolha um nome e o seu brasão oficial.</p>
            </section>

            <form className="club-editor-form" onSubmit={handleSubmit}>
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

            <div className="club-editor-actions">
                <button
                    type="button"
                    className="btn-outline"
                    onClick={() => {
                        window.navigateWithLoader(`/minha_liga?liga_id=${liga.id}`);
                    }}
                >
                    Voltar
                </button>
                <button type="button" className="btn-primary" onClick={handleSubmit} disabled={saving}>
                    {saving ? 'Salvando...' : 'Salvar clube'}
                </button>
            </div>

            {feedback && <p className="club-editor-feedback">{feedback}</p>}
            <Navbar active="ligas" />
        </main>
    );
}
