import Navbar from '../components/app_publico/Navbar';
import DashboardButton from '../components/app_publico/DashboardButton';
import backgroundDefault from '../../../storage/app/public/app/background/fundopadrao.webp';
import backgroundVertical from '../../../storage/app/public/app/background/fundo_vertical.webp';
import leagueShield from '../../images/league-shield.svg';

const LEAGUE_INFO = {
    name: 'Liga Suprema MCO',
    participants: '12 Participantes',
    level: 'Nível S',
    platform: 'PlayStation 5 · Geração 4',
    game: 'MCO FIFA 17',
};

const MENU_ACTIONS = [
    {
        label: 'Meu Time',
        paths: ['M3 13h6l3-5 4 9h4'],
    },
    {
        label: 'Meu Elenco',
        paths: ['M5 8h14l-7 11-7-11z'],
    },
    {
        label: 'Partidas',
        paths: ['M3 6h18'],
    },
    {
        label: 'Chat',
        paths: ['M4 4h16v14H4z'],
    },
    {
        label: 'Resultados',
        paths: ['M6 16h12', 'M6 12h12', 'M6 8h12'],
    },
];

export default function MinhaLiga() {
    const backgroundStyles = {
        '--mco-cover': `url(${backgroundDefault})`,
        '--mco-cover-mobile': `url(${backgroundVertical})`,
    };

    return (
        <main className="mco-screen" style={backgroundStyles} aria-label="Minha liga">
            <section className="league-header">
                <div className="league-logo">
                    <img src={leagueShield} alt={`Escudo da ${LEAGUE_INFO.name}`} />
                </div>
                <p className="league-title">{LEAGUE_INFO.name}</p>
                <div className="league-meta">
                    <div>
                        <span>Número de participantes</span>
                        <strong>{LEAGUE_INFO.participants}</strong>
                    </div>
                    <div>
                        <span>Nível da liga</span>
                        <strong>{LEAGUE_INFO.level}</strong>
                    </div>
                    <div>
                        <span>Plataforma & Geração</span>
                        <strong>{LEAGUE_INFO.platform}</strong>
                    </div>
                    <div>
                        <span>Jogo</span>
                        <strong>{LEAGUE_INFO.game}</strong>
                    </div>
                </div>
            </section>

            <section className="league-menu" aria-label="Ações da liga">
                {MENU_ACTIONS.map((action) => (
                    <DashboardButton key={action.label} label={action.label} paths={action.paths} />
                ))}
            </section>

            <section className="wallet-card" aria-label="Carteira">
                <h2>Carteira</h2>
                <p className="wallet-balance">$15.750,00</p>
            </section>

            <Navbar active="ligas" />
        </main>
    );
}
