import Navbar from '../components/app_publico/Navbar';
import DashboardButton from '../components/app_publico/DashboardButton';
import backgroundDefault from '../../../storage/app/public/app/background/fundopadrao.jpg';
import backgroundVertical from '../../../storage/app/public/app/background/fundopadrao.jpg';

const BUTTONS = [
    {
        label: 'Perfil',
        paths: [
            'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2',
            'M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
        ],
        href: '/perfil',
    },
    {
        label: 'Minhas Ligas',
        paths: [
            'M6 9H4.5a2.5 2.5 0 0 1 0-5H6',
            'M18 9h1.5a2.5 2.5 0 0 0 0-5H18',
            'M4 22h16',
            'M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22',
            'M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22',
            'M18 2H6v7a6 6 0 0 0 12 0V2Z',
        ],
    },
    {
        label: 'Partidas',
        paths: [
            'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z',
            'm6.7 6.7 10.6 10.6',
            'm6.7 17.3 10.6-10.6',
        ],
    },
];

export default function Dashboard() {
    const backgroundStyles = {
        '--mco-cover': `url(${backgroundDefault})`,
        '--mco-cover-mobile': `url(${backgroundVertical})`,
    };

    return (
        <main className="mco-screen" style={backgroundStyles} aria-label="Tela inicial do MCO">
            <section className="dashboard-actions" aria-label="Ações rápidas">
                {BUTTONS.map((button) => (
                    <DashboardButton
                        key={button.label}
                        label={button.label}
                        paths={button.paths}
                        href={button.href}
                    />
                ))}
            </section>
            <Navbar active="home" />
        </main>
    );
}
