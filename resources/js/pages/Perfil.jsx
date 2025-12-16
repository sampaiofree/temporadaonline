import Navbar from '../components/app_publico/Navbar';
import DashboardButton from '../components/app_publico/DashboardButton';
import backgroundDefault from '../../../storage/app/public/app/background/fundopadrao.webp';
import backgroundVertical from '../../../storage/app/public/app/background/fundo_vertical.webp';

const PROFILE_DATA = {
    name: 'Miguel Santana',
    nickname: 'MC_PRO',
    email: 'miguel.santana@mco.gg',
    platform: 'PlayStation 5 · Geração 4',
    game: 'MCO FIFA 17',
};

const PROFILE_FIELDS = [
    { label: 'Nome', value: PROFILE_DATA.name },
    { label: 'Nickname', value: PROFILE_DATA.nickname },
    { label: 'Email', value: PROFILE_DATA.email },
    { label: 'Plataforma & Geração', value: PROFILE_DATA.platform },
    { label: 'Jogo', value: PROFILE_DATA.game },
];

const EDIT_ICON_PATHS = [
    'M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z',
    'M20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0L13.27 7.35l3.75 3.75 3.69-3.69z',
];

const getInitials = (name) => {
    if (!name) return '';
    const parts = name.split(' ').filter(Boolean);
    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }
    return `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase();
};

export default function Perfil() {
    const initials = getInitials(PROFILE_DATA.name);
    const backgroundStyles = {
        '--mco-cover': `url(${backgroundDefault})`,
        '--mco-cover-mobile': `url(${backgroundVertical})`,
    };

    return (
        <main className="mco-screen" style={backgroundStyles} aria-label="Perfil do jogador">
            <section className="profile-panel" aria-label="Dados do jogador">
                <div className="profile-avatar" aria-hidden="true">
                    <span>{initials}</span>
                </div>
                <p className="profile-name">{PROFILE_DATA.name}</p>
                <p className="profile-nickname">#{PROFILE_DATA.nickname}</p>
                <div className="profile-details">
                    {PROFILE_FIELDS.map((field) => (
                        <article key={field.label} className="profile-field">
                            <span className="profile-label">{field.label}</span>
                            <span className="profile-value">{field.value}</span>
                        </article>
                    ))}
                </div>
                <div className="profile-footer">
                    <DashboardButton label="Editar Perfil" paths={EDIT_ICON_PATHS} />
                </div>
            </section>
            <Navbar active="home" />
        </main>
    );
}
