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

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0,
});

const formatCurrency = (value) => {
    if (value === null || value === undefined) return '—';
    return currencyFormatter.format(value);
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
const getClubeFromWindow = () => window.__CLUBE__ ?? null;
const getEscudosFromWindow = () => (Array.isArray(window.__ESCUDOS__) ? window.__ESCUDOS__ : []);
const getUsedEscudosFromWindow = () => (Array.isArray(window.__USED_ESCUDOS__) ? window.__USED_ESCUDOS__ : []);

export default function MinhaLiga() {
    const liga = getLigaFromWindow();
    const clube = getClubeFromWindow();
    const appContext = getAppContext();
    const [clubSnapshot, setClubSnapshot] = useState(clube);
    const clubEscudoImage = clubSnapshot?.escudo_id && clubSnapshot?.escudo_url
        ? clubSnapshot.escudo_url
        : null;

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
    const meuClubeHref = `/minha_liga/clube?liga_id=${liga.id}`;
    const periodos = Array.isArray(liga?.periodos) ? liga.periodos : [];
    const activePeriod = liga?.periodo_atual ?? null;
    const isActivePeriod = (period) =>
        Boolean(
            activePeriod
            && period.inicio === activePeriod.inicio
            && period.fim === activePeriod.fim
        );

    const parsePeriodDate = (value) => {
        if (!value) return null;
        return new Date(`${value}T00:00:00`);
    };

    const calculatePeriodProgress = (period) => {
        const start = parsePeriodDate(period?.inicio);
        const end = parsePeriodDate(period?.fim);
        if (!start || !end || start >= end) {
            return 0;
        }

        const now = new Date();
        if (now <= start) return 0;
        if (now >= end) return 100;

        const elapsed = now.getTime() - start.getTime();
        const duration = end.getTime() - start.getTime();
        return Math.round((elapsed / duration) * 100);
    };

    const sectionStatusLabel = activePeriod ? 'Período atual' : 'Aguardando Início';

    return (
        <main className="mco-screen minha-liga-screen" aria-label="Minha liga">
            <section className="league-header expanded league-header-custom">
                <div className="league-header-top custom">
                    <div className="league-logo compact custom">
                        {liga.imagem ? (
                            <img src={`/storage/${liga.imagem}`} alt={`Escudo da ${liga.nome}`} />
                        ) : (
                            <span className="league-logo-initials">{getLeagueInitials(liga.nome)}</span>
                        )}
                    </div>

                    <div className="league-title-group">
                        <p className="league-title highlight custom">{liga.nome}</p>
                        <div className="league-active-status">
                            <span className="league-status-dot" />
                            <span className="league-active-label">
                                Liga ativa · <span>Partidas agora</span>
                            </span>
                        </div>
                    </div>
                </div>
                <div className="league-meta compact league-meta-custom">
                    <div>
                        <span>Plataforma</span>
                        <strong>{liga.plataforma || '—'}</strong>
                    </div>
                    <div>
                        <span>Geração</span>
                        <strong>{liga.geracao || '—'}</strong>
                    </div>
                    <div className="league-meta-highlight">
                        <span>Jogo</span>
                        <strong>{liga.jogo || '—'}</strong>
                    </div>
                </div>
            </section>
            {clubSnapshot && (
                <section className="club-summary gold-card club-summary-custom">
                    <article className="club-summary-card-inner">
                        <div className="club-summary-banner">
                            <div className="club-summary-badge">
                                {clubEscudoImage ? (
                                    <img src={clubEscudoImage} alt={`Escudo do ${clubSnapshot.nome}`} />
                                ) : (
                                    <span>{getLeagueInitials(clubSnapshot.nome)}</span>
                                )}
                            </div>
                            <div className="club-summary-info">
                                <p className="club-summary-title">{clubSnapshot.nome}</p>
                                <div className="club-summary-status">
                                    <span className="club-summary-dot" />
                                    <span>Manager ativo · nível 1</span>
                                </div>
                            </div>
                        </div>
                        <div className="club-summary-stats club-summary-stats-custom">
                            <div>
                                <span>Elenco</span>
                                <strong>{clubSnapshot.elenco_count ?? 0} jogadores</strong>
                            </div>
                            <div>
                                <span>Saldo</span>
                                <strong>{formatCurrency(clubSnapshot.saldo)}</strong>
                            </div>
                        </div>
                    </article>
                </section>
            )}
            <section className="league-actions grid league-actions-custom">
                <a className="control-card" href={mercadoHref}>
                    <span className="control-card-title">Mercado</span>
                    <span className="control-card-ghost">MKT</span>
                    <span className="control-card-arrow">→</span>
                </a>
                <a className="control-card" href={financeiroHref}>
                    <span className="control-card-title">Financeiro</span>
                    <span className="control-card-ghost">FIN</span>
                    <span className="control-card-arrow">→</span>
                </a>
                <a className="control-card" href={meuElencoHref}>
                    <span className="control-card-title">Meu elenco</span>
                    <span className="control-card-ghost">ELENCO</span>
                    <span className="control-card-arrow">→</span>
                </a>
                <a className="control-card primary-control" href={meuClubeHref}>
                    <span className="control-card-subtitle">Centro de comando</span>
                    <span className="control-card-title primary">Meu clube</span>
                </a>
            </section>
            <section className="league-periods">
                <div className="league-periods-header">
                    <h3 className="league-periods-title">🕒 Cronograma de rodadas</h3>
                    <span className="league-periods-status">{sectionStatusLabel}</span>
                </div>

                <div className="league-periods-list">
                    {periodos.length > 0 ? (
                        periodos.map((periodo, index) => {
                            const progress = calculatePeriodProgress(periodo);
                            return (
                                <article
                                    key={periodo.codigo ?? index}
                                    className={`league-period-card${isActivePeriod(periodo) ? ' is-active' : ''}`}
                                >
                                    <div className="league-period-card-side">
                                        <span className="league-period-card-rodada">
                                            Rodada {index + 1}
                                        </span>
                                        <span className="league-period-card-status">
                                            {isActivePeriod(periodo) ? 'Em andamento' : 'Planejada'}
                                        </span>
                                    </div>
                                    <div className="league-period-card-body">
                                        <div className="league-period-card-row">
                                            <div>
                                                <span>Início</span>
                                                <strong>{periodo.inicio_label ?? periodo.inicio ?? '—'}</strong>
                                            </div>
                                            <div className="league-period-card-arrow">➔</div>
                                            <div>
                                                <span>Término</span>
                                                <strong>{periodo.fim_label ?? periodo.fim ?? '—'}</strong>
                                            </div>
                                        </div>
                                        <div className="league-period-card-progress">
                                            <div style={{ width: `${progress}%` }} />
                                        </div>
                                    </div>
                                </article>
                            );
                        })
                    ) : (
                        <p className="league-period-empty">
                            Ainda não existem períodos cadastrados para esta liga.
                        </p>
                    )}
                </div>

                <div className="league-periods-note">
                    <p>
                        As partidas realizadas fora deste intervalo <strong>não serão
                        contabilizadas</strong> no sistema da liga.
                    </p>
                </div>
            </section>
            <Navbar active="ligas" />
        </main>
    );
}
