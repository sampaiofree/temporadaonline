import Navbar from '../components/app_publico/Navbar';

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getClassificacaoFromWindow = () => window.__CLASSIFICACAO__ ?? [];

export default function LigaClassificacao() {
    const liga = getLigaFromWindow();
    const classificacao = getClassificacaoFromWindow();

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
                    <span>Posição</span>
                    <span>Clube</span>
                    <span>Pontos</span>
                    <span>Vitórias</span>
                </div>
                {classificacao.length === 0 ? (
                    <p className="ligas-empty">Não há clubes nessa liga ainda.</p>
                ) : (
                    classificacao.map((item) => (
                        <a
                            key={item.clube_id}
                            className="classificacao-row"
                            href={`/liga/clubes/${item.clube_id}`}
                        >
                            <span>#{item.posicao}</span>
                            <span className="classificacao-clube">
                                <strong>{item.clube_nome}</strong>
                                <small>
                                    {item.empates} empates · {item.derrotas} derrotas ·
                                    GM {item.gols_marcados} · GS {item.gols_sofridos} ·
                                    saldo {item.saldo_gols}
                                </small>
                            </span>
                            <span>{item.pontos}</span>
                            <span>{item.vitorias}</span>
                        </a>
                    ))
                )}
            </section>

            <Navbar active="ligas" />
        </main>
    );
}
