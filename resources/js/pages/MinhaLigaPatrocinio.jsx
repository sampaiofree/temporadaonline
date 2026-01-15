import { useMemo, useState } from 'react';
import Navbar from '../components/app_publico/Navbar';

const getData = () => window.__PATROCINIOS__ ?? {};

const formatNumber = (value) => {
    if (value === null || value === undefined) return '0';
    return Number(value).toLocaleString('pt-BR');
};

const getInitials = (text) => {
    if (!text) return 'P';
    const parts = text.split(/\s+/).filter(Boolean);
    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }
    return `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase();
};

export default function MinhaLigaPatrocinio() {
    const data = getData();
    const liga = data.liga;
    const clube = data.clube;
    const [items, setItems] = useState(data.patrocinios ?? []);
    const [feedback, setFeedback] = useState('');
    const [loadingId, setLoadingId] = useState(null);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const claimedItems = useMemo(() => items.filter((item) => item.status === 'claimed'), [items]);
    const lockedItems = useMemo(() => items.filter((item) => item.status !== 'claimed'), [items]);

    if (!liga) {
        return (
            <main className="mco-screen club-editor-screen">
                <p className="ligas-empty">Liga não encontrada.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    const heroes = `Você já conquistou ${formatNumber(data.fans ?? 0)} fãs`;

    const handleClaim = async (id) => {
        if (!csrf) return;
        setFeedback('');
        setLoadingId(id);

        try {
            const response = await fetch(`/minha_liga/clube/patrocinio/${id}/claim?liga_id=${liga.id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
            });

            const payload = await response.json();
            if (!response.ok) {
                setFeedback(payload?.message || 'Não foi possível resgatar o patrocínio.');
                return;
            }

            setItems((previous) =>
                previous.map((item) =>
                    item.id === id
                        ? { ...item, claimed_at: payload.claimed_at, status: 'claimed', can_claim: false }
                        : item,
                ),
            );
            setFeedback(payload?.message || 'Patrocínio resgatado com sucesso.');
        } catch (error) {
            setFeedback('Erro ao resgatar o patrocínio.');
        } finally {
            setLoadingId(null);
        }
    };

    return (
        <main className="mco-screen club-editor-screen conquistas-screen">
            <Navbar active="ligas" />

            <section className="club-editor-hero conquistas-hero">
                <p className="club-editor-kicker">Patrocínios do clube</p>
                <h1 className="club-editor-title">Missão de marca</h1>
                <p className="club-editor-subtitle">
                    Alcance novos patrocinadores conforme acumula fãs e conquistas.
                </p>
                <p className="club-editor-tagline">{clube ? `${clube.nome} · Liga ${liga.nome}` : 'Crie um clube e desbloqueie parcerias.'}</p>
                <p className="club-editor-tagline">{heroes}</p>
            </section>

            {feedback && <p className="club-editor-feedback">{feedback}</p>}

            <section className="conquista-gallery">
                <div className="conquista-gallery-header">
                    <h2>Patrocínios resgatados</h2>
                </div>
                {claimedItems.length === 0 ? (
                    <p className="ligas-empty">Nenhum patrocínio foi resgatado ainda.</p>
                ) : (
                    <div className="conquista-gallery-grid">
                        {claimedItems.map((item) => (
                            <article key={item.id} className="patrocinio-card">
                                <div className="patrocinio-thumb">
                                    {item.imagem_url ? (
                                        <img src={item.imagem_url} alt={item.nome} loading="lazy" />
                                    ) : (
                                        <span>{getInitials(item.nome)}</span>
                                    )}
                                </div>
                                <div className="patrocinio-info">
                                    <p className="patrocinio-name">{item.nome}</p>
                                    <p className="patrocinio-value">€ {formatNumber(item.valor)}</p>
                                </div>
                            </article>
                        ))}
                    </div>
                )}
            </section>

            <section className="conquista-gallery">
                <div className="conquista-gallery-header">
                    <h2>Por conquistar</h2>
                </div>
                {lockedItems.length === 0 ? (
                    <p className="ligas-empty">Todos os patrocínios foram resgatados.</p>
                ) : (
                    <div className="conquista-locked-grid">
                        {lockedItems.map((item) => (
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
                                        Requer {formatNumber(item.fans)} fãs · oferece {formatNumber(item.valor)}€
                                    </p>
                                    <div className="conquista-progress">
                                        <div
                                            className="conquista-progress-bar"
                                            style={{
                                                width: `${Math.min(100, item.progress?.percent ?? 0)}%`,
                                                background: 'linear-gradient(120deg, #f59e0b, #eab308)',
                                            }}
                                        />
                                    </div>
                                    <p className="conquista-progress-text">
                                        {formatNumber(item.progress?.value ?? 0)} / {formatNumber(item.progress?.required ?? 0)} fãs
                                    </p>
                                    <div className="conquista-actions">
                                        {item.can_claim ? (
                                            <button
                                                type="button"
                                                className="btn-primary"
                                                onClick={() => handleClaim(item.id)}
                                                disabled={loadingId === item.id}
                                            >
                                                {loadingId === item.id ? 'Resgatando...' : 'Resgatar patrocínio'}
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
