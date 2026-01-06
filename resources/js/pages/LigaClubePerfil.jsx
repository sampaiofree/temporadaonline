import { useState } from 'react';
import Navbar from '../components/app_publico/Navbar';

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getClubePerfilFromWindow = () => window.__CLUBE_PERFIL__ ?? null;

const proxyFaceUrl = (url) => {
    if (!url) return null;
    const trimmed = url.replace(/^https?:\/\//, '');
    return `https://images.weserv.nl/?url=${encodeURIComponent(trimmed)}&w=80&h=80`;
};

const resolveOvrTone = (overall) => {
    const ovr = Number(overall ?? 0);
    if (ovr >= 80) return 'high';
    if (ovr >= 60) return 'mid';
    return 'low';
};

const getInitials = (name) => {
    if (!name) return '?';
    const parts = name.split(/\s+/).filter(Boolean);
    if (parts.length === 0) return '?';
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase();
};

const normalizePositions = (positions) => {
    if (!positions) return [];
    return positions
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean);
};

function PlayerAvatar({ src, alt, fallback }) {
    const [failed, setFailed] = useState(false);

    if (!src || failed) {
        return <span className="mercado-avatar-fallback">{fallback}</span>;
    }

    return <img src={src} alt={alt} loading="lazy" decoding="async" onError={() => setFailed(true)} />;
}

export default function LigaClubePerfil() {
    const liga = getLigaFromWindow();
    const clube = getClubePerfilFromWindow();

    if (!liga || !clube) {
        return (
            <main className="liga-clube-perfil-screen">
                <p className="ligas-empty">Clube ou liga indisponível.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    const escudoUrl = clube?.escudo_url ?? null;
    const esquemaUrl = clube?.esquema_tatico_imagem_url ?? null;
    const nickname = clube?.nickname || clube?.dono || 'Sem nickname';
    const platformLabel = [clube?.plataforma, clube?.geracao].filter(Boolean).join(' · ');
    const players = Array.isArray(clube.players) ? clube.players : [];

    return (
        <main className="liga-clube-perfil-screen">
            <section className="liga-dashboard-hero clube-perfil-hero">
                <p className="ligas-eyebrow">CLUBE</p>
                <div className="clube-perfil-header">
                    <div className="clube-perfil-escudo">
                        {escudoUrl ? (
                            <img src={escudoUrl} alt={`Escudo do ${clube.nome}`} />
                        ) : (
                            <span className="clube-perfil-escudo-fallback">{getInitials(clube.nome)}</span>
                        )}
                    </div>
                    <div className="clube-perfil-info">
                        <h1 className="clube-perfil-title">{clube.nome}</h1>
                        <div className="clube-perfil-meta">
                            <span className="clube-perfil-chip">#{nickname}</span>
                            <span className="clube-perfil-chip">
                                {platformLabel || 'Plataforma não informada'}
                            </span>
                        </div>
                    </div>
                </div>
                {esquemaUrl && (
                    <div className="clube-perfil-esquema">
                        <p className="clube-perfil-section-title">ESQUEMA TÁTICO</p>
                        <div className="meu-elenco-esquema-preview">
                            <img src={esquemaUrl} alt={`Esquema tático do ${clube.nome}`} />
                        </div>
                    </div>
                )}
            </section>

            <section className="mercado-table-wrap" aria-label={`Elenco do ${clube.nome}`}>
                <div className="mercado-list-header">
                    <span>Jogador</span>
                    <span>OVR / Posição</span>
                </div>
                <div className="mercado-player-list">
                    {players.length ? (
                        players.map((player) => {
                            const name = player?.short_name || player?.long_name || 'Sem nome';
                            const positions = normalizePositions(player?.player_positions);
                            const positionBadge = positions[0]?.toUpperCase() ?? '—';
                            const ovr = player?.overall ?? '—';
                            const ovrTone = resolveOvrTone(ovr);
                            const imageUrl = proxyFaceUrl(player?.player_face_url);

                            return (
                                <article key={player.id} className="mercado-player-card liga-clube-perfil-card">
                                    <div className="mercado-player-card-content">
                                        <span className={`mercado-ovr-badge ovr-${ovrTone}`}>
                                            {ovr}
                                        </span>
                                        <span className="mercado-player-avatar">
                                            <PlayerAvatar
                                                src={imageUrl}
                                                alt={name}
                                                fallback={getInitials(name)}
                                            />
                                            <span className="mercado-player-position">{positionBadge}</span>
                                        </span>
                                        <div className="mercado-player-info">
                                            <strong>{name}</strong>
                                            <span>{positions.join(', ') || 'Posição desconhecida'}</span>
                                        </div>
                                    </div>
                                </article>
                            );
                        })
                    ) : (
                        <p className="ligas-empty">Nenhum jogador listado para este clube.</p>
                    )}
                </div>
            </section>

            <Navbar active="ligas" />
        </main>
    );
}
