import { useMemo, useState } from 'react';
import Navbar from '../components/app_publico/Navbar';

const getData = () => window.__CONQUISTAS__ ?? {};

const formatNumber = (value) => {
    if (value === null || value === undefined) return '0';
    return Number(value).toLocaleString('pt-BR');
};

const getInitials = (text) => {
    if (!text) return 'C';
    const parts = text.split(/\s+/).filter(Boolean);
    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }
    return `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase();
};

export default function MinhaLigaConquistas() {
    const data = getData();
    const liga = data.liga;
    const clube = data.clube;
    const [items, setItems] = useState(data.conquistas ?? []);
    const [feedback, setFeedback] = useState('');
    const [loadingId, setLoadingId] = useState(null);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const stats = useMemo(
        () => ({
            gols: data.progress?.gols ?? 0,
            assistencias: data.progress?.assistencias ?? 0,
            jogos: data.progress?.quantidade_jogos ?? 0,
        }),
        [data.progress],
    );

    const claimedItems = items.filter((item) => item.status === 'claimed');
    const unclaimedItems = items.filter((item) => item.status !== 'claimed');

    const handleClaim = async (conquistaId) => {
        if (!csrf) return;
        setFeedback('');
        setLoadingId(conquistaId);

        try {
            const response = await fetch(
                `/minha_liga/clube/conquistas/${conquistaId}/claim?liga_id=${liga.id}`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                },
            );

            const payload = await response.json();
            if (!response.ok) {
                setFeedback(payload?.message || 'Não foi possível resgatar a conquista.');
                return;
            }

            setItems((previous) =>
                previous.map((item) =>
                    item.id === conquistaId
                        ? { ...item, claimed_at: payload.claimed_at, status: 'claimed', can_claim: false }
                        : item,
                ),
            );
            setFeedback(payload?.message || 'Conquista resgatada com sucesso.');
        } catch (error) {
            setFeedback('Erro ao resgatar conquista.');
        } finally {
            setLoadingId(null);
        }
    };

    if (!liga) {
        return (
            <main className="mco-screen club-editor-screen">
                <p className="ligas-empty">Liga não encontrada.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    return (
        <main className="mco-screen club-editor-screen conquistas-screen">
            <Navbar active="ligas" />

            <section className="club-editor-hero conquistas-hero">
                <p className="club-editor-kicker">Conquistas do clube</p>
                <h1 className="club-editor-title">Minhas conquistas</h1>
                <p className="club-editor-subtitle">
                    Complete os requisitos e resgate os selos que já atingiu.
                </p>
                {clube ? (
                    <p className="club-editor-tagline">
                        {clube.nome} · Liga {liga.nome}
                    </p>
                ) : (
                    <p className="club-editor-tagline">
                        Crie um clube para desbloquear conquistas nesta liga.
                    </p>
                )}
            </section>

            {feedback && <p className="club-editor-feedback">{feedback}</p>}

            <section className="conquista-gallery">
                <div className="conquista-gallery-header">
                    <h2>Conquistas resgatadas</h2>
                </div>
                {claimedItems.length === 0 ? (
                    <p className="ligas-empty">Ainda não existem conquistas resgatadas.</p>
                ) : (
                    <div className="conquista-gallery-grid">
                        {claimedItems.map((item) => (
                            <article key={item.id} className="conquista-resgatada-card">
                                <span className="conquista-resgatada-tag">
                                    {item.tipo_label?.toUpperCase() || 'Elite'}
                                </span>
                                <div className="conquista-resgatada-media">
                                    {item.imagem_url ? (
                                        <img src={item.imagem_url} alt={item.nome} loading="lazy" />
                                    ) : (
                                        <span>{getInitials(item.nome)}</span>
                                    )}
                                </div>
                                <p className="conquista-thumb-name conquista-resgatada-name">{item.nome}</p>
                            </article>
                        ))}
                    </div>
                )}
            </section>

            <section className="conquista-gallery">
                <div className="conquista-gallery-header">
                    <h2>Por conquistar</h2>
                </div>
                {unclaimedItems.length === 0 ? (
                    <p className="ligas-empty">Todas as conquistas foram resgatadas.</p>
                ) : (
                    <div className="conquista-locked-grid">
                        {unclaimedItems.map((item) => (
                            <article key={item.id} className="conquista-locked-card">
                                <div className="conquista-thumb-media">
                                    {item.imagem_url ? (
                                        <img src={item.imagem_url} alt={item.nome} loading="lazy" />
                                    ) : (
                                        <span>{getInitials(item.nome)}</span>
                                    )}
                                </div>
                                <div className="conquista-locked-body">
                                    <p className="conquista-thumb-name">{item.nome}</p>
                                    <p className="conquista-progress-note">
                                        {item.tipo_label} · precisa de {item.quantidade}
                                    </p>
                                    <div className="conquista-progress">
                                        <div
                                            className="conquista-progress-bar"
                                            style={{ width: `${Math.min(100, item.progress?.percent ?? 0)}%` }}
                                        />
                                    </div>
                                    <p className="conquista-progress-text">
                                        {formatNumber(item.progress?.value ?? 0)} / {formatNumber(item.progress?.required ?? 0)}
                                    </p>
                                    <div className="conquista-actions">
                                        {item.can_claim ? (
                                            <button
                                                type="button"
                                                className="btn-primary"
                                                onClick={() => handleClaim(item.id)}
                                                disabled={loadingId === item.id}
                                            >
                                                {loadingId === item.id ? 'Resgatando...' : 'Pegar meu troféu'}
                                            </button>
                                        ) : (
                                            <span className="conquista-badge muted">Aguardando requisitos</span>
                                        )}
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>
                )}
            </section>
        </main>
    );
}
