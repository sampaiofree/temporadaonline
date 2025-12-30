import { useState } from 'react';

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0,
});

const formatCurrency = (value) => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }
    return currencyFormatter.format(value);
};

const formatDetailLabel = (key) =>
    key
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());

const formatDetailValue = (key, value) => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (typeof value === 'boolean') {
        return value ? 'Sim' : 'Não';
    }

    if (key.endsWith('_eur')) {
        return formatCurrency(value);
    }

    if (key.endsWith('_cm')) {
        return `${value} cm`;
    }

    if (key.endsWith('_kg')) {
        return `${value} kg`;
    }

    if (key.endsWith('_date') || key === 'dob') {
        const date = new Date(value);
        if (!Number.isNaN(date.getTime())) {
            return date.toLocaleDateString('pt-BR');
        }
    }

    return String(value);
};

const normalizePositions = (positions) => {
    if (!positions) return [];
    return String(positions)
        .split(',')
        .map((p) => p.trim())
        .filter(Boolean);
};

const getOvrTone = (overall) => {
    const ovr = Number(overall ?? 0);
    if (ovr >= 80) return 'high';
    if (ovr >= 60) return 'mid';
    return 'low';
};

const getPlayerName = (player) => (player?.short_name || player?.long_name || '').toString().trim();

const resolveFaceUrl = (url) => {
    if (!url) return null;
    if (url.startsWith('/')) return url;
    const trimmed = url.replace(/^https?:\/\//, '');
    return `https://images.weserv.nl/?url=${encodeURIComponent(trimmed)}&w=240&h=240`;
};

function PlayerAvatar({ src, alt, fallback }) {
    const [failed, setFailed] = useState(false);

    if (!src || failed) {
        return <span className="mercado-avatar-fallback">{fallback}</span>;
    }

    return (
        <img
            src={src}
            alt={alt}
            loading="lazy"
            decoding="async"
            onError={() => setFailed(true)}
        />
    );
}

export default function PlayerDetailModal({
    player,
    snapshot,
    fullData,
    expanded = false,
    loading = false,
    error,
    statusLabel,
    onClose,
    onToggleDetails,
    primaryAction,
}) {
    if (!player) return null;

    const detailSnapshot = snapshot ?? player;
    const name = getPlayerName(detailSnapshot) || '—';
    const avatarName = getPlayerName(player) || name;
    const statusText = statusLabel || '—';
    const positions = normalizePositions(detailSnapshot?.player_positions);
    const canToggle = typeof onToggleDetails === 'function';

    return (
        <div
            className="player-detail-modal"
            role="dialog"
            aria-modal="true"
            aria-label="Detalhes do jogador"
        >
            <div className="player-detail-header">
                <div className="player-detail-header-inner">
                    <div className="player-detail-avatar">
                        <PlayerAvatar
                            src={resolveFaceUrl(player?.player_face_url)}
                            alt={avatarName}
                            fallback={avatarName.slice(0, 2).toUpperCase()}
                        />
                    </div>
                    <div className="player-detail-identity">
                        <p className="player-detail-name">{name}</p>
                        <div className="player-detail-meta">
                            <span className="player-detail-status">● {statusText}</span>
                            <span className="player-detail-nationality">
                                {detailSnapshot?.nationality_name || '—'}
                            </span>
                        </div>
                    </div>
                    <div className={`player-detail-ovr ovr-${getOvrTone(detailSnapshot?.overall)}`}>
                        {detailSnapshot?.overall ?? '—'}
                    </div>
                </div>
            </div>

            <div className="player-detail-summary">
                <div className="player-detail-summary-card highlight">
                    <span>Posições</span>
                    <strong>{positions.join(' · ') || '—'}</strong>
                </div>
                <div className="player-detail-summary-card">
                    <span>Idade</span>
                    <strong>{detailSnapshot?.age ? `${detailSnapshot.age} anos` : '—'}</strong>
                </div>
                <div className="player-detail-summary-card">
                    <span>Valor de mercado</span>
                    <strong>{formatCurrency(detailSnapshot?.value_eur)}</strong>
                </div>
                <div className="player-detail-summary-card">
                    <span>Salário</span>
                    <strong>{formatCurrency(detailSnapshot?.wage_eur)}</strong>
                </div>
            </div>

            <section className="player-detail-performance">
                <h4>Atributos de Performance</h4>
                <div className="player-detail-performance-grid">
                    <div className="player-detail-stat">
                        <span>PAC</span>
                        <strong>{detailSnapshot?.pace ?? '—'}</strong>
                    </div>
                    <div className="player-detail-stat">
                        <span>DRI</span>
                        <strong>{detailSnapshot?.dribbling ?? '—'}</strong>
                    </div>
                    <div className="player-detail-stat">
                        <span>SHO</span>
                        <strong>{detailSnapshot?.shooting ?? '—'}</strong>
                    </div>
                    <div className="player-detail-stat danger">
                        <span>DEF</span>
                        <strong>{detailSnapshot?.defending ?? '—'}</strong>
                    </div>
                </div>
            </section>

            <div className="player-detail-full">
                <button
                    type="button"
                    className="player-detail-full-toggle"
                    onClick={onToggleDetails}
                    disabled={loading || !canToggle}
                >
                    {expanded
                        ? 'Ocultar informações detalhadas'
                        : loading
                        ? 'Carregando...'
                        : 'Informações Detalhadas'}
                </button>

                {error && <p className="modal-error">{error}</p>}

                {expanded && fullData && (
                    <div className="player-detail-full-list">
                        {Object.entries(fullData).map(([key, value]) => (
                            <div className="player-detail-full-row" key={key}>
                                <span>{formatDetailLabel(key)}</span>
                                <strong>{formatDetailValue(key, value)}</strong>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            <div className="player-detail-actions">
                <button type="button" className="btn-outline" onClick={onClose}>
                    Fechar
                </button>
                {primaryAction && (
                    <button
                        type="button"
                        className="player-detail-primary"
                        onClick={primaryAction.onClick}
                        disabled={primaryAction.disabled}
                    >
                        {primaryAction.label}
                    </button>
                )}
            </div>
        </div>
    );
}
