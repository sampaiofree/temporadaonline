import { useMemo, useState } from 'react';
import Navbar from '../components/app_publico/Navbar';

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getClubePerfilFromWindow = () => window.__CLUBE_PERFIL__ ?? null;

const PLATFORM_BADGES = [
    { match: ['pc'], label: 'PC', className: 'platform-pc' },
    { match: ['ps5', 'playstation'], label: 'PS5', className: 'platform-ps' },
    { match: ['xbox'], label: 'XBOX', className: 'platform-xbox' },
];

const getPlatformBadge = (plataforma) => {
    if (!plataforma) {
        return { label: '—', className: 'platform-default' };
    }
    const normalized = plataforma.toString().toLowerCase();
    const match = PLATFORM_BADGES.find((item) =>
        item.match.some((token) => normalized.includes(token)),
    );
    if (match) {
        return { label: match.label, className: match.className };
    }
    const short = plataforma.toString().toUpperCase().slice(0, 4);
    return { label: short, className: 'platform-default' };
};

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

const formatCurrency = (value) => {
    if (value === null || value === undefined) return '—';
    return Number(value).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0,
    });
};

const formatNumber = (value) => {
    if (value === null || value === undefined) return '0';
    return Number(value).toLocaleString('pt-BR');
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
    const platformBadge = getPlatformBadge(clube?.plataforma);
    const platformImage = clube?.plataforma_imagem ?? null;
    const achievements = Array.isArray(clube.achievement_images) ? clube.achievement_images : [];
    const players = Array.isArray(clube.players) ? clube.players : [];

    const clubValue = clube?.club_value ?? 0;
    const valorElenco = clube?.valor_elenco ?? 0;
    const saldoClube = clube?.saldo ?? 0;
    const fansTotal = clube?.fans_total ?? 0;

    return (
        <main className="liga-clube-perfil-screen">
            <div className="clube-perfil-top-line">
                <span className="clube-perfil-user-icon">{getInitials(nickname)}</span>
                <span className="clube-perfil-nickname">#{nickname}</span>
                <span className={`clube-perfil-platform ${platformBadge.className}`}>
                    {platformImage ? (
                        <img src={platformImage} alt={clube?.plataforma || platformBadge.label} />
                    ) : (
                        platformBadge.label
                    )}
                </span>
            </div>
            <section className="clube-perfil-stats" aria-label="Valor e conquistas">
                <article className="clube-perfil-value-card">
                    <p className="clube-perfil-stats-label">Valor do clube</p>
                    <strong>{formatCurrency(clubValue)}</strong>
                    <p className="clube-perfil-stats-meta">
                        Elenco {formatCurrency(valorElenco)} + Saldo {formatCurrency(saldoClube)}
                    </p>
                </article>
                <article className="clube-perfil-achievements-card">
                    <p className="clube-perfil-stats-label">Conquistas</p>
                    <div className="clube-perfil-achievement-gallery">
                        {achievements.length === 0 ? (
                            <span className="clube-perfil-achievement-empty">Nenhuma conquista resgatada</span>
                        ) : (
                            achievements.map((item) => (
                                <img key={item.id} src={item.image_url} alt={item.nome} loading="lazy" />
                            ))
                        )}
                    </div>
                </article>
                <article className="clube-perfil-fans-card">
                    <p className="clube-perfil-stats-label">Fans</p>
                    <strong>{formatNumber(fansTotal)}</strong>
                    <p className="clube-perfil-stats-meta">Total acumulado</p>
                </article>
            </section>
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
