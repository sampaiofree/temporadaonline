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

const ATTRIBUTE_GROUPS = [
    {
        key: 'pace',
        label: 'Pace',
        items: [
            { key: 'movement_acceleration', label: 'Acceleration' },
            { key: 'movement_sprint_speed', label: 'Sprint Speed' },
        ],
    },
    {
        key: 'shooting',
        label: 'Shooting',
        items: [
            { key: 'mentality_positioning', label: 'Positioning' },
            { key: 'attacking_finishing', label: 'Finishing' },
            { key: 'power_shot_power', label: 'Shot Power' },
            { key: 'power_long_shots', label: 'Long Shots' },
            { key: 'attacking_volleys', label: 'Volleys' },
            { key: 'mentality_penalties', label: 'Penalties' },
        ],
    },
    {
        key: 'passing',
        label: 'Passing',
        items: [
            { key: 'mentality_vision', label: 'Vision' },
            { key: 'attacking_crossing', label: 'Crossing' },
            { key: 'skill_fk_accuracy', label: 'Fk Accuracy' },
            { key: 'attacking_short_passing', label: 'Short Passing' },
            { key: 'skill_long_passing', label: 'Long Passing' },
            { key: 'skill_curve', label: 'Curve' },
        ],
    },
    {
        key: 'dribbling',
        label: 'Dribbling',
        items: [
            { key: 'movement_agility', label: 'Agility' },
            { key: 'movement_balance', label: 'Balance' },
            { key: 'movement_reactions', label: 'Reactions' },
            { key: 'skill_ball_control', label: 'Ball Control' },
            { key: 'skill_dribbling', label: 'Dribbling' },
            { key: 'mentality_composure', label: 'Composure' },
        ],
    },
    {
        key: 'defending',
        label: 'Defending',
        items: [
            { key: 'mentality_interceptions', label: 'Interceptions' },
            { key: 'attacking_heading_accuracy', label: 'Heading Accuracy' },
            { key: 'defending_marking_awareness', label: 'Marking Awareness' },
            { key: 'defending_standing_tackle', label: 'Standing Tackle' },
            { key: 'defending_sliding_tackle', label: 'Sliding Tackle' },
        ],
    },
    {
        key: 'physic',
        label: 'Physic',
        items: [
            { key: 'power_jumping', label: 'Jumping' },
            { key: 'power_stamina', label: 'Stamina' },
            { key: 'power_strength', label: 'Strength' },
            { key: 'mentality_aggression', label: 'Aggression' },
        ],
    },
];

const ATTRIBUTE_KEYS = new Set(
    ATTRIBUTE_GROUPS.flatMap((group) => [group.key, ...group.items.map((item) => item.key)]),
);

const getPlayerName = (player) => (player?.short_name || player?.long_name || '').toString().trim();

const resolveFaceUrl = (url) => {
    if (!url) return null;
    if (url.startsWith('/')) return url;
    const trimmed = url.replace(/^https?:\/\//, '');
    return `https://images.weserv.nl/?url=${encodeURIComponent(trimmed)}&w=240&h=240`;
};

const resolveAttributeValue = (value) => {
    if (value === null || value === undefined || value === '') {
        return '-';
    }
    return value;
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
    secondaryAction,
}) {
    if (!player) return null;

    const detailSnapshot = snapshot ?? player;
    const name = getPlayerName(detailSnapshot) || '—';
    const avatarName = getPlayerName(player) || name;
    const statusText = statusLabel || '—';
    const positions = normalizePositions(detailSnapshot?.player_positions);
    const nationalityName = detailSnapshot?.nationality_name || '—';
    const nationalityFlagUrl = detailSnapshot?.nationality_flag_url || null;
    const playstyleBadges = Array.isArray(detailSnapshot?.playstyle_badges)
        ? detailSnapshot.playstyle_badges
        : [];
    const canToggle = typeof onToggleDetails === 'function';
    const detailEntries = fullData
        ? Object.entries(fullData).filter(([key]) => !ATTRIBUTE_KEYS.has(key))
        : [];

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
                                {nationalityFlagUrl ? (
                                    <span className="player-detail-flag">
                                        <img
                                            src={nationalityFlagUrl}
                                            alt={`Bandeira do pais ${nationalityName}`}
                                            className="player-detail-flag-image"
                                        />
                                        <span className="player-detail-flag-name">{nationalityName}</span>
                                    </span>
                                ) : (
                                    nationalityName
                                )}
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

            {playstyleBadges.length > 0 && (
                <section className="player-detail-playstyles">
                    <h4>Playstyles</h4>
                    <div className="player-detail-playstyle-grid">
                        {playstyleBadges.map((badge) => (
                            <div className="player-detail-playstyle" key={badge.name}>
                                <img
                                    src={badge.image_url}
                                    alt={badge.name}
                                    className="player-detail-playstyle-image"
                                />
                                <span className="player-detail-playstyle-name">{badge.name}</span>
                            </div>
                        ))}
                    </div>
                </section>
            )}

            <section className="player-detail-performance">
                <h4>Atributos de Performance</h4>
                <div className="player-detail-attributes-grid">
                    {ATTRIBUTE_GROUPS.map((group) => (
                        <div className="player-detail-attribute-group" key={group.key}>
                            <div className="player-detail-attribute-header">
                                <span>{group.label}</span>
                                <strong>{resolveAttributeValue(detailSnapshot?.[group.key])}</strong>
                            </div>
                            <div className="player-detail-attribute-list">
                                {group.items.map((item) => (
                                    <div className="player-detail-attribute-row" key={item.key}>
                                        <span>{item.label}</span>
                                        <strong>{resolveAttributeValue(detailSnapshot?.[item.key])}</strong>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
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

                {expanded && detailEntries.length > 0 && (
                    <div className="player-detail-full-list">
                        {detailEntries.map(([key, value]) => (
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
                {secondaryAction && (
                    <button
                        type="button"
                        className="btn-outline"
                        onClick={secondaryAction.onClick}
                        disabled={secondaryAction.disabled}
                    >
                        {secondaryAction.label}
                    </button>
                )}
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
