import { useState } from 'react';
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

const getLeagueInitials = (name) => {
    if (!name) return 'MCO';
    const parts = name.split(/\s+/).filter(Boolean);
    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }
    return `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase();
};

const getAppContext = () => window.__APP_CONTEXT__ ?? null;

const getLigaFromWindow = () => window.__LIGA__ ?? null;

export default function MinhaLiga() {
    const liga = getLigaFromWindow();
    const appContext = getAppContext();
    const resolveExistingClubName = () => appContext?.clube?.nome ?? '';

    if (!liga) {
        return (
            <main className="mco-screen" aria-label="Minha liga">
                <p className="ligas-empty">Sua liga não foi encontrada. Volte para a lista e tente novamente.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    const mercadoHref = `/liga/mercado?liga_id=${liga.id}`;
    const financeiroHref = `/minha_liga/financeiro?liga_id=${liga.id}`;
    const meuElencoHref = `/minha_liga/meu-elenco?liga_id=${liga.id}`;
    const [isClubModalOpen, setClubModalOpen] = useState(false);
    const [clubName, setClubName] = useState(resolveExistingClubName());
    const [clubFeedback, setClubFeedback] = useState('');
    const [clubErrors, setClubErrors] = useState([]);
    const [isSavingClub, setIsSavingClub] = useState(false);

    const clearClubForm = () => {
        setClubName(resolveExistingClubName());
        setClubErrors([]);
    };

    const handleClubSubmit = async (event) => {
        event.preventDefault();

        const errors = [];
        if (!clubName.trim()) {
            errors.push('Informe um nome para o clube.');
        }

        if (errors.length > 0) {
            setClubErrors(errors);
            return;
        }

        setIsSavingClub(true);
        setClubErrors([]);

        try {
            const payload = new FormData();
            payload.append('nome', clubName.trim());
            payload.append('liga_id', liga.id);

            const { data } = await window.axios.post('/minha_liga/clubes', payload);

            if (data?.redirect) {
                window.location.href = data.redirect;
                return;
            }

            setClubFeedback(data?.message ?? 'Clube salvo com sucesso.');
            setClubModalOpen(false);
            clearClubForm();
        } catch (error) {
            const message =
                error.response?.data?.message ??
                'Não foi possível salvar o clube. Tente novamente.';
            setClubErrors([message]);
        } finally {
            setIsSavingClub(false);
        }
    };

    return (
        <main className="mco-screen" aria-label="Minha liga">
            <section className="league-header">
                <div className="league-logo">
                    {liga.imagem ? (
                        <img src={`/storage/${liga.imagem}`} alt={`Escudo da ${liga.nome}`} />
                    ) : (
                        <span className="league-logo-initials">{getLeagueInitials(liga.nome)}</span>
                    )}
                </div>
                <p className="league-title">{liga.nome}</p>
                <div className="league-meta">
                    <div>
                        <span>Nível da liga</span>
                        <strong>{TYPE_LABELS[liga.tipo] ? liga.tipo.toUpperCase() : 'INDIVIDUAL'}</strong>
                    </div>
                    <div>
                        <span>Plataforma & Geração</span>
                        <strong>
                            {[liga.plataforma, liga.geracao].filter(Boolean).join(' · ') || 'Não informados'}
                        </strong>
                    </div>
                    <div>
                        <span>Jogo</span>
                        <strong>{liga.jogo || 'Não informado'}</strong>
                    </div>
                </div>
            </section>
            <section className="league-actions">
                <a className="btn-primary text-center" href={mercadoHref}>
                    Mercado
                </a>
                <a className="btn-primary text-center" href={financeiroHref}>
                    Financeiro
                </a>
                <a className="btn-primary text-center" href={meuElencoHref}>
                    Meu elenco
                </a>
                <button
                    type="button"
                className="btn-primary"
                onClick={() => {
                    setClubModalOpen(true);
                    setClubFeedback('');
                    setClubName(resolveExistingClubName());
                }}
            >
                    Meu clube
                </button>
                <p className="league-actions-copy">
                    Acesse o mercado para buscar, filtrar e contratar jogadores disponíveis nesta liga.
                </p>
            </section>
            {clubFeedback && <p className="league-actions-copy success">{clubFeedback}</p>}
            {isClubModalOpen && (
                <div className="club-modal-overlay" role="presentation" onClick={() => setClubModalOpen(false)}>
                    <div
                        className="club-modal"
                        role="dialog"
                        aria-modal="true"
                        aria-label="Cadastrar clube"
                        onClick={(event) => event.stopPropagation()}
                    >
                        <h3>Criar clube</h3>
                        <p>Informe apenas um nome para o seu clube.</p>
                        <form onSubmit={handleClubSubmit} className="club-form">
                            <label htmlFor="club-name">Nome do clube</label>
                            <input
                                id="club-name"
                                type="text"
                                value={clubName}
                                onChange={(event) => setClubName(event.target.value)}
                                placeholder="Ex.: Furia FC"
                            />
                            {clubErrors.length > 0 && (
                                <ul className="club-errors">
                                    {clubErrors.map((error) => (
                                        <li key={error}>{error}</li>
                                    ))}
                                </ul>
                            )}
                            <div className="club-actions">
                                <button className="btn-outline" type="button" onClick={() => setClubModalOpen(false)}>
                                    Cancelar
                                </button>
                                <button className="btn-primary" type="submit" disabled={isSavingClub}>
                                    {isSavingClub ? 'Salvando...' : 'Salvar clube'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
            <Navbar active="ligas" />
        </main>
    );
}
