import Navbar from '../components/app_publico/Navbar';
import backgroundDefault from '../../../storage/app/public/app/background/fundopadrao.webp';
import backgroundVertical from '../../../storage/app/public/app/background/fundo_vertical.webp';

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0,
});

const formatCurrency = (value) => {
    if (value === null || typeof value === 'undefined') {
        return '—';
    }

    return currencyFormatter.format(value);
};

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getClubeFromWindow = () => window.__CLUBE__ ?? null;
const getFinanceiroFromWindow = () =>
    window.__FINANCEIRO__ ?? { saldo: null, salarioPorRodada: 0, rodadasRestantes: null, movimentos: [] };

const TYPE_LABELS = {
    jogador_livre: 'Jogador livre',
    venda: 'Venda',
    multa: 'Multa',
    troca: 'Troca',
    compra: 'Compra',
};

export default function FinanceiroClube() {
    const liga = getLigaFromWindow();
    const clube = getClubeFromWindow();
    const financeiro = getFinanceiroFromWindow();
    const movimentos = Array.isArray(financeiro.movimentos) ? financeiro.movimentos : [];

    const backgroundStyles = {
        '--mco-cover': `url(${backgroundDefault})`,
        '--mco-cover-mobile': `url(${backgroundVertical})`,
    };

    if (!liga) {
        return (
            <main className="mco-screen" style={backgroundStyles} aria-label="Financeiro do clube">
                <p className="ligas-empty">Liga indisponível. Volte para a lista e tente novamente.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    const minhaLigaHref = `/minha_liga?liga_id=${liga.id}`;
    const saldo = financeiro.saldo;
    const salario = financeiro.salarioPorRodada ?? 0;
    const rodadasRestantes = financeiro.rodadasRestantes;

    const situationText = (() => {
        if (rodadasRestantes === null) {
            return 'Sem custos fixos por rodada.';
        }

        if (rodadasRestantes < 0) {
            return 'Você já está no negativo. Compras e multas ficam bloqueadas até regularizar.';
        }

        return `Você aguenta ${rodadasRestantes} rodadas no ritmo atual.`;
    })();

    return (
        <main className="mco-screen" style={backgroundStyles} aria-label="Financeiro do clube">
            <section className="league-header" aria-label="Resumo da liga e clube">
                <p className="league-title">Financeiro</p>
                <div className="league-meta">
                    <div>
                        <span>Liga</span>
                        <strong>{liga.nome}</strong>
                    </div>
                    <div>
                        <span>Clube</span>
                        <strong>{clube?.nome ?? 'Ainda não criado'}</strong>
                    </div>
                    <div>
                        <span>Jogo</span>
                        <strong>{liga.jogo || 'Não informado'}</strong>
                    </div>
                </div>
            </section>

            {!clube ? (
                <section className="league-actions" aria-label="Aviso de clube não criado">
                    <p className="ligas-empty">
                        Você ainda não criou um clube nesta liga. Registre um clube antes de acompanhar o financeiro.
                    </p>
                    <a className="btn-primary" href={minhaLigaHref}>
                        Criar meu clube
                    </a>
                </section>
            ) : (
                <>
                    <section className="league-menu" aria-label="Indicadores financeiros">
                        <article className="card card-gold">
                            <p className="card-title">Saldo atual</p>
                            <p className="wallet-balance">{formatCurrency(saldo)}</p>
                            <p className="card-meta">Dinheiro disponível para mercado e salários.</p>
                        </article>
                        <article className="card card-gold">
                            <p className="card-title">Salário / rodada</p>
                            <p className="wallet-balance">{formatCurrency(salario)}</p>
                            <p className="card-meta">Soma dos salários (ativos) do seu elenco.</p>
                        </article>
                        <article className="card card-gold">
                            <p className="card-title">Fôlego</p>
                            <p className="wallet-balance">{rodadasRestantes === null ? '—' : rodadasRestantes}</p>
                            <p className="card-meta">{situationText}</p>
                        </article>
                    </section>

                    <section className="wallet-card" aria-label="Últimos movimentos">
                        <h2>Últimos movimentos</h2>
                        {movimentos.length === 0 ? (
                            <p className="card-meta">Nenhum movimento registrado ainda.</p>
                        ) : (
                            <div className="profile-details" aria-label="Lista de movimentos">
                                {movimentos.map((movimento) => (
                                    <article key={movimento.id} className="profile-field">
                                        <span className="profile-label">
                                            {TYPE_LABELS[movimento.tipo] ?? movimento.tipo}
                                        </span>
                                        <span className="profile-value">
                                            {formatCurrency(movimento.valor)}
                                        </span>
                                        {movimento.observacao && (
                                            <span className="card-meta">{movimento.observacao}</span>
                                        )}
                                    </article>
                                ))}
                            </div>
                        )}
                        <div className="profile-footer">
                            <a className="btn-outline" href={minhaLigaHref}>
                                Voltar para Minha Liga
                            </a>
                        </div>
                    </section>
                </>
            )}

            <Navbar active="ligas" />
        </main>
    );
}

