import { useState } from 'react';
import Navbar from '../components/app_publico/Navbar';
import backgroundDefault from '../../../storage/app/public/app/background/fundopadrao.jpgp';
import backgroundVertical from '../../../storage/app/public/app/background/fundopadrao.jpgp';

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getClubeFromWindow = () => window.__CLUBE__ ?? null;
const getPartidasFromWindow = () =>
    window.__PARTIDAS__ ?? { minhas_partidas: [], todas_partidas: [] };

export default function LigaPartidas() {
    const liga = getLigaFromWindow();
    const clube = getClubeFromWindow();
    const partidas = getPartidasFromWindow();
    const [activeTab, setActiveTab] = useState('minhas');

    const backgroundStyles = {
        '--mco-cover': `url(${backgroundDefault})`,
        '--mco-cover-mobile': `url(${backgroundVertical})`,
    };

    if (!liga) {
        return (
            <main className="liga-partidas-screen" style={backgroundStyles}>
                <p className="ligas-empty">Liga indisponível. Volte para o painel.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    const tabContent = activeTab === 'minhas' ? partidas.minhas_partidas : partidas.todas_partidas;

    return (
        <main className="liga-partidas-screen" style={backgroundStyles}>
            <section className="liga-dashboard-hero">
                <p className="ligas-eyebrow">PARTIDAS</p>
                <h1 className="ligas-title">Agenda da liga</h1>
                <p className="ligas-subtitle">
                    {clube
                        ? `Você joga como ${clube.nome}.`
                        : 'Crie um clube para começar a ver as partidas.'}
                </p>
            </section>

            <section className="liga-partidas-tabs">
                <button
                    type="button"
                    className={`filter-pill${activeTab === 'minhas' ? ' active' : ''}`}
                    onClick={() => setActiveTab('minhas')}
                >
                    Minhas partidas
                </button>
                <button
                    type="button"
                    className={`filter-pill${activeTab === 'todas' ? ' active' : ''}`}
                    onClick={() => setActiveTab('todas')}
                >
                    Todas as partidas
                </button>
            </section>

            <section className="liga-partidas-list">
                {tabContent.length === 0 ? (
                    <p className="ligas-empty">Nenhuma partida disponível no momento.</p>
                ) : (
                    tabContent.map((partida) => (
                        <article key={partida.id}>
                            <p>{partida.title ?? 'Partida sem título'}</p>
                        </article>
                    ))
                )}
            </section>

            <Navbar active="ligas" />
        </main>
    );
}
