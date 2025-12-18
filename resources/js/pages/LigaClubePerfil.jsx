import Navbar from '../components/app_publico/Navbar';
import backgroundDefault from '../../../storage/app/public/app/background/fundopadrao.webp';
import backgroundVertical from '../../../storage/app/public/app/background/fundo_vertical.webp';

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getClubePerfilFromWindow = () => window.__CLUBE_PERFIL__ ?? null;

export default function LigaClubePerfil() {
    const liga = getLigaFromWindow();
    const clube = getClubePerfilFromWindow();
    const backgroundStyles = {
        '--mco-cover': `url(${backgroundDefault})`,
        '--mco-cover-mobile': `url(${backgroundVertical})`,
    };

    if (!liga || !clube) {
        return (
            <main className="liga-clube-perfil-screen" style={backgroundStyles}>
                <p className="ligas-empty">Clube ou liga indisponível.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    return (
        <main className="liga-clube-perfil-screen" style={backgroundStyles}>
            <section className="liga-dashboard-hero">
                <p className="ligas-eyebrow">CLUBE</p>
                <h1 className="ligas-title">{clube.nome}</h1>
                <p className="ligas-subtitle">{`Dono: ${clube.dono ?? 'Sem dono definido'}`}</p>
            </section>

            <section className="clube-elenco-list">
                {clube.players?.length ? (
                    clube.players.map((player) => (
                        <article key={player.id} className="elenco-card">
                            <div className="elenco-card-body">
                                <p className="elenco-card-title">{player.short_name ?? 'Sem nome'}</p>
                                <span className="elenco-card-meta">
                                    {player.player_positions ?? 'Posição desconhecida'} · OVR {player.overall ?? '—'}
                                </span>
                            </div>
                        </article>
                    ))
                ) : (
                    <p className="ligas-empty">Nenhum jogador listado para este clube.</p>
                )}
            </section>

            <Navbar active="ligas" />
        </main>
    );
}
