import Navbar from '../components/app_publico/Navbar';

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0,
});

const formatCurrency = (value) => {
    if (value === null || typeof value === 'undefined') return '—';
    return currencyFormatter.format(value);
};

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getClubeFromWindow = () => window.__CLUBE__ ?? null;
const getFinanceiroFromWindow = () =>
    window.__FINANCEIRO__ ?? {
        saldo: null,
        salarioPorRodada: 0,
        rodadasRestantes: null,
        movimentos: [],
    };

const TYPE_LABELS = {
    jogador_livre: 'Jogador livre',
    venda: 'Venda',
    multa: 'Multa',
    troca: 'Troca',
    compra: 'Compra',
    salario_partida: 'Salário da partida',
    multa_wo: 'Multa W.O.',
};

export default function FinanceiroClube() {
    const liga = getLigaFromWindow();
    const clube = getClubeFromWindow();
    const financeiro = getFinanceiroFromWindow();
    const movimentos = Array.isArray(financeiro.movimentos) ? financeiro.movimentos : [];
    const patrocinioMovimentos = Array.isArray(financeiro.patrocinios)
        ? financeiro.patrocinios
        : [];

    const saldo = financeiro.saldo;
    const salario = financeiro.salarioPorRodada ?? 0;
    const rodadas = financeiro.rodadasRestantes;

    // Lógica de texto de fôlego do desenvolvedor
    const folegoText =
        rodadas === null
            ? 'Sem gasto fixo'
            : rodadas < 0
            ? 'Saldo Negativo'
            : `${rodadas} partidas`;

    if (!liga) {
        return (
            <main className="mco-screen">
                <p className="ligas-empty">Liga indisponível. Volte e tente novamente.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    const minhaLigaHref = `/minha_liga?liga_id=${liga.id}`;
    const headerSubtitle = clube?.nome ? `${liga.nome} · ${clube.nome}` : `${liga.nome} · Clube não criado`;

    const folegoTone = rodadas === null
        ? 'is-neutral'
        : rodadas < 0
            ? 'is-danger'
            : rodadas <= 3
                ? 'is-warning'
                : 'is-safe';

    const formatShortDate = (value) => {
        if (!value) return null;
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return null;
        return date.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
    };

    const resolveMovementDirection = (movimento) => {
        const clubId = clube?.id ? Number(clube.id) : null;
        if (clubId) {
            if (movimento.clube_origem_id && Number(movimento.clube_origem_id) === clubId) {
                return 'out';
            }
            if (movimento.clube_destino_id && Number(movimento.clube_destino_id) === clubId) {
                return 'in';
            }
        }
        return movimento.valor < 0 ? 'out' : 'in';
    };

    return (
        <main className="mco-screen liga-financeiro-screen" aria-label="Financeiro do clube">
            <section className="liga-dashboard-hero">
                <p className="ligas-eyebrow">FINANCEIRO</p>
                <h1 className="ligas-title">Gestão financeira</h1>
                <p className="ligas-subtitle">{headerSubtitle}</p>
            </section>

            {!clube ? (
                <section className="financeiro-empty">
                    <p className="ligas-empty">Você ainda não criou um clube nesta liga.</p>
                    <a className="btn-primary" href={minhaLigaHref}>
                        Criar meu clube
                    </a>
                </section>
            ) : (
                <>
                    <section className="financeiro-overview">
                        <article className={`financeiro-balance-card${saldo < 0 ? ' is-negative' : ''}`}>
                            <div className="financeiro-balance-inner">
                                <p className="financeiro-card-label">Saldo atual disponível</p>
                                <p className="financeiro-card-value">{formatCurrency(saldo)}</p>
                                <p className="financeiro-card-meta">
                                    Poder de compra total para contratações.
                                </p>
                            </div>
                        </article>

                        <div className="financeiro-stats-grid">
                            <article className="financeiro-stat-card is-negative">
                                <p className="financeiro-stat-label">Salário / partida</p>
                                <p className="financeiro-stat-value">
                                    {salario > 0 ? '-' : ''}
                                    {formatCurrency(salario)}
                                </p>
                                <p className="financeiro-stat-meta">Custo fixo do elenco.</p>
                            </article>

                            <article className={`financeiro-stat-card ${folegoTone}`}>
                                <p className="financeiro-stat-label">Fôlego de caixa</p>
                                <p className="financeiro-stat-value">{folegoText}</p>
                                {rodadas !== null && rodadas <= 3 && (
                                    <p className="financeiro-stat-alert">Atenção ao caixa.</p>
                                )}
                            </article>
                        </div>
                    </section>

                    {patrocinioMovimentos.length > 0 && (
                        <section className="wallet-card patrocinio-card">
                            <div className="financeiro-movimentos-header">
                                <h2>Patrocínios resgatados</h2>
                                <span className="financeiro-movimentos-count">
                                    {patrocinioMovimentos.length}
                                </span>
                            </div>
                            <div className="financeiro-movimento-list">
                                {patrocinioMovimentos.map((patrocinio) => {
                                    const dateLabel = formatShortDate(patrocinio.created_at);

                                    return (
                                        <article
                                            key={patrocinio.id}
                                            className="financeiro-movimento is-in"
                                        >
                                            <div className="financeiro-movimento-icon">
                                                ★
                                            </div>
                                            <div className="financeiro-movimento-body">
                                                <span className="financeiro-movimento-title">
                                                    {patrocinio.observacao}
                                                </span>
                                                <span className="financeiro-movimento-subtitle">
                                                    {dateLabel}
                                                </span>
                                            </div>
                                            <span className="financeiro-movimento-amount is-in">
                                                {formatCurrency(Math.abs(patrocinio.valor))}
                                            </span>
                                        </article>
                                    );
                                })}
                            </div>
                        </section>
                    )}

                    <section className="wallet-card financeiro-movimentos">
                        <div className="financeiro-movimentos-header">
                            <h2>Últimos movimentos</h2>
                            <span className="financeiro-movimentos-count">
                                {movimentos.length}
                            </span>
                        </div>
                        {movimentos.length === 0 ? (
                            <p className="card-meta">Nenhum movimento registrado ainda.</p>
                        ) : (
                            <div className="financeiro-movimento-list">
                                {movimentos.map((movimento) => {
                                    const direction = resolveMovementDirection(movimento);
                                    const amountValue = direction === 'out'
                                        ? -Math.abs(movimento.valor ?? 0)
                                        : Math.abs(movimento.valor ?? 0);
                                    const title =
                                        movimento.jogador_nome
                                        || movimento.observacao
                                        || TYPE_LABELS[movimento.tipo]
                                        || movimento.tipo;
                                    const dateLabel = formatShortDate(movimento.created_at);
                                    const subtitleParts = [
                                        TYPE_LABELS[movimento.tipo] || movimento.tipo,
                                        dateLabel,
                                    ].filter(Boolean);
                                    const subtitle = subtitleParts.join(' · ');

                                    return (
                                        <article
                                            key={movimento.id}
                                            className={`financeiro-movimento is-${direction}`}
                                        >
                                            <div className="financeiro-movimento-icon">
                                                {direction === 'out' ? '↓' : '↑'}
                                            </div>
                                            <div className="financeiro-movimento-body">
                                                <span className="financeiro-movimento-title">{title}</span>
                                                <span className="financeiro-movimento-subtitle">{subtitle}</span>
                                            </div>
                                            <span className={`financeiro-movimento-amount is-${direction}`}>
                                                {formatCurrency(amountValue)}
                                            </span>
                                        </article>
                                    );
                                })}
                            </div>
                        )}
                    </section>
                </>
            )}

            <Navbar active="ligas" />
        </main>
    );
}
