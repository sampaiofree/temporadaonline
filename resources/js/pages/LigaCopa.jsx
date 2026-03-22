import { useEffect, useMemo, useState } from 'react';
import Navbar from '../components/app_publico/Navbar';

const getLigaFromWindow = () => window.__LIGA__ ?? null;
const getClubeFromWindow = () => window.__CLUBE__ ?? null;
const getCopaFromWindow = () => window.__COPA__ ?? null;

const TAB_META = [
    { id: 'grupos', label: 'Grupos' },
    { id: 'chave', label: 'Chave' },
    { id: 'partidas', label: 'Partidas' },
];

const VALID_TABS = new Set(TAB_META.map((tab) => tab.id));

const resolveInitialTab = () => {
    const params = new URLSearchParams(window.location.search);
    const requested = params.get('tab') || 'grupos';

    return VALID_TABS.has(requested) ? requested : 'grupos';
};

const syncTabInUrl = (nextTab) => {
    const params = new URLSearchParams(window.location.search);
    params.set('tab', nextTab);
    const nextQuery = params.toString();
    const nextUrl = `${window.location.pathname}?${nextQuery}`;
    window.history.replaceState({}, '', nextUrl);
};

const phaseStatusLabel = {
    ativa: 'Em andamento',
    concluida: 'Concluida',
    aguardando_correcao: 'Aguardando correcao',
    pendente: 'Pendente',
};

const estadoLabel = {
    confirmacao_necessaria: 'Aguardando horario',
    agendada: 'Agendada',
    confirmada: 'Confirmada',
    placar_registrado: 'Placar registrado',
    placar_confirmado: 'Placar confirmado',
    em_reclamacao: 'Em reclamacao',
    finalizada: 'Finalizada',
    wo: 'W.O.',
    cancelada: 'Cancelada',
};

const formatDateTime = (value) => {
    if (!value) return 'Sem horario definido';

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return 'Sem horario definido';

    return parsed.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const sectionCardStyle = {
    background: 'linear-gradient(160deg, rgba(13,17,23,0.96) 0%, rgba(24,29,38,0.98) 100%)',
    border: '1px solid rgba(255, 215, 0, 0.18)',
    boxShadow: '0 18px 40px rgba(0,0,0,0.28)',
    borderRadius: 22,
};

const renderShield = (url, alt) => (
    url ? (
        <img
            src={url}
            alt={alt}
            style={{ width: 40, height: 40, objectFit: 'cover', borderRadius: 12 }}
        />
    ) : (
        <div
            aria-hidden="true"
            style={{
                width: 40,
                height: 40,
                borderRadius: 12,
                display: 'grid',
                placeItems: 'center',
                background: 'rgba(255,255,255,0.06)',
                color: '#ffd709',
                fontWeight: 800,
                fontSize: 12,
            }}
        >
            SH
        </div>
    )
);

export default function LigaCopa() {
    const liga = getLigaFromWindow();
    const clube = getClubeFromWindow();
    const copa = getCopaFromWindow();
    const [activeTab, setActiveTab] = useState(resolveInitialTab);

    useEffect(() => {
        syncTabInUrl(activeTab);
    }, [activeTab]);

    const groups = Array.isArray(copa?.groups) ? copa.groups : [];
    const bracketPhases = Array.isArray(copa?.bracket?.phases) ? copa.bracket.phases : [];
    const cupMatches = Array.isArray(copa?.matches) ? copa.matches : [];
    const summary = copa?.summary ?? {};

    const tabContent = useMemo(() => {
        if (activeTab === 'grupos') {
            if (groups.length === 0) {
                return <p className="ligas-empty">A Copa ainda nao possui grupos configurados para esta liga.</p>;
            }

            return (
                <div style={{ display: 'grid', gap: 18 }}>
                    {groups.map((group) => (
                        <section key={group.id} style={{ ...sectionCardStyle, overflow: 'hidden' }}>
                            <div
                                style={{
                                    padding: '1rem 1.2rem',
                                    borderBottom: '1px solid rgba(255,255,255,0.06)',
                                    display: 'flex',
                                    justifyContent: 'space-between',
                                    alignItems: 'center',
                                    gap: 12,
                                }}
                            >
                                <div>
                                    <p className="ligas-eyebrow" style={{ marginBottom: 6 }}>{group.label}</p>
                                    <h3 className="ligas-title" style={{ fontSize: '1.15rem', margin: 0 }}>
                                        Classificacao parcial
                                    </h3>
                                </div>
                                <span className="league-periods-status">Top 2 avancam</span>
                            </div>
                            <div style={{ display: 'grid', gap: 1, background: 'rgba(255,255,255,0.04)' }}>
                                <div
                                    style={{
                                        display: 'grid',
                                        gridTemplateColumns: '56px minmax(0, 1fr) 70px 70px 70px',
                                        gap: 12,
                                        padding: '0.9rem 1.2rem',
                                        fontSize: 12,
                                        textTransform: 'uppercase',
                                        letterSpacing: '0.08em',
                                        color: 'rgba(255,255,255,0.55)',
                                        background: 'rgba(255,255,255,0.03)',
                                    }}
                                >
                                    <span>Pos</span>
                                    <span>Clube</span>
                                    <span style={{ textAlign: 'center' }}>P</span>
                                    <span style={{ textAlign: 'center' }}>V</span>
                                    <span style={{ textAlign: 'center' }}>SG</span>
                                </div>
                                {group.rows.map((row) => (
                                    <div
                                        key={row.club_id}
                                        style={{
                                            display: 'grid',
                                            gridTemplateColumns: '56px minmax(0, 1fr) 70px 70px 70px',
                                            gap: 12,
                                            padding: '0.95rem 1.2rem',
                                            alignItems: 'center',
                                            background: row.is_user
                                                ? 'linear-gradient(90deg, rgba(255,215,9,0.14) 0%, rgba(255,215,9,0.04) 100%)'
                                                : 'rgba(8,10,14,0.7)',
                                            borderLeft: row.qualified
                                                ? '4px solid rgba(255, 215, 9, 0.85)'
                                                : '4px solid transparent',
                                        }}
                                    >
                                        <strong style={{ color: row.is_user ? '#ffd709' : '#fff' }}>
                                            {row.pos}
                                        </strong>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 12, minWidth: 0 }}>
                                            {renderShield(row.club_escudo_url, row.club_name)}
                                            <div style={{ minWidth: 0 }}>
                                                <strong style={{ display: 'block', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                                                    {row.club_name}
                                                </strong>
                                                <small style={{ color: 'rgba(255,255,255,0.55)' }}>
                                                    {row.played}J · {row.goals_for} GP · {row.goals_against} GC
                                                </small>
                                            </div>
                                        </div>
                                        <strong style={{ textAlign: 'center' }}>{row.points}</strong>
                                        <span style={{ textAlign: 'center' }}>{row.wins}</span>
                                        <span style={{ textAlign: 'center', color: row.goal_balance >= 0 ? '#9ae6b4' : '#feb2b2' }}>
                                            {row.goal_balance >= 0 ? '+' : ''}{row.goal_balance}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </section>
                    ))}
                </div>
            );
        }

        if (activeTab === 'chave') {
            if (bracketPhases.length === 0) {
                return (
                    <p className="ligas-empty">
                        O mata-mata sera montado quando todos os grupos estiverem completos e com os placares confirmados.
                    </p>
                );
            }

            return (
                <div style={{ display: 'grid', gap: 18 }}>
                    {bracketPhases.map((phase) => (
                        <section key={phase.id} style={{ ...sectionCardStyle, padding: '1.2rem' }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 12, marginBottom: 18 }}>
                                <div>
                                    <p className="ligas-eyebrow" style={{ marginBottom: 6 }}>{phase.label}</p>
                                    <h3 className="ligas-title" style={{ fontSize: '1.1rem', margin: 0 }}>
                                        {phase.matches.length} confronto{phase.matches.length === 1 ? '' : 's'}
                                    </h3>
                                </div>
                                <span className="league-periods-status">{phaseStatusLabel[phase.status] ?? phase.status}</span>
                            </div>
                            <div style={{ display: 'grid', gap: 14 }}>
                                {phase.matches.map((match) => (
                                    <article
                                        key={`${phase.id}-${match.slot}`}
                                        style={{
                                            border: '1px solid rgba(255,255,255,0.08)',
                                            borderRadius: 18,
                                            padding: '1rem',
                                            background: match.is_user_involved
                                                ? 'linear-gradient(180deg, rgba(255,215,9,0.1) 0%, rgba(255,215,9,0.03) 100%)'
                                                : 'rgba(255,255,255,0.02)',
                                        }}
                                    >
                                        <div style={{ display: 'flex', justifyContent: 'space-between', gap: 12, alignItems: 'center', marginBottom: 12 }}>
                                            <strong>{match.aggregate?.home_club_name ?? 'Confronto'}</strong>
                                            <span style={{ color: 'rgba(255,255,255,0.45)', fontSize: 12 }}>
                                                {match.needs_review ? 'Agregado empatado' : `Chave ${match.slot_order}`}
                                            </span>
                                            <strong>{match.aggregate?.away_club_name ?? 'pendente'}</strong>
                                        </div>
                                        {match.aggregate && (
                                            <div style={{ display: 'flex', justifyContent: 'center', gap: 12, alignItems: 'baseline', marginBottom: 12 }}>
                                                <strong style={{ fontSize: '1.8rem', color: '#ffd709' }}>{match.aggregate.home_score}</strong>
                                                <span style={{ color: 'rgba(255,255,255,0.4)' }}>x</span>
                                                <strong style={{ fontSize: '1.8rem' }}>{match.aggregate.away_score}</strong>
                                            </div>
                                        )}
                                        {match.winner_club_name && !match.needs_review && (
                                            <p style={{ margin: '0 0 12px', color: '#9ae6b4', fontWeight: 700 }}>
                                                Classificado: {match.winner_club_name}
                                            </p>
                                        )}
                                        {match.needs_review && (
                                            <p style={{ margin: '0 0 12px', color: '#feb2b2', fontWeight: 700 }}>
                                                O agregado terminou empatado. Corrija a volta para liberar o avanço automatico.
                                            </p>
                                        )}
                                        <div style={{ display: 'grid', gap: 10 }}>
                                            {match.legs.map((leg) => (
                                                <div
                                                    key={leg.partida_id}
                                                    style={{
                                                        display: 'grid',
                                                        gridTemplateColumns: '1fr auto 1fr',
                                                        gap: 12,
                                                        alignItems: 'center',
                                                        padding: '0.9rem 1rem',
                                                        borderRadius: 14,
                                                        background: 'rgba(0,0,0,0.2)',
                                                    }}
                                                >
                                                    <div style={{ display: 'flex', alignItems: 'center', gap: 10, minWidth: 0 }}>
                                                        {renderShield(leg.mandante_logo, leg.mandante)}
                                                        <div style={{ minWidth: 0 }}>
                                                            <strong style={{ display: 'block', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                                                                {leg.mandante}
                                                            </strong>
                                                            <small style={{ color: 'rgba(255,255,255,0.55)' }}>Jogo {leg.perna}</small>
                                                        </div>
                                                    </div>
                                                    <div style={{ textAlign: 'center' }}>
                                                        <strong style={{ fontSize: '1.2rem' }}>
                                                            {leg.placar_mandante ?? '-'} x {leg.placar_visitante ?? '-'}
                                                        </strong>
                                                        <small style={{ display: 'block', color: 'rgba(255,255,255,0.5)' }}>
                                                            {estadoLabel[leg.estado] ?? leg.estado}
                                                        </small>
                                                    </div>
                                                    <div style={{ display: 'flex', alignItems: 'center', gap: 10, justifyContent: 'flex-end', minWidth: 0 }}>
                                                        <div style={{ minWidth: 0, textAlign: 'right' }}>
                                                            <strong style={{ display: 'block', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                                                                {leg.visitante}
                                                            </strong>
                                                            <small style={{ color: 'rgba(255,255,255,0.55)' }}>
                                                                {formatDateTime(leg.scheduled_at)}
                                                            </small>
                                                        </div>
                                                        {renderShield(leg.visitante_logo, leg.visitante)}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </article>
                                ))}
                            </div>
                        </section>
                    ))}
                </div>
            );
        }

        if (cupMatches.length === 0) {
            return <p className="ligas-empty">Seu clube ainda nao tem partidas de Copa nesta liga.</p>;
        }

        return (
            <div style={{ display: 'grid', gap: 16 }}>
                {cupMatches.map((match) => {
                    const canFinalize = match.estado === 'confirmada';
                    const finalizeHref = `/liga/partidas/${match.id}/finalizar?liga_id=${liga.id}`;
                    const matchCenterHref = `/liga/partidas?liga_id=${liga.id}`;

                    return (
                        <article key={match.id} style={{ ...sectionCardStyle, padding: '1.05rem 1.2rem' }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', gap: 16, alignItems: 'flex-start', marginBottom: 16 }}>
                                <div>
                                    <p className="ligas-eyebrow" style={{ marginBottom: 6 }}>
                                        {match.cup_phase_label ?? match.competition_label}
                                        {match.cup_group_label ? ` · ${match.cup_group_label}` : ''}
                                    </p>
                                    <h3 className="ligas-title" style={{ fontSize: '1.05rem', margin: 0 }}>
                                        {match.mandante} x {match.visitante}
                                    </h3>
                                </div>
                                <span className="league-periods-status">{estadoLabel[match.estado] ?? match.estado}</span>
                            </div>
                            <div style={{ display: 'grid', gridTemplateColumns: '1fr auto 1fr', gap: 14, alignItems: 'center' }}>
                                <div style={{ display: 'flex', alignItems: 'center', gap: 12, minWidth: 0 }}>
                                    {renderShield(match.mandante_logo, match.mandante)}
                                    <div style={{ minWidth: 0 }}>
                                        <strong style={{ display: 'block', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                                            {match.mandante}
                                        </strong>
                                        <small style={{ color: 'rgba(255,255,255,0.55)' }}>
                                            {match.is_mandante ? 'Seu clube' : 'Mandante'}
                                        </small>
                                    </div>
                                </div>
                                <div style={{ textAlign: 'center' }}>
                                    <strong style={{ fontSize: '1.4rem', display: 'block' }}>
                                        {match.placar_mandante ?? '-'} x {match.placar_visitante ?? '-'}
                                    </strong>
                                    <small style={{ color: 'rgba(255,255,255,0.55)' }}>{formatDateTime(match.scheduled_at)}</small>
                                </div>
                                <div style={{ display: 'flex', alignItems: 'center', gap: 12, justifyContent: 'flex-end', minWidth: 0 }}>
                                    <div style={{ minWidth: 0, textAlign: 'right' }}>
                                        <strong style={{ display: 'block', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                                            {match.visitante}
                                        </strong>
                                        <small style={{ color: 'rgba(255,255,255,0.55)' }}>
                                            {match.is_visitante ? 'Seu clube' : 'Visitante'}
                                        </small>
                                    </div>
                                    {renderShield(match.visitante_logo, match.visitante)}
                                </div>
                            </div>
                            <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap', marginTop: 16 }}>
                                <a className="btn-outline" href={matchCenterHref}>Abrir Match Center</a>
                                {canFinalize && <a className="btn-primary" href={finalizeHref}>Finalizar partida</a>}
                            </div>
                        </article>
                    );
                })}
            </div>
        );
    }, [activeTab, bracketPhases, clube, copa, cupMatches, groups, liga]);

    if (!liga || !copa) {
        return (
            <main className="liga-classificacao-screen">
                <p className="ligas-empty">A Copa da Liga nao esta disponivel para esta liga.</p>
                <Navbar active="ligas" />
            </main>
        );
    }

    return (
        <main className="liga-classificacao-screen" style={{ paddingBottom: 120 }}>
            <section
                className="liga-dashboard-hero"
                style={{
                    position: 'relative',
                    overflow: 'hidden',
                    borderRadius: 28,
                    background: 'radial-gradient(circle at top left, rgba(255,215,9,0.22), transparent 38%), linear-gradient(155deg, #10141c 0%, #181d27 100%)',
                    border: '1px solid rgba(255,215,9,0.16)',
                }}
            >
                <p className="ligas-eyebrow">COPA DA LIGA</p>
                <h1 className="ligas-title">Grupos, chave e calendario da copa</h1>
                <p className="ligas-subtitle">
                    Toda liga ativa disputa uma copa paralela. Os grupos fecham com 4 clubes e o mata-mata so abre quando todos os grupos terminam.
                </p>
                <div style={{ display: 'grid', gap: 12, gridTemplateColumns: 'repeat(auto-fit, minmax(160px, 1fr))', marginTop: 24 }}>
                    <div style={{ ...sectionCardStyle, padding: '1rem 1.1rem' }}>
                        <small style={{ color: 'rgba(255,255,255,0.55)', display: 'block', marginBottom: 6 }}>Fase atual</small>
                        <strong style={{ fontSize: '1.15rem' }}>{summary.current_phase_label ?? 'Fase de Grupos'}</strong>
                    </div>
                    <div style={{ ...sectionCardStyle, padding: '1rem 1.1rem' }}>
                        <small style={{ color: 'rgba(255,255,255,0.55)', display: 'block', marginBottom: 6 }}>Seu grupo</small>
                        <strong style={{ fontSize: '1.15rem' }}>{summary.viewer_group_label ?? 'Aguardando clube'}</strong>
                    </div>
                    <div style={{ ...sectionCardStyle, padding: '1rem 1.1rem' }}>
                        <small style={{ color: 'rgba(255,255,255,0.55)', display: 'block', marginBottom: 6 }}>Sua posicao</small>
                        <strong style={{ fontSize: '1.15rem' }}>
                            {summary.viewer_group_position ? `${summary.viewer_group_position}o lugar` : 'Sem classificacao'}
                        </strong>
                    </div>
                    <div style={{ ...sectionCardStyle, padding: '1rem 1.1rem' }}>
                        <small style={{ color: 'rgba(255,255,255,0.55)', display: 'block', marginBottom: 6 }}>Partidas de copa</small>
                        <strong style={{ fontSize: '1.15rem' }}>{summary.viewer_matches_count ?? 0}</strong>
                    </div>
                </div>
                {summary.champion && (
                    <div style={{ marginTop: 20 }}>
                        <span className="league-periods-status">Campeao atual: {summary.champion.club_name}</span>
                    </div>
                )}
            </section>

            <section style={{ display: 'flex', gap: 12, flexWrap: 'wrap', marginTop: 20, marginBottom: 24 }}>
                {TAB_META.map((tab) => (
                    <button
                        key={tab.id}
                        type="button"
                        onClick={() => setActiveTab(tab.id)}
                        className={activeTab === tab.id ? 'btn-primary' : 'btn-outline'}
                    >
                        {tab.label}
                    </button>
                ))}
                <a className="btn-outline" href={`/liga/partidas?liga_id=${liga.id}`}>Abrir Match Center</a>
            </section>

            {tabContent}

            <Navbar active="ligas" />
        </main>
    );
}
