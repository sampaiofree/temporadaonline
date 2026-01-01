import { useMemo, useState } from 'react';
import Navbar from '../components/app_publico/Navbar';

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getElencoFromWindow = () => window.__ELENCO_LIGA__ ?? [];

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

const resolveEscudoUrl = (url) => {
    if (!url) return null;
    if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('/storage/')) {
        return url;
    }
    return `/storage/${url}`;
};

const formatNota = (value) => {
    const num = Number(value);
    if (!Number.isFinite(num)) return '—';
    return num.toFixed(1);
};

const countFormatter = new Intl.NumberFormat('pt-BR');
const formatCount = (value) => countFormatter.format(value ?? 0);

function PlayerAvatar({ src, alt, fallback }) {
    const [failed, setFailed] = useState(false);

    if (!src || failed) {
        return <span className="mercado-avatar-fallback">{fallback}</span>;
    }

    return <img src={src} alt={alt} loading="lazy" decoding="async" onError={() => setFailed(true)} />;
}

export default function LigaElenco() {
    const liga = getLigaFromWindow();
    const elenco = getElencoFromWindow();

    const players = useMemo(() => (Array.isArray(elenco) ? elenco : []), [elenco]);

    if (!liga) {
        return (
            <main className="liga-elenco-screen">
                <p className="ligas-empty">Liga indisponível. Volte para o painel.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    return (
        <main className="liga-elenco-screen">
            <section className="liga-dashboard-hero">
                <p className="ligas-eyebrow">ELENCO DA LIGA</p>
                <h1 className="ligas-title">Ranking por desempenho</h1>
                <p className="ligas-subtitle">
                    Média calculada com base em todas as partidas da liga.
                </p>
            </section>

            <section className="mercado-table-wrap" aria-label="Ranking de desempenho">
                <div className="mercado-list-header">
                    <span>Jogador / Clube</span>
                    <span>Média / Números</span>
                </div>
                <div className="mercado-player-list">
                    {players.length === 0 ? (
                        <p className="ligas-empty">Nenhum desempenho registrado ainda.</p>
                    ) : (
                        players.map((player) => {
                            const name = player?.nome || '—';
                            const pos = player?.posicao || '—';
                            const ovr = player?.overall ?? '—';
                            const ovrTone = resolveOvrTone(ovr);
                            const imageUrl = proxyFaceUrl(player?.foto_url);
                            const clubName = player?.clube?.nome || 'Sem clube';
                            const clubEscudo = resolveEscudoUrl(player?.clube?.escudo_url);

                            return (
                                <article key={player.player_id} className="mercado-player-card liga-elenco-card">
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
                                            <span className="mercado-player-position">{pos}</span>
                                        </span>
                                        <div className="mercado-player-info">
                                            <strong>{name}</strong>
                                            <span className="liga-elenco-club">
                                                {clubEscudo ? (
                                                    <img src={clubEscudo} alt={clubName} />
                                                ) : (
                                                    <span className="liga-elenco-club-fallback">
                                                        {getInitials(clubName)}
                                                    </span>
                                                )}
                                                {clubName}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="liga-elenco-stats">
                                        <div className="liga-elenco-average">
                                            <span className="liga-elenco-average-label">MÉDIA</span>
                                            <strong>{formatNota(player?.nota_media)}</strong>
                                        </div>
                                        <div className="liga-elenco-numbers">
                                            <span>J {formatCount(player?.jogos)}</span>
                                            <span>G {formatCount(player?.gols)}</span>
                                            <span>A {formatCount(player?.assistencias)}</span>
                                        </div>
                                    </div>
                                </article>
                            );
                        })
                    )}
                </div>
            </section>

            <Navbar active="ligas" />
        </main>
    );
}
