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

const TYPE_ICONS = {
    venda: '⬆',
    compra: '⬇',
    multa: '⚠',
    troca: '🔁',
    jogador_livre: '➕',
};

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

    const saldo = financeiro.saldo;
    const salario = financeiro.salarioPorRodada ?? 0;
    const rodadas = financeiro.rodadasRestantes;

    // Lógica de texto de fôlego do desenvolvedor
    const folegoText =
        rodadas === null
            ? 'Sem gasto fixo'
            : rodadas < 0
            ? 'Saldo Negativo'
            : `${rodadas} rodadas`;

    if (!liga) {
        return (
            <main className="mco-screen">
                <p className="ligas-empty">Liga indisponível. Volte e tente novamente.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    const minhaLigaHref = `/minha_liga?liga_id=${liga.id}`;

    return (
        <main className="mco-screen" aria-label="Financeiro do clube">
            <section className="league-header">
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
                </div>
            </section>

            {!clube ? (
                <section className="league-actions">
                    <p className="ligas-empty">
                        Você ainda não criou um clube nesta liga.
                    </p>
                    <a className="btn-primary" href={minhaLigaHref}>
                        Criar meu clube
                    </a>
                </section>
            ) : (
                <>
                    {/* Seção de Cards - Usando suas classes originais */}
                    <section className="league-menu">
                        <article className="card card-gold">
                            <p className="card-title">Saldo atual</p>
                            <p className="wallet-balance">{formatCurrency(saldo)}</p>
                            <p className="card-meta">Dinheiro disponível para mercado.</p>
                        </article>

                        <article className="card card-gold">
                            <p className="card-title">Salário / rodada</p>
                            <p className="wallet-balance">{formatCurrency(salario)}</p>
                            <p className="card-meta">Custo fixo do elenco.</p>
                        </article>

                        <article className="card card-gold">
                            <p className="card-title">Fôlego</p>
                            <p className="wallet-balance">{folegoText}</p>
                            {rodadas !== null && rodadas <= 3 && (
                                <p className="card-meta" style={{ color: '#ffcc00' }}>
                                    ⚠ Atenção ao caixa!
                                </p>
                            )}
                        </article>
                    </section>

                    {/* Seção de Histórico - Usando suas classes originais */}
                    <section className="wallet-card">
                        <h2>Últimos movimentos</h2>
                        {movimentos.length === 0 ? (
                            <p className="card-meta">Nenhum movimento registrado ainda.</p>
                        ) : (
                            <div className="profile-details">
                                {movimentos.map((movimento) => (
                                    <article key={movimento.id} className="profile-field">
                                        <span className="profile-label">
                                            {TYPE_ICONS[movimento.tipo] ?? '•'}{' '}
                                            {movimento.observacao || TYPE_LABELS[movimento.tipo] || movimento.tipo}
                                        </span>
                                        <span 
                                            className="profile-value" 
                                            style={{ color: movimento.valor < 0 ? '#ff4d4d' : '#2ecc71' }}
                                        >
                                            {formatCurrency(movimento.valor)}
                                        </span>
                                    </article>
                                ))}
                            </div>
                        )}
                        
                    </section>
                </>
            )}

            <Navbar active="ligas" />
        </main>
    );
}