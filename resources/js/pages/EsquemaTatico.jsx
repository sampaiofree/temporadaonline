import { useMemo, useRef, useState } from 'react';
import html2canvas from 'html2canvas';
import Navbar from '../components/app_publico/Navbar';

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getClubeFromWindow = () => window.__CLUBE__ ?? null;
const getEsquemaFromWindow = () => window.__ESQUEMA_TATICO__ ?? {};

const clamp = (value, min = 0.04, max = 0.96) => Math.min(max, Math.max(min, value));

const resolveFaceUrl = (url) => {
    if (!url) {
        return null;
    }
    if (url.startsWith('/')) {
        return url;
    }
    const trimmed = url.replace(/^https?:\/\//, '');
    return `https://images.weserv.nl/?url=${encodeURIComponent(trimmed)}&w=180&h=180&fit=cover`;
};

const getInitials = (name) => {
    if (!name) return '?';
    const parts = name.split(/\s+/).filter(Boolean);
    if (parts.length === 0) return '?';
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase();
};

const buildInitialPlacements = (layout, availablePlayers = []) => {
    const placements = {};
    const players = Array.isArray(layout?.players) ? layout.players : [];
    const availableIds = new Set(availablePlayers.map((player) => String(player?.id)));

    players.forEach((player) => {
        const id = player?.id;
        const x = Number(player?.x);
        const y = Number(player?.y);
        if (!id || Number.isNaN(x) || Number.isNaN(y)) return;
        if (availableIds.size > 0 && !availableIds.has(String(id))) return;
        placements[String(id)] = {
            x: clamp(x),
            y: clamp(y),
        };
    });

    return placements;
};

const createBlobFromCanvas = async (canvas) =>
    new Promise((resolve) => {
        canvas.toBlob((blob) => {
            if (blob) {
                resolve(blob);
                return;
            }

            const dataUrl = canvas.toDataURL('image/png', 0.92);
            fetch(dataUrl)
                .then((response) => response.blob())
                .then(resolve)
                .catch(() => resolve(null));
        }, 'image/png', 0.92);
    });

function PlayerChip({
    id,
    name,
    overall,
    positionLabel,
    imageUrl,
    initials,
    position,
    onPointerDown,
    onPointerMove,
    onPointerUp,
}) {
    const [failed, setFailed] = useState(false);
    const showFallback = !imageUrl || failed;

    const metaPosition = positionLabel || '—';
    const metaOverall = overall ?? '—';

    return (
        <button
            type="button"
            className="esquema-player"
            style={{ left: `${position.x * 100}%`, top: `${position.y * 100}%` }}
            onPointerDown={onPointerDown}
            onPointerMove={onPointerMove}
            onPointerUp={onPointerUp}
            onPointerCancel={onPointerUp}
            title={name}
            aria-label={`Mover ${name}`}
        >
            <span className="esquema-player-label">
                <span className="esquema-player-name">{name}</span>
                <span className="esquema-player-meta">
                    {metaOverall} | {metaPosition}
                </span>
            </span>
            <span className="esquema-player-chip">
                {showFallback ? (
                    <span className="esquema-player-fallback">{initials}</span>
                ) : (
                    <img
                        src={imageUrl}
                        alt={name}
                        loading="lazy"
                        decoding="async"
                        crossOrigin="anonymous"
                        className="esquema-player-photo"
                        onError={() => setFailed(true)}
                    />
                )}
            </span>
        </button>
    );
}

export default function EsquemaTatico() {
    const liga = getLigaFromWindow();
    const clube = getClubeFromWindow();
    const esquema = getEsquemaFromWindow();
    const players = Array.isArray(esquema?.players) ? esquema.players : [];
    const initialPlacements = useMemo(
        () => buildInitialPlacements(esquema?.layout, players),
        [esquema?.layout, players],
    );
    const [placements, setPlacements] = useState(initialPlacements);
    const [isSaving, setIsSaving] = useState(false);
    const [saveError, setSaveError] = useState('');
    const [saveSuccess, setSaveSuccess] = useState('');
    const [isCapturing, setIsCapturing] = useState(false);
    const fieldRef = useRef(null);
    const draggingRef = useRef({ id: null, pointerId: null });

    const playersById = useMemo(
        () => new Map(players.map((player) => [String(player.id), player])),
        [players],
    );

    const sortedRoster = useMemo(() => {
        return [...players]
            .sort((a, b) => {
                const overallA = Number(a?.overall ?? 0);
                const overallB = Number(b?.overall ?? 0);
                if (overallA !== overallB) return overallB - overallA;
                const nameA = (a?.short_name || a?.long_name || '').toString().toLowerCase();
                const nameB = (b?.short_name || b?.long_name || '').toString().toLowerCase();
                return nameA.localeCompare(nameB);
            });
    }, [players]);

    const placedIds = useMemo(() => new Set(Object.keys(placements)), [placements]);

    if (!liga) {
        return (
            <main className="esquema-screen">
                <p className="ligas-empty">Liga indisponível. Volte para o painel.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    if (!clube) {
        return (
            <main className="esquema-screen">
                <p className="ligas-empty">Clube não encontrado para esta liga.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    const handleAddPlayer = (playerId) => {
        const id = String(playerId);
        setPlacements((prev) => {
            if (prev[id]) return prev;

            const count = Object.keys(prev).length;
            const offsetX = ((count % 5) - 2) * 0.08;
            const offsetY = Math.floor(count / 5) * -0.06;

            return {
                ...prev,
                [id]: {
                    x: clamp(0.5 + offsetX),
                    y: clamp(0.78 + offsetY),
                },
            };
        });
    };

    const handleRemovePlayer = (playerId) => {
        const id = String(playerId);
        setPlacements((prev) => {
            if (!prev[id]) return prev;
            const next = { ...prev };
            delete next[id];
            return next;
        });
    };

    const handleClear = () => {
        setPlacements({});
        setSaveError('');
        setSaveSuccess('');
    };

    const updatePosition = (event, playerId) => {
        const field = fieldRef.current;
        if (!field) return;
        const rect = field.getBoundingClientRect();
        const rawX = (event.clientX - rect.left) / rect.width;
        const rawY = (event.clientY - rect.top) / rect.height;

        setPlacements((prev) => ({
            ...prev,
            [playerId]: {
                x: clamp(rawX),
                y: clamp(rawY),
            },
        }));
    };

    const handlePointerDown = (event, playerId) => {
        event.preventDefault();
        draggingRef.current = { id: String(playerId), pointerId: event.pointerId };
        event.currentTarget.setPointerCapture(event.pointerId);
        updatePosition(event, String(playerId));
    };

    const handlePointerMove = (event) => {
        const dragging = draggingRef.current;
        if (!dragging?.id) return;
        updatePosition(event, dragging.id);
    };

    const handlePointerUp = (event) => {
        const dragging = draggingRef.current;
        if (!dragging?.id) return;

        try {
            event.currentTarget.releasePointerCapture(dragging.pointerId);
        } catch (error) {
            // Ignora caso o pointer capture já tenha sido liberado.
        }

        draggingRef.current = { id: null, pointerId: null };
    };

    const handleSave = async () => {
        if (!fieldRef.current) {
            setSaveError('Campo indisponível para captura.');
            return;
        }

        const payloadPlayers = Object.entries(placements).map(([id, pos]) => ({
            id: Number(id),
            x: Number(pos.x.toFixed(4)),
            y: Number(pos.y.toFixed(4)),
        }));

        if (payloadPlayers.length === 0) {
            setSaveError('Adicione pelo menos um jogador antes de salvar.');
            return;
        }

        setIsSaving(true);
        setIsCapturing(true);
        setSaveError('');
        setSaveSuccess('');

        try {
            await new Promise((resolve) => setTimeout(resolve, 60));
            const canvas = await html2canvas(fieldRef.current, {
                backgroundColor: null,
                scale: 2,
                useCORS: true,
            });

            const blob = await createBlobFromCanvas(canvas);
            if (!blob) {
                throw new Error('Não foi possível gerar a imagem do esquema.');
            }

            const formData = new FormData();
            formData.append('layout', JSON.stringify({ players: payloadPlayers }));
            formData.append('imagem', blob, 'esquema-tatico.png');

            const saveUrl = liga?.id
                ? `/minha_liga/esquema-tatico?liga_id=${liga.id}`
                : '/minha_liga/esquema-tatico';

            const { data } = await window.axios.post(saveUrl, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            setSaveSuccess(data?.message ?? 'Esquema tático salvo com sucesso.');
        } catch (error) {
            const message =
                error.response?.data?.message ??
                error.message ??
                'Não foi possível salvar o esquema. Tente novamente.';
            setSaveError(message);
        } finally {
            setIsSaving(false);
            setIsCapturing(false);
        }
    };

    return (
        <main className="esquema-screen">
            <section className="esquema-hero">
                <p className="meu-elenco-eyebrow">MEU</p>
                <h1 className="meu-elenco-title">ESQUEMA TÁTICO • {clube.nome}</h1>
                <p className="liga-dashboard-subtitle">
                    Posicione seus jogadores no campo e salve a imagem para compartilhar com o adversário.
                </p>
                <div className="esquema-actions">
                    <button type="button" className="btn-primary" onClick={handleSave} disabled={isSaving}>
                        {isSaving ? 'Salvando...' : 'Salvar esquema'}
                    </button>
                    <button type="button" className="btn-outline" onClick={handleClear} disabled={isSaving}>
                        Limpar campo
                    </button>
                </div>
            </section>

            <section className="esquema-field-wrap" aria-label="Campo tático">
                <header>
                    <h2>Campo</h2>
                    <p>Arraste os jogadores para definir o posicionamento.</p>
                </header>
                <div
                    className={`esquema-field${isCapturing ? ' is-capturing' : ''}`}
                    ref={fieldRef}
                >
                    {Object.keys(placements).length === 0 && (
                        <div className="esquema-field-empty">
                            Nenhum jogador posicionado. Use a lista abaixo.
                        </div>
                    )}
                    {Object.entries(placements).map(([id, pos]) => {
                        const player = playersById.get(id);
                        if (!player) return null;
                        const name = player.short_name || player.long_name || 'Jogador';
                        const initials = getInitials(name);
                        const overall = player.overall ?? null;
                        const imageUrl = resolveFaceUrl(player.player_face_url);
                        const positionLabelRaw = player.player_positions
                            ? player.player_positions.split(',')[0].trim()
                            : '';
                        const positionLabel = positionLabelRaw ? positionLabelRaw.toUpperCase() : '—';

                        return (
                            <PlayerChip
                                key={id}
                                id={id}
                                name={name}
                                overall={overall}
                                positionLabel={positionLabel}
                                imageUrl={imageUrl}
                                initials={initials}
                                position={pos}
                                onPointerDown={(event) => handlePointerDown(event, id)}
                                onPointerMove={handlePointerMove}
                                onPointerUp={handlePointerUp}
                            />
                        );
                    })}
                </div>
                <p className="esquema-field-footnote">
                    Dica: toque no jogador e arraste para ajustar. Para remover, use a lista abaixo.
                </p>
                {saveError && <p className="esquema-feedback error">{saveError}</p>}
                {saveSuccess && <p className="esquema-feedback success">{saveSuccess}</p>}
            </section>

            <section className="esquema-roster" aria-label="Elenco disponível">
                <header className="esquema-roster-header">
                    <div>
                        <h2>Elenco disponível</h2>
                        <p>{sortedRoster.length} jogadores ativos</p>
                    </div>
                    <span className="esquema-roster-count">{Object.keys(placements).length} no campo</span>
                </header>
                <div className="esquema-roster-list">
                    {sortedRoster.length === 0 ? (
                        <p className="mercado-no-results">
                            Nenhum jogador ativo disponível. Atualize o elenco.
                        </p>
                    ) : (
                        sortedRoster.map((player) => {
                            const name = player.short_name || player.long_name || 'Jogador';
                            const positions = player.player_positions?.split(',').map((pos) => pos.trim());
                            const posLabel = positions?.[0] ?? '—';
                            const overall = player.overall ?? '—';
                            const isPlaced = placedIds.has(String(player.id));

                            return (
                                <article key={player.id} className={`esquema-roster-card${isPlaced ? ' placed' : ''}`}>
                                    <div className="esquema-roster-info">
                                        <span className="esquema-roster-ovr">{overall}</span>
                                        <div>
                                            <strong>{name}</strong>
                                            <span>{posLabel}</span>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        className={isPlaced ? 'btn-outline' : 'btn-primary'}
                                        onClick={() =>
                                            isPlaced
                                                ? handleRemovePlayer(player.id)
                                                : handleAddPlayer(player.id)
                                        }
                                        disabled={isSaving}
                                    >
                                        {isPlaced ? 'Remover' : 'Adicionar'}
                                    </button>
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
