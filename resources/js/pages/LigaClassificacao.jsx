import Navbar from '../components/app_publico/Navbar';

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getClassificacaoFromWindow = () => window.__CLASSIFICACAO__ ?? [];

export default function LigaClassificacao() {
    const liga = getLigaFromWindow();
    const classificacao = getClassificacaoFromWindow();

    const formatOrdinal = (value) => `${value}º`;

    if (!liga) {
        return (
            <main className="liga-classificacao-screen">
                <p className="ligas-empty">Liga indisponível. Volte para o painel.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    return (
        <main className="liga-classificacao-screen">
            <section className="liga-dashboard-hero">
                <p className="ligas-eyebrow">CLASSIFICAÇÃO</p>
                <h1 className="ligas-title">Ranking da liga</h1>
                <p className="ligas-subtitle">Classificação calculada em tempo real a partir dos resultados confirmados.</p>
            </section>

            <section className="liga-classificacao-table">
                <div className="classificacao-row header">
                    <span>Pos</span>
                    <span>Clube</span>
                    <span>V</span>
                    <span>Pontos</span>
                </div>
                {classificacao.length === 0 ? (
                    <p className="ligas-empty">Não há clubes nessa liga ainda.</p>
                ) : (
                    classificacao.map((item) => (
                        <a
                            key={item.clube_id}
                            className={`classificacao-row${item.posicao === 1 ? ' highlight' : ''}`}
                            href={`/liga/clubes/${item.clube_id}`}
                        >
                            <span className="classificacao-pos">
                                {formatOrdinal(item.posicao)}
                                {item.clube_escudo_url && (
                                    <img
                                        className="classificacao-shield"
                                        src={`/storage/${item.clube_escudo_url}`}
                                        alt={item.clube_nome}
                                        aria-hidden="true"
                                    />
                                )}
                            </span>
                            <span className="classificacao-clube">
                                <strong>{item.clube_nome}</strong>
                                <small>
                                    {item.empates}E · {item.derrotas}D · SG: {item.saldo_gols || 0} · GM: {item.gols_marcados}
                                </small>
                            </span>
                            <span className="classificacao-vitorias">{item.vitorias}</span>
                            <span className="classificacao-pontos">{item.pontos}</span>
                        </a>
                    ))
                )}
                <div className="classificacao-note">
                    <p>
                        As partidas realizadas fora deste intervalo não são contabilizadas no ranking oficial da liga.
                    </p>
                </div>
            </section>

            <Navbar active="ligas" />
        </main>
    );
}
