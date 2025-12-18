import Navbar from '../components/app_publico/Navbar';
import backgroundDefault from '../../../storage/app/public/app/background/fundopadrao.jpgp';
import backgroundVertical from '../../../storage/app/public/app/background/fundopadrao.jpgp';

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getClassificacaoFromWindow = () => window.__CLASSIFICACAO__ ?? [];

export default function LigaClassificacao() {
    const liga = getLigaFromWindow();
    const classificacao = getClassificacaoFromWindow();
    const backgroundStyles = {
        '--mco-cover': `url(${backgroundDefault})`,
        '--mco-cover-mobile': `url(${backgroundVertical})`,
    };

    if (!liga) {
        return (
            <main className="liga-classificacao-screen" style={backgroundStyles}>
                <p className="ligas-empty">Liga indisponível. Volte para o painel.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    return (
        <main className="liga-classificacao-screen" style={backgroundStyles}>
            <section className="liga-dashboard-hero">
                <p className="ligas-eyebrow">CLASSIFICAÇÃO</p>
                <h1 className="ligas-title">Ranking da liga</h1>
                <p className="ligas-subtitle">Ordenação provisória baseada no nome dos clubes.</p>
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
                            <span>{item.clube_nome}</span>
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
