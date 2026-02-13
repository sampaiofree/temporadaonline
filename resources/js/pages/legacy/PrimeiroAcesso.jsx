import { useMemo, useState } from 'react';

const FIRST_ACCESS_DATA = window.__LEGACY_FIRST_ACCESS__ ?? {};
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const TOTAL_STEPS = 6;
const AGGRESSIVE_CLIP = 'polygon(16px 0, 100% 0, 100% calc(100% - 16px), calc(100% - 16px) 100%, 0 100%, 0 16px)';
const STEP_KEYS = ['regiao_idioma', 'nickname', 'whatsapp', 'plataforma_geracao', 'jogo', 'disponibilidade'];

const DAY_OPTIONS = [
    { value: 0, label: 'Domingo' },
    { value: 1, label: 'Segunda-feira' },
    { value: 2, label: 'Terça-feira' },
    { value: 3, label: 'Quarta-feira' },
    { value: 4, label: 'Quinta-feira' },
    { value: 5, label: 'Sexta-feira' },
    { value: 6, label: 'Sábado' },
];

const STEPS_META = {
    1: {
        title: 'Região e idioma',
        subtitle: 'Selecione sua região e idioma principal.',
        key: 'regiao_idioma',
    },
    2: {
        title: 'Nickname',
        subtitle: 'Defina seu nome de exibição nos rankings.',
        key: 'nickname',
    },
    3: {
        title: 'WhatsApp',
        subtitle: 'Contato para organização de partidas e convites.',
        key: 'whatsapp',
    },
    4: {
        title: 'Plataforma e geração',
        subtitle: 'Selecione onde você joga atualmente.',
        key: 'plataforma_geracao',
    },
    5: {
        title: 'Jogo',
        subtitle: 'Escolha o jogo principal da sua conta.',
        key: 'jogo',
    },
    6: {
        title: 'Horários disponíveis',
        subtitle: 'Cadastre pelo menos um horário válido.',
        key: 'disponibilidade',
    },
};

const defaultEntry = () => ({ dia_semana: 1, hora_inicio: '19:00', hora_fim: '21:00' });

const normalizeWhatsApp = (value) => String(value ?? '').replace(/\D+/g, '');

const sanitizeEntries = (entries) =>
    entries
        .map((entry) => ({
            dia_semana: Number(entry.dia_semana),
            hora_inicio: String(entry.hora_inicio || ''),
            hora_fim: String(entry.hora_fim || ''),
        }))
        .filter((entry) =>
            Number.isInteger(entry.dia_semana)
            && entry.dia_semana >= 0
            && entry.dia_semana <= 6
            && entry.hora_inicio
            && entry.hora_fim,
        );

const resolveInitialStep = (status) => {
    const steps = status?.steps ?? {};

    for (let i = 0; i < STEP_KEYS.length; i += 1) {
        if (!steps[STEP_KEYS[i]]) {
            return i + 1;
        }
    }

    return TOTAL_STEPS;
};

const jsonRequest = async (url, options = {}) => {
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

        throw new Error(firstError || payload?.message || 'Falha ao salvar.');
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

export default function PrimeiroAcesso() {
    const profileInitial = FIRST_ACCESS_DATA.profile ?? {};
    const options = FIRST_ACCESS_DATA.options ?? {};
    const endpoints = FIRST_ACCESS_DATA.endpoints ?? {};
    const initialStatus = FIRST_ACCESS_DATA.status ?? { steps: {}, is_complete: false };
    const initialEntries = Array.isArray(FIRST_ACCESS_DATA.disponibilidades)
        ? FIRST_ACCESS_DATA.disponibilidades
        : [];

    const [profile, setProfile] = useState({
        regiao_id: profileInitial.regiao_id ?? '',
        idioma_id: profileInitial.idioma_id ?? '',
        nickname: profileInitial.nickname ?? '',
        whatsapp: profileInitial.whatsapp ?? '',
        plataforma_id: profileInitial.plataforma_id ?? '',
        geracao_id: profileInitial.geracao_id ?? '',
        jogo_id: profileInitial.jogo_id ?? '',
    });
    const [entries, setEntries] = useState(
        (initialEntries.length > 0 ? initialEntries : [defaultEntry()]).map((entry) => ({
            dia_semana: Number(entry.dia_semana ?? 1),
            hora_inicio: entry.hora_inicio || '19:00',
            hora_fim: entry.hora_fim || '21:00',
        })),
    );
    const [status, setStatus] = useState(initialStatus);
    const [step, setStep] = useState(resolveInitialStep(initialStatus));
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');
    const [message, setMessage] = useState('');

    const stepInfo = STEPS_META[step];
    const progress = useMemo(() => (step / TOTAL_STEPS) * 100, [step]);

    const updateProfileField = (field, value) => {
        setProfile((previous) => ({ ...previous, [field]: value }));
    };

    const updateEntryField = (index, field, value) => {
        setEntries((previous) => previous.map((entry, current) => (current === index ? { ...entry, [field]: value } : entry)));
    };

    const goBack = () => {
        setError('');
        setMessage('');
        setStep((previous) => Math.max(1, previous - 1));
    };

    const goNext = () => {
        setError('');
        setMessage('');
        setStep((previous) => Math.min(TOTAL_STEPS, previous + 1));
    };

    const applyResponse = (response) => {
        if (response?.profile) {
            setProfile((previous) => ({
                ...previous,
                regiao_id: response.profile.regiao_id ?? previous.regiao_id,
                idioma_id: response.profile.idioma_id ?? previous.idioma_id,
                nickname: response.profile.nickname ?? previous.nickname,
                whatsapp: response.profile.whatsapp ?? previous.whatsapp,
                plataforma_id: response.profile.plataforma_id ?? previous.plataforma_id,
                geracao_id: response.profile.geracao_id ?? previous.geracao_id,
                jogo_id: response.profile.jogo_id ?? previous.jogo_id,
            }));
        }

        if (response?.status) {
            setStatus(response.status);

            if (response.status.is_complete && endpoints.finish_url) {
                window.location.assign(endpoints.finish_url);
                return true;
            }
        }

        return false;
    };

    const saveProfileStep = async (payload) => {
        if (!endpoints.update_profile_url) {
            throw new Error('Endpoint de atualização de perfil não configurado.');
        }

        const response = await jsonRequest(endpoints.update_profile_url, {
            method: 'PUT',
            body: JSON.stringify(payload),
        });

        const redirected = applyResponse(response);
        if (redirected) {
            return;
        }

        setMessage(response?.message || 'Etapa salva com sucesso.');
        goNext();
    };

    const saveDisponibilidadesStep = async () => {
        if (!endpoints.sync_disponibilidades_url) {
            throw new Error('Endpoint de horários não configurado.');
        }

        const payloadEntries = sanitizeEntries(entries);
        if (payloadEntries.length < 1) {
            throw new Error('Cadastre ao menos um horário disponível.');
        }

        const response = await jsonRequest(endpoints.sync_disponibilidades_url, {
            method: 'PUT',
            body: JSON.stringify({ entries: payloadEntries }),
        });

        const redirected = applyResponse(response);
        if (redirected) {
            return;
        }

        setMessage(response?.message || 'Horários salvos com sucesso.');
    };

    const onSaveStep = async () => {
        setSaving(true);
        setError('');
        setMessage('');

        try {
            if (step === 1) {
                if (!profile.regiao_id || !profile.idioma_id) {
                    throw new Error('Selecione região e idioma para continuar.');
                }

                await saveProfileStep({
                    regiao_id: Number(profile.regiao_id),
                    idioma_id: Number(profile.idioma_id),
                });
                return;
            }

            if (step === 2) {
                if (!String(profile.nickname).trim()) {
                    throw new Error('Informe um nickname válido.');
                }

                await saveProfileStep({ nickname: String(profile.nickname).trim() });
                return;
            }

            if (step === 3) {
                const whatsapp = normalizeWhatsApp(profile.whatsapp);
                if (!whatsapp) {
                    throw new Error('Informe um WhatsApp válido.');
                }

                await saveProfileStep({ whatsapp });
                return;
            }

            if (step === 4) {
                if (!profile.plataforma_id || !profile.geracao_id) {
                    throw new Error('Selecione plataforma e geração para continuar.');
                }

                await saveProfileStep({
                    plataforma_id: Number(profile.plataforma_id),
                    geracao_id: Number(profile.geracao_id),
                });
                return;
            }

            if (step === 5) {
                if (!profile.jogo_id) {
                    throw new Error('Selecione um jogo para continuar.');
                }

                await saveProfileStep({ jogo_id: Number(profile.jogo_id) });
                return;
            }

            await saveDisponibilidadesStep();
        } catch (currentError) {
            setError(currentError?.message || 'Falha ao salvar a etapa.');
        } finally {
            setSaving(false);
        }
    };

    const renderStepContent = () => {
        const inputBase = 'w-full bg-[#121212] border border-[#FFD700]/30 text-white px-4 py-3 font-black italic uppercase text-sm outline-none focus:border-[#FFD700]';
        const fieldBox = 'space-y-2';
        const labelBase = 'block text-[9px] font-black text-[#FFD700] uppercase italic tracking-[0.2em]';

        if (step === 1) {
            return (
                <div className="space-y-4">
                    <div className={fieldBox}>
                        <label htmlFor="primeiro-acesso-regiao" className={labelBase}>Região</label>
                        <select
                            id="primeiro-acesso-regiao"
                            className={inputBase}
                            style={{ clipPath: AGGRESSIVE_CLIP }}
                            value={profile.regiao_id}
                            onChange={(event) => updateProfileField('regiao_id', event.target.value)}
                        >
                            <option value="">Selecione a região</option>
                            {(options.regioes ?? []).map((regiao) => (
                                <option key={regiao.id} value={regiao.id}>{regiao.nome}</option>
                            ))}
                        </select>
                    </div>

                    <div className={fieldBox}>
                        <label htmlFor="primeiro-acesso-idioma" className={labelBase}>Idioma</label>
                        <select
                            id="primeiro-acesso-idioma"
                            className={inputBase}
                            style={{ clipPath: AGGRESSIVE_CLIP }}
                            value={profile.idioma_id}
                            onChange={(event) => updateProfileField('idioma_id', event.target.value)}
                        >
                            <option value="">Selecione o idioma</option>
                            {(options.idiomas ?? []).map((idioma) => (
                                <option key={idioma.id} value={idioma.id}>{idioma.nome}</option>
                            ))}
                        </select>
                    </div>
                </div>
            );
        }

        if (step === 2) {
            return (
                <div className={fieldBox}>
                    <label htmlFor="primeiro-acesso-nickname" className={labelBase}>Nickname</label>
                    <input
                        id="primeiro-acesso-nickname"
                        type="text"
                        className={inputBase}
                        style={{ clipPath: AGGRESSIVE_CLIP }}
                        value={profile.nickname}
                        onChange={(event) => updateProfileField('nickname', event.target.value)}
                        placeholder="EX.: MANAGER_ALPHA"
                    />
                </div>
            );
        }

        if (step === 3) {
            return (
                <div className={fieldBox}>
                    <label htmlFor="primeiro-acesso-whatsapp" className={labelBase}>WhatsApp</label>
                    <input
                        id="primeiro-acesso-whatsapp"
                        type="tel"
                        className={inputBase}
                        style={{ clipPath: AGGRESSIVE_CLIP }}
                        value={profile.whatsapp}
                        onChange={(event) => updateProfileField('whatsapp', event.target.value)}
                        placeholder="(11) 99999-9999"
                    />
                </div>
            );
        }

        if (step === 4) {
            return (
                <div className="space-y-4">
                    <div className={fieldBox}>
                        <label htmlFor="primeiro-acesso-plataforma" className={labelBase}>Plataforma</label>
                        <select
                            id="primeiro-acesso-plataforma"
                            className={inputBase}
                            style={{ clipPath: AGGRESSIVE_CLIP }}
                            value={profile.plataforma_id}
                            onChange={(event) => updateProfileField('plataforma_id', event.target.value)}
                        >
                            <option value="">Selecione a plataforma</option>
                            {(options.plataformas ?? []).map((plataforma) => (
                                <option key={plataforma.id} value={plataforma.id}>{plataforma.nome}</option>
                            ))}
                        </select>
                    </div>

                    <div className={fieldBox}>
                        <label htmlFor="primeiro-acesso-geracao" className={labelBase}>Geração</label>
                        <select
                            id="primeiro-acesso-geracao"
                            className={inputBase}
                            style={{ clipPath: AGGRESSIVE_CLIP }}
                            value={profile.geracao_id}
                            onChange={(event) => updateProfileField('geracao_id', event.target.value)}
                        >
                            <option value="">Selecione a geração</option>
                            {(options.geracoes ?? []).map((geracao) => (
                                <option key={geracao.id} value={geracao.id}>{geracao.nome}</option>
                            ))}
                        </select>
                    </div>
                </div>
            );
        }

        if (step === 5) {
            return (
                <div className={fieldBox}>
                    <label htmlFor="primeiro-acesso-jogo" className={labelBase}>Jogo</label>
                    <select
                        id="primeiro-acesso-jogo"
                        className={inputBase}
                        style={{ clipPath: AGGRESSIVE_CLIP }}
                        value={profile.jogo_id}
                        onChange={(event) => updateProfileField('jogo_id', event.target.value)}
                    >
                        <option value="">Selecione o jogo</option>
                        {(options.jogos ?? []).map((jogo) => (
                            <option key={jogo.id} value={jogo.id}>{jogo.nome}</option>
                        ))}
                    </select>
                </div>
            );
        }

        return (
            <div className="space-y-4">
                {entries.map((entry, index) => (
                    <article
                        key={`entry-${index}`}
                        className="bg-[#121212] border border-[#FFD700]/25 p-4 space-y-3"
                        style={{ clipPath: AGGRESSIVE_CLIP }}
                    >
                        <div className="flex items-center justify-between">
                            <p className="text-[10px] font-black text-[#FFD700] uppercase italic tracking-[0.15em]">
                                Horário {index + 1}
                            </p>
                            <LegacyButton
                                variant="outline"
                                className="!px-3 !py-2"
                                disabled={entries.length === 1}
                                onClick={() => setEntries((previous) => previous.filter((_, current) => current !== index))}
                            >
                                Remover
                            </LegacyButton>
                        </div>

                        <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <select
                                className="bg-[#1E1E1E] border border-[#FFD700]/30 text-white px-3 py-2 text-sm font-bold outline-none"
                                style={{ clipPath: AGGRESSIVE_CLIP }}
                                value={entry.dia_semana}
                                onChange={(event) => updateEntryField(index, 'dia_semana', Number(event.target.value))}
                            >
                                {DAY_OPTIONS.map((day) => (
                                    <option key={day.value} value={day.value}>{day.label}</option>
                                ))}
                            </select>

                            <input
                                type="time"
                                className="bg-[#1E1E1E] border border-[#FFD700]/30 text-white px-3 py-2 text-sm font-bold outline-none"
                                style={{ clipPath: AGGRESSIVE_CLIP }}
                                value={entry.hora_inicio || ''}
                                onChange={(event) => updateEntryField(index, 'hora_inicio', event.target.value)}
                            />

                            <input
                                type="time"
                                className="bg-[#1E1E1E] border border-[#FFD700]/30 text-white px-3 py-2 text-sm font-bold outline-none"
                                style={{ clipPath: AGGRESSIVE_CLIP }}
                                value={entry.hora_fim || ''}
                                onChange={(event) => updateEntryField(index, 'hora_fim', event.target.value)}
                            />
                        </div>
                    </article>
                ))}

                <LegacyButton
                    variant="outline"
                    className="w-full"
                    onClick={() => setEntries((previous) => [...previous, defaultEntry()])}
                >
                    + Adicionar horário
                </LegacyButton>
            </div>
        );
    };

    return (
        <div className="min-h-screen bg-[#121212] px-6 py-20 relative overflow-hidden">
            <div className="absolute top-0 left-0 w-full h-full opacity-[0.03] pointer-events-none" style={{ backgroundImage: 'repeating-linear-gradient(45deg, rgba(255,215,0,0.35) 0, rgba(255,215,0,0.35) 1px, transparent 0, transparent 10px)' }} />
            <div className="absolute -top-20 -right-20 w-64 h-64 bg-[#FFD700] blur-[120px] opacity-10" />

            <div className="fixed top-0 left-0 w-full h-1 bg-white/5 z-[100]">
                <div className="h-full bg-[#FFD700] shadow-[0_0_15px_#FFD700] transition-all duration-500" style={{ width: `${progress}%` }} />
            </div>

            <div className="max-w-2xl mx-auto w-full relative z-10 space-y-6">
                <header className="space-y-3">
                    <p className="text-[10px] text-[#FFD700] font-black uppercase italic tracking-[0.35em]">Legacy XI</p>
                    <h1 className="text-4xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">
                        Primeiro acesso
                    </h1>
                    <p className="text-[10px] text-white/40 font-bold uppercase italic tracking-[0.14em]">
                        Complete seu perfil por etapas para desbloquear o hub legacy
                    </p>
                </header>

                <section className="bg-[#1E1E1E] border-l-[6px] border-[#FFD700] p-6 md:p-8 space-y-6" style={{ clipPath: AGGRESSIVE_CLIP }}>
                    <div className="flex flex-wrap gap-2">
                        {STEP_KEYS.map((key, index) => {
                            const done = Boolean(status?.steps?.[key]);
                            const current = step === index + 1;

                            return (
                                <span
                                    key={key}
                                    className={`px-3 py-1 text-[8px] font-black uppercase italic tracking-[0.18em] border ${current ? 'bg-[#FFD700] text-[#121212] border-[#FFD700]' : done ? 'bg-[#008000] text-white border-[#008000]' : 'bg-[#121212] text-white/50 border-white/20'}`}
                                    style={{ clipPath: AGGRESSIVE_CLIP }}
                                >
                                    {index + 1}
                                </span>
                            );
                        })}
                    </div>

                    <div className="space-y-2">
                        <h2 className="text-2xl font-black italic uppercase font-heading text-white tracking-tight">
                            {stepInfo?.title}
                        </h2>
                        <p className="text-[10px] text-[#FFD700] font-bold uppercase italic tracking-[0.16em]">
                            {stepInfo?.subtitle}
                        </p>
                    </div>

                    {renderStepContent()}

                    {error ? (
                        <div className="bg-[#B22222]/25 border border-[#B22222] p-3 text-[10px] font-black uppercase italic tracking-[0.13em]" style={{ clipPath: AGGRESSIVE_CLIP }}>
                            {error}
                        </div>
                    ) : null}

                    {message ? (
                        <div className="bg-[#FFD700]/12 border border-[#FFD700]/35 p-3 text-[10px] font-black uppercase italic tracking-[0.13em] text-[#FFD700]" style={{ clipPath: AGGRESSIVE_CLIP }}>
                            {message}
                        </div>
                    ) : null}

                    <div className="flex flex-col sm:flex-row gap-3">
                        <LegacyButton variant="outline" onClick={goBack} disabled={step === 1 || saving} className="w-full">
                            Voltar
                        </LegacyButton>
                        <LegacyButton onClick={onSaveStep} disabled={saving} className="w-full">
                            {saving ? 'Salvando...' : step === TOTAL_STEPS ? 'Finalizar' : 'Salvar e avançar'}
                        </LegacyButton>
                    </div>
                </section>
            </div>
        </div>
    );
}
