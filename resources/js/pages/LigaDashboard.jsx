import Navbar from '../components/app_publico/Navbar';
import backgroundDefault from '../../../storage/app/public/app/background/fundopadrao.jpgp';
import backgroundVertical from '../../../storage/app/public/app/background/fundopadrao.jpgp';

const formatDate = (value) => {
    if (!value) {
        return 'Sem data';
    }

    return new Date(value).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getClubeFromWindow = () => window.__CLUBE__ ?? null;
const getDashboardFromWindow = () =>
    window.__DASHBOARD__ ?? {
        hasClub: false,
        nextMatch: null,
        classification: { position: null, total: null, points: null },
        actions: [],
    };

export default function LigaDashboard() {
    const liga = getLigaFromWindow();
    const clube = getClubeFromWindow();
    const dashboard = getDashboardFromWindow();
    const backgroundStyles = {
        '--mco-cover': `url(${backgroundDefault})`,
        '--mco-cover-mobile': `url(${backgroundVertical})`,
    };

    if (!liga) {
        return (
            <main className="liga-dashboard-screen" style={backgroundStyles}>
                <p className="ligas-empty">Liga indisponível. Volte para o painel principal.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    const heroSubtitle = dashboard.hasClub
        ? `Clube registrado: ${clube?.nome ?? 'Sem nome'}`
        : 'Crie seu clube para liberar todas as funcionalidades.';

    return (
        <main className="liga-dashboard-screen" style={backgroundStyles}>
            <section className="liga-dashboard-hero">
                <p className="ligas-eyebrow">LIGA</p>
                <h1 className="ligas-title">{liga.nome}</h1>
                <p className="ligas-subtitle">{liga.descricao ?? 'Bem-vindo à liga!'}</p>
                <p className="liga-dashboard-subtitle">{heroSubtitle}</p>
                <div className="liga-dashboard-stats">
                    <div>
                        <span>Jogo</span>
                        <strong>{liga.jogo || 'Não informado'}</strong>
                    </div>
                    <div>
                        <span>Tipo</span>
                        <strong>{liga.tipo?.toUpperCase() ?? 'PUBLICA'}</strong>
                    </div>
                </div>
            </section>

            <section className="liga-dashboard-grid">
                <article className="dashboard-card">
                    <h2>Próxima partida</h2>
                    {dashboard.hasClub && dashboard.nextMatch ? (
                        <>
                            <p className="card-title">Rodada {dashboard.nextMatch.round}</p>
                            <p className="card-value">{dashboard.nextMatch.opponent}</p>
                            <p className="card-meta">{dashboard.nextMatch.status}</p>
                            <p className="card-meta">{formatDate(dashboard.nextMatch.date)}</p>
                        </>
                    ) : (
                        <p className="card-meta">Sem partidas agendadas.</p>
                    )}
                </article>

                <article className="dashboard-card">
                    <h2>Classificação</h2>
                    {dashboard.hasClub && dashboard.classification.position !== null ? (
                        <>
                            <p className="card-title">
                                #{dashboard.classification.position} de {dashboard.classification.total}
                            </p>
                            <p className="card-value">{dashboard.classification.points} pts</p>
                            <p className="card-meta">Vantagem atual sobre a média</p>
                        </>
                    ) : (
                        <p className="card-meta">Classificação disponível após criar o clube.</p>
                    )}
                </article>
            </section>

            {!dashboard.hasClub && (
                <section className="dashboard-warning">
                    <p>Você precisa criar um clube antes de acessar as demais telas.</p>
                    <a className="btn-primary" href={`/minha_liga?liga_id=${liga.id}`}>
                        Criar clube agora
                    </a>
                </section>
            )}

            <section className="dashboard-actions" aria-label="Navegação rápida">
                {dashboard.actions.map((action) => {
                    const isComingSoon = !dashboard.hasClub || !action.href;
                    return (
                        <article key={action.label} className="dashboard-action-card">
                            <div>
                                <strong>{action.label}</strong>
                                <p>{action.description}</p>
                            </div>
                            <a
                                className={`btn-primary${isComingSoon ? ' disabled' : ''}`}
                                href={isComingSoon ? '#' : action.href}
                                aria-disabled={isComingSoon}
                            >
                                {isComingSoon ? 'Em breve' : 'Abrir'}
                            </a>
                        </article>
                    );
                })}
            </section>

            <Navbar active="ligas" />
        </main>
    );
}
