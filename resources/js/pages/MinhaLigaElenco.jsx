import { useEffect, useState } from 'react';
import Navbar from '../components/app_publico/Navbar';
import backgroundDefault from '../../../storage/app/public/app/background/fundopadrao.webp';
import backgroundVertical from '../../../storage/app/public/app/background/fundo_vertical.webp';

const fallbackFace = 'https://via.placeholder.com/180?text=MCO';
const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0,
});

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getElencoFromWindow = () => window.__ELENCO__ ?? { data: [], links: [] };
const getUserClub = () => window.__USER_CLUB__ ?? null;
const getClubElencoIds = () => window.__CLUBE_ELENCO_IDS__ ?? [];

const formatValue = (value) => {
    if (value === null || typeof value === 'undefined') {
        return 'Valor não informado';
    }

    return currencyFormatter.format(value);
};

const getInitials = (name) => {
    if (!name) {
        return '?';
    }

    const parts = name.split(/\s+/).filter(Boolean);
    if (parts.length === 0) {
        return name.charAt(0).toUpperCase();
    }

    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }

    return (parts[0][0] + parts[1][0]).toUpperCase();
};

const normalizeLabel = (label) =>
    label?.replace(/&laquo;/g, '«').replace(/&raquo;/g, '»').replace(/&nbsp;/g, ' ') ?? '';

const proxyFaceUrl = (url) => {
    if (!url) {
        return null;
    }

    const trimmed = url.replace(/^https?:\/\//, '');
    return `https://images.weserv.nl/?url=${encodeURIComponent(trimmed)}&w=200&h=200`;
};

export default function MinhaLigaElenco() {
    const liga = getLigaFromWindow();
    const elenco = getElencoFromWindow();
    const players = elenco.data ?? elenco;
    const paginationLinks = elenco.links ?? [];
    const userClub = getUserClub();
    const [clubElencoIds, setClubElencoIds] = useState(getClubElencoIds());
    const [addingId, setAddingId] = useState(null);
    const [clubMessage, setClubMessage] = useState('');
    const [warningOpen, setWarningOpen] = useState(!userClub);

    useEffect(() => {
        setWarningOpen(!userClub);
    }, [userClub]);
    const backgroundStyles = {
        '--mco-cover': `url(${backgroundDefault})`,
        '--mco-cover-mobile': `url(${backgroundVertical})`,
    };

    const handleAddToClub = async (playerId) => {
        if (!liga) {
            return;
        }

        if (!userClub) {
            setWarningOpen(true);
            return;
        }

        if (clubElencoIds.includes(playerId) || addingId) {
            return;
        }

        setAddingId(playerId);
        setClubMessage('');

        try {
            const { data } = await window.axios.post(`/api/ligas/${liga.id}/clubes/${userClub.id}/comprar`, {
                elencopadrao_id: playerId,
            });
            setClubElencoIds((current) => [...current, playerId]);
            setClubMessage(data?.message ?? 'Jogador adicionado ao elenco.');
        } catch (error) {
            const status = error.response?.status;
            const message =
                error.response?.data?.message ?? 'Não foi possível adicionar este jogador ao clube.';
            setClubMessage(message);

            if (status === 409) {
                setClubElencoIds((current) => (current.includes(playerId) ? current : [...current, playerId]));
            }
        } finally {
            setAddingId(null);
        }
    };

    if (!liga) {
        return (
            <main className="mco-screen" style={backgroundStyles} aria-label="Elenco da liga">
                <p className="ligas-empty">Liga indisponível. Volte para o painel e tente novamente.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    return (
        <main className="mco-screen" style={backgroundStyles} aria-label="Elenco da liga">
            <section className="ligas-hero" aria-label="Cabeçalho do elenco">
                <p className="ligas-eyebrow">ELENCO</p>
                <h1 className="ligas-title">{liga.nome}</h1>
                <p className="ligas-subtitle">
                    Veja os jogadores que compõem o universo deste jogo, seus valores estimados, clubes e nacionalidades.
                </p>
                {clubMessage && <p className="elenco-feedback">{clubMessage}</p>}
            </section>
            <section className="elenco-list" aria-label="Cartas do elenco">
                {players.length === 0 ? (
                    <p className="ligas-empty">Nenhum jogador cadastrado para esta liga no momento.</p>
                ) : (
                    <>
                        <div className="elenco-grid">
                            {players.map((player) => {
                                const alreadyInClub = player.id && clubElencoIds.includes(player.id);

                                return (
                                    <article key={player.id ?? player.short_name} className="elenco-card">
                                        <div className="elenco-card-image" aria-hidden="true">
                                            {player.player_face_url ? (
                                                <img
                                                    src={proxyFaceUrl(player.player_face_url)}
                                                    alt={player.short_name}
                                                    loading="lazy"
                                                    decoding="async"
                                                    referrerPolicy="no-referrer"
                                                    crossOrigin="anonymous"
                                                />
                                            ) : (
                                                <span className="elenco-card-initials">
                                                    {getInitials(player.short_name)}
                                                </span>
                                            )}
                                        </div>
                                        <div className="elenco-card-body">
                                            <div className="elenco-card-line">
                                                <span className="elenco-card-title">{player.short_name}</span>
                                                <span className="elenco-card-meta">
                                                    {[player.club_name, player.nationality_name]
                                                        .filter(Boolean)
                                                        .join(' · ')}
                                                </span>
                                                <span className="elenco-card-value">
                                                    {formatValue(player.value_eur)}
                                                </span>
                                            </div>
                                            <div className="elenco-card-actions">
                                                {alreadyInClub ? (
                                                    <span className="elenco-card-status">
                                                        Já pertence a um clube
                                                    </span>
                                                ) : (
                                                    <button
                                                        type="button"
                                                        className="btn-outline small"
                                                        onClick={() => handleAddToClub(player.id)}
                                                        disabled={!userClub || addingId === player.id}
                                                    >
                                                        {addingId === player.id
                                                            ? 'Adicionando...'
                                                            : 'Adicionar ao meu elenco'}
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    </article>
                                );
                            })}
                        </div>
                        {paginationLinks.length > 0 && (
                            <nav className="elenco-pagination" aria-label="Paginação do elenco">
                                {paginationLinks.map((link, index) => (
                                    <a
                                        key={`${link.label}-${index}`}
                                        href={link.url ?? '#'}
                                        className={`elenco-pagination-link${link.active ? ' active' : ''}${
                                            !link.url ? ' disabled' : ''
                                        }`}
                                        aria-current={link.active ? 'page' : undefined}
                                    >
                                        {normalizeLabel(link.label)}
                                    </a>
                                ))}
                            </nav>
                        )}
                    </>
                )}
            </section>
            {warningOpen && (
                <div className="elenco-warning-modal-overlay" role="alertdialog" aria-modal="true">
                    <div className="elenco-warning-modal">
                        <p className="elenco-warning-title">Crie seu clube</p>
                        <p className="elenco-warning">
                            Você ainda não criou um clube para esta liga — registre um na tela anterior antes de montar o elenco.
                        </p>
                        <div className="elenco-warning-actions">
                            <button
                                type="button"
                                className="btn-primary"
                                onClick={() => {
                                    window.location.href = `/minha_liga?liga_id=${liga.id}`;
                                }}
                            >
                                Criar clube
                            </button>
                            <button type="button" className="btn-outline small" onClick={() => setWarningOpen(false)}>
                                Fechar
                            </button>
                        </div>
                    </div>
                </div>
            )}
            <Navbar active="ligas" />
        </main>
    );
}
