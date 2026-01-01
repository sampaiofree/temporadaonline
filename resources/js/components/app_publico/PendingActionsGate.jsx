import { useEffect, useMemo, useState } from 'react';

const getOpponentName = (partida) => {
    if (!partida) return 'adversario';
    if (partida.is_mandante) {
        return partida.visitante_nickname || partida.visitante || 'adversario';
    }
    return partida.mandante_nickname || partida.mandante || 'adversario';
};

const getTeamLabels = (partida) => ({
    mandante: partida?.mandante || 'Mandante',
    visitante: partida?.visitante || 'Visitante',
});

export default function PendingActionsGate() {
    const [confirmations, setConfirmations] = useState([]);
    const [evaluations, setEvaluations] = useState([]);
    const [disabled, setDisabled] = useState(false);
    const [nota, setNota] = useState(0);
    const [denunciaTexto, setDenunciaTexto] = useState('');
    const [error, setError] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const fetchPending = async () => {
        try {
            const { data } = await window.axios.get('/api/me/pendencias');
            setConfirmations(Array.isArray(data?.confirmations) ? data.confirmations : []);
            setEvaluations(Array.isArray(data?.evaluations) ? data.evaluations : []);
            setDisabled(Boolean(data?.disabled));
        } catch (err) {
            setConfirmations([]);
            setEvaluations([]);
            setDisabled(true);
        }
    };

    useEffect(() => {
        fetchPending();
    }, []);

    const activeConfirmation = confirmations[0] ?? null;
    const activeEvaluation = !activeConfirmation ? evaluations[0] ?? null : null;
    const activePartida = activeConfirmation || activeEvaluation;

    useEffect(() => {
        setNota(0);
        setDenunciaTexto('');
        setError('');
        setSubmitting(false);
    }, [activePartida?.id]);

    const isBlocking =
        !disabled && (Boolean(activeConfirmation) || Boolean(activeEvaluation));

    useEffect(() => {
        if (!isBlocking) {
            return;
        }

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        return () => {
            document.body.style.overflow = previousOverflow;
        };
    }, [isBlocking]);

    const requireEvaluation = activeConfirmation && !activeConfirmation.avaliacao;

    const submitEvaluation = async (partidaId) => {
        if (!nota) {
            setError('Selecione uma nota antes de continuar.');
            return false;
        }

        await window.axios.post(`/api/partidas/${partidaId}/avaliacoes`, {
            nota: Number(nota),
        });
        return true;
    };

    const handleConfirmarPlacar = async () => {
        if (!activeConfirmation) return;

        setSubmitting(true);
        setError('');

        try {
            if (requireEvaluation) {
                const ok = await submitEvaluation(activeConfirmation.id);
                if (!ok) {
                    setSubmitting(false);
                    return;
                }
            }

            await window.axios.post(`/api/partidas/${activeConfirmation.id}/confirmar-placar`);
            await fetchPending();
        } catch (err) {
            const message =
                err.response?.data?.errors?.avaliacao?.[0] ??
                err.response?.data?.message ??
                'Não foi possível confirmar o placar.';
            setError(message);
        } finally {
            setSubmitting(false);
        }
    };

    const handleDenunciar = async () => {
        if (!activeConfirmation) return;

        if (!denunciaTexto.trim()) {
            setError('Informe o texto da denuncia.');
            return;
        }

        setSubmitting(true);
        setError('');

        try {
            await window.axios.post(`/api/partidas/${activeConfirmation.id}/denunciar`, {
                descricao: denunciaTexto.trim(),
            });
            await fetchPending();
        } catch (err) {
            const message =
                err.response?.data?.message ?? 'Não foi possível registrar a denúncia.';
            setError(message);
        } finally {
            setSubmitting(false);
        }
    };

    const handleEnviarAvaliacao = async () => {
        if (!activeEvaluation) return;

        if (!nota) {
            setError('Selecione uma nota antes de continuar.');
            return;
        }

        setSubmitting(true);
        setError('');

        try {
            await window.axios.post(`/api/partidas/${activeEvaluation.id}/avaliacoes`, {
                nota: Number(nota),
            });
            await fetchPending();
        } catch (err) {
            const message =
                err.response?.data?.message ?? 'Nao foi possivel registrar a avaliacao.';
            setError(message);
        } finally {
            setSubmitting(false);
        }
    };

    const opponentName = useMemo(() => getOpponentName(activePartida), [activePartida]);
    const teams = useMemo(() => getTeamLabels(activePartida), [activePartida]);

    if (!isBlocking) {
        return null;
    }

    return (
        <div className="meu-elenco-modal-overlay" role="dialog" aria-modal="true">
            <div className="meu-elenco-modal">
                {activeConfirmation && (
                    <>
                        <h3>Confirmar placar</h3>
                        <p className="meu-elenco-modal-description">
                            Confirme o placar desta partida para liberar o acesso ao app.
                        </p>
                        <div className="placar-resumo">
                            <div className="placar-resumo-row">
                                <span>{teams.mandante}</span>
                                <strong>{activeConfirmation.placar_mandante ?? '-'}</strong>
                            </div>
                            <div className="placar-resumo-row">
                                <span>{teams.visitante}</span>
                                <strong>{activeConfirmation.placar_visitante ?? '-'}</strong>
                            </div>
                        </div>
                        {requireEvaluation && (
                            <>
                                <p className="meu-elenco-modal-description">
                                    Selecione uma nota de 1 a 5 para {opponentName}.
                                </p>
                                <div className="filter-pill-row filter-pill-row-compact">
                                    {[1, 2, 3, 4, 5].map((value) => (
                                        <button
                                            key={value}
                                            type="button"
                                            className={`filter-pill${nota === value ? ' active' : ''}`}
                                            onClick={() => setNota(value)}
                                        >
                                            {value}
                                        </button>
                                    ))}
                                </div>
                            </>
                        )}
                        <p className="meu-elenco-modal-description">
                            Se houver divergência, denuncie a partida.
                        </p>
                        <textarea
                            className="denuncia-textarea"
                            placeholder="Descreva a denuncia"
                            value={denunciaTexto}
                            onChange={(event) => setDenunciaTexto(event.target.value)}
                        />
                        {error && <p className="modal-error">{error}</p>}
                        <div className="meu-elenco-modal-actions">
                            <button
                                type="button"
                                className="btn-outline"
                                onClick={handleDenunciar}
                                disabled={submitting}
                            >
                                {submitting ? 'Enviando...' : 'Denunciar'}
                            </button>
                            <button
                                type="button"
                                className="btn-primary"
                                onClick={handleConfirmarPlacar}
                                disabled={submitting || (requireEvaluation && !nota)}
                            >
                                {submitting ? 'Confirmando...' : 'Confirmar placar'}
                            </button>
                        </div>
                    </>
                )}

                {activeEvaluation && (
                    <>
                        <h3>Avaliar adversario</h3>
                        <p className="meu-elenco-modal-description">
                            Selecione uma nota de 1 a 5 para {opponentName}.
                        </p>
                        <div className="filter-pill-row filter-pill-row-compact">
                            {[1, 2, 3, 4, 5].map((value) => (
                                <button
                                    key={value}
                                    type="button"
                                    className={`filter-pill${nota === value ? ' active' : ''}`}
                                    onClick={() => setNota(value)}
                                >
                                    {value}
                                </button>
                            ))}
                        </div>
                        {error && <p className="modal-error">{error}</p>}
                        <div className="meu-elenco-modal-actions">
                            <button
                                type="button"
                                className="btn-primary"
                                onClick={handleEnviarAvaliacao}
                                disabled={submitting || !nota}
                            >
                                {submitting ? 'Enviando...' : 'Enviar avaliacao'}
                            </button>
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}
