import { useMemo, useState } from 'react';

const DATA = window.__LEGACY_ONBOARDING_SELECTOR__ ?? {};
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const AGGRESSIVE_CLIP = 'polygon(16px 0, 100% 0, 100% calc(100% - 16px), calc(100% - 16px) 100%, 0 100%, 0 16px)';
const TOTAL_STEPS = 2;

const requestJson = async (url, options = {}) => {
    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            ...(options.headers || {}),
        },
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        const errors = payload?.errors;
        const firstError = errors && typeof errors === 'object'
            ? Object.values(errors).flat().find(Boolean)
            : null;
        throw new Error(firstError || payload?.message || 'Falha ao selecionar liga.');
    }

    return payload;
};

const LegacyButton = ({ children, onClick, disabled = false, variant = 'primary', className = '', type = 'button' }) => {
    const base = 'px-5 py-3 text-[10px] font-black uppercase italic tracking-[0.2em] transition-all active:translate-y-[1px] disabled:opacity-40 disabled:cursor-not-allowed';
    const variantClass = variant === 'outline'
        ? 'bg-[#121212] text-[#FFD700] border border-[#FFD700]/60'
        : 'bg-[#FFD700] text-[#121212]';

    return (
        <button
            type={type}
            onClick={onClick}
            disabled={disabled}
            className={`${base} ${variantClass} ${className}`.trim()}
            style={{ clipPath: AGGRESSIVE_CLIP }}
        >
            {children}
        </button>
    );
};

export default function OnboardingClubeSelect() {
    const confederacoes = DATA.confederacoes ?? [];
    const endpoints = DATA.endpoints ?? {};

    const [step, setStep] = useState(1);
    const [selectedConfederacaoId, setSelectedConfederacaoId] = useState(confederacoes[0]?.id ?? null);
    const [selectedLigaId, setSelectedLigaId] = useState(null);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    const selectedConfederacao = useMemo(
        () => confederacoes.find((confed) => confed.id === selectedConfederacaoId) ?? null,
        [confederacoes, selectedConfederacaoId],
    );
    const ligas = selectedConfederacao?.ligas ?? [];
    const progress = (step / TOTAL_STEPS) * 100;

    const selectLiga = async () => {
        if (!selectedConfederacaoId || !selectedLigaId) {
            setError('Selecione uma liga para continuar.');
            return;
        }

        if (!endpoints.select_liga_url) {
            setError('Endpoint de seleção de liga não configurado.');
            return;
        }

        setSaving(true);
        setError('');

        try {
            const response = await requestJson(endpoints.select_liga_url, {
                method: 'POST',
                body: JSON.stringify({
                    confederacao_id: selectedConfederacaoId,
                    liga_id: selectedLigaId,
                }),
            });

            if (response?.redirect) {
                window.navigateWithLoader(response.redirect);
                return;
            }

            setError('Liga selecionada, mas sem URL de redirecionamento.');
        } catch (currentError) {
            setError(currentError?.message || 'Não foi possível selecionar a liga.');
        } finally {
            setSaving(false);
        }
    };

    const handleConfederacaoContinue = () => {
        if (!selectedConfederacaoId) {
            setError('Selecione uma confederação para continuar.');
            return;
        }

        setError('');
        setStep(2);
    };

    const renderConfederacaoStep = () => (
        <section className="bg-[#1E1E1E] border-l-[6px] border-[#FFD700] p-6 md:p-8 space-y-6" style={{ clipPath: AGGRESSIVE_CLIP }}>
            <div className="space-y-2">
                <h2 className="text-2xl font-black italic uppercase font-heading text-white">1. Universo</h2>
                <p className="text-[10px] text-white/45 font-bold uppercase italic tracking-[0.12em]">
                    Escolha a confederação onde você quer competir.
                </p>
            </div>

            <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                {confederacoes.map((confed) => {
                    const active = confed.id === selectedConfederacaoId;

                    return (
                        <button
                            key={confed.id}
                            type="button"
                            onClick={() => {
                                setSelectedConfederacaoId(confed.id);
                                setSelectedLigaId(null);
                                setError('');
                            }}
                            className={`text-left p-4 border transition-all ${active ? 'bg-[#FFD700] text-[#121212] border-[#FFD700]' : 'bg-[#121212] text-white border-white/15 hover:border-[#FFD700]/50'}`}
                            style={{ clipPath: AGGRESSIVE_CLIP }}
                        >
                            <p className="text-[12px] font-black uppercase italic tracking-[0.15em]">{confed.nome}</p>
                            <p className={`text-[10px] font-bold uppercase italic mt-1 ${active ? 'text-[#121212]/75' : 'text-white/45'}`}>
                                {confed.descricao || 'Confederação sem descrição'}
                            </p>
                        </button>
                    );
                })}
            </div>

            <div className="flex flex-col sm:flex-row gap-3">
                <LegacyButton
                    variant="outline"
                    className="w-full"
                    onClick={() => {
                        if (endpoints.cancel_url) {
                            window.navigateWithLoader(endpoints.cancel_url);
                        }
                    }}
                >
                    Voltar
                </LegacyButton>
                <LegacyButton
                    className="w-full"
                    onClick={handleConfederacaoContinue}
                    disabled={!selectedConfederacaoId || confederacoes.length === 0}
                >
                    Continuar para ligas
                </LegacyButton>
            </div>
        </section>
    );

    const renderLigaStep = () => (
        <section className="bg-[#1E1E1E] border-l-[6px] border-[#FFD700] p-6 md:p-8 space-y-6" style={{ clipPath: AGGRESSIVE_CLIP }}>
            <div className="space-y-2">
                <h2 className="text-2xl font-black italic uppercase font-heading text-white">2. Liga</h2>
                <p className="text-[10px] text-white/45 font-bold uppercase italic tracking-[0.12em]">
                    Universo atual: {selectedConfederacao?.nome || 'não definido'}
                </p>
            </div>

            {selectedConfederacao ? (
                <article className="bg-[#121212] p-4 border border-[#FFD700]/35" style={{ clipPath: AGGRESSIVE_CLIP }}>
                    <p className="text-[9px] text-[#FFD700] font-black uppercase italic tracking-[0.2em]">Confederação selecionada</p>
                    <strong className="block text-[14px] font-black uppercase italic mt-2">{selectedConfederacao.nome}</strong>
                    <span className="block text-[10px] text-white/45 font-bold uppercase italic mt-1">
                        {selectedConfederacao.descricao || 'Sem descrição'}
                    </span>
                </article>
            ) : null}

            {ligas.length === 0 ? (
                <p className="text-[11px] text-white/60 font-black uppercase italic tracking-[0.1em]">Nenhuma liga disponível nesta confederação.</p>
            ) : (
                <div className="space-y-2">
                    {ligas.map((liga) => {
                        const active = liga.id === selectedLigaId;

                        return (
                            <button
                                key={liga.id}
                                type="button"
                                onClick={() => {
                                    setSelectedLigaId(liga.id);
                                    setError('');
                                }}
                                className={`w-full text-left p-4 border transition-all ${active ? 'bg-[#FFD700] text-[#121212] border-[#FFD700]' : 'bg-[#121212] text-white border-white/15 hover:border-[#FFD700]/50'}`}
                                style={{ clipPath: AGGRESSIVE_CLIP }}
                            >
                                <div className="flex items-center justify-between gap-2">
                                    <p className="text-[12px] font-black uppercase italic tracking-[0.13em]">{liga.nome}</p>
                                    {liga.registered ? (
                                        <span className="text-[8px] font-black uppercase italic px-2 py-1 bg-[#008000] text-white" style={{ clipPath: AGGRESSIVE_CLIP }}>
                                            Já inscrito
                                        </span>
                                    ) : null}
                                </div>
                                <p className={`text-[10px] font-bold uppercase italic mt-1 ${active ? 'text-[#121212]/75' : 'text-white/45'}`}>
                                    {[liga.jogo, liga.geracao, liga.plataforma].filter(Boolean).join(' · ') || 'Sem metadados'}
                                </p>
                            </button>
                        );
                    })}
                </div>
            )}

            <div className="flex flex-col sm:flex-row gap-3">
                <LegacyButton
                    variant="outline"
                    className="w-full"
                    onClick={() => {
                        setError('');
                        setStep(1);
                    }}
                >
                    Voltar
                </LegacyButton>
                <LegacyButton
                    className="w-full"
                    onClick={selectLiga}
                    disabled={saving || !selectedConfederacaoId || !selectedLigaId}
                >
                    {saving ? 'Validando...' : 'Continuar para clube'}
                </LegacyButton>
            </div>
        </section>
    );

    return (
        <div className="min-h-screen bg-[#121212] px-6 py-20 relative overflow-hidden">
            <div className="absolute top-0 left-0 w-full h-full opacity-[0.03] pointer-events-none" style={{ backgroundImage: 'repeating-linear-gradient(45deg, rgba(255,215,0,0.35) 0, rgba(255,215,0,0.35) 1px, transparent 0, transparent 10px)' }} />
            <div className="absolute -top-20 -right-20 w-64 h-64 bg-[#FFD700] blur-[120px] opacity-10" />
            <div className="fixed top-0 left-0 w-full h-1 bg-white/5 z-[100]">
                <div
                    className="h-full bg-[#FFD700] shadow-[0_0_15px_#FFD700] transition-all duration-500"
                    style={{ width: `${progress}%` }}
                />
            </div>

            <div className="max-w-3xl mx-auto w-full relative z-10 space-y-6">
                <header className="space-y-3">
                    <p className="text-[10px] text-[#FFD700] font-black uppercase italic tracking-[0.35em]">Legacy XI</p>
                    <h1 className="text-4xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">
                        Escolha seu universo e liga
                    </h1>
                    <p className="text-[10px] text-white/40 font-bold uppercase italic tracking-[0.14em]">
                        Etapa {step} de {TOTAL_STEPS}: {step === 1 ? 'confederação' : 'liga'}
                    </p>
                </header>

                {step === 1 ? renderConfederacaoStep() : renderLigaStep()}

                {error ? (
                    <div className="bg-[#B22222]/25 border border-[#B22222] p-3 text-[10px] font-black uppercase italic tracking-[0.13em]" style={{ clipPath: AGGRESSIVE_CLIP }}>
                        {error}
                    </div>
                ) : null}
            </div>
        </div>
    );
}
