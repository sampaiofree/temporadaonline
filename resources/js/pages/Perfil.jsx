import { useEffect, useState } from 'react';
import Navbar from '../components/app_publico/Navbar';
import DashboardButton from '../components/app_publico/DashboardButton';
import { useMemo } from 'react';
import Alert from '../components/app_publico/Alert';

const EDIT_ICON_PATHS = [
    'M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z',
    'M20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0L13.27 7.35l3.75 3.75 3.69-3.69z',
];

const TEXT_FIELDS = [
    {
        name: 'nome',
        label: 'Nome',
        placeholder: 'Informe seu nome completo',
        required: true,
        autoComplete: 'name',
    },
    {
        name: 'nickname',
        label: 'Nickname',
        placeholder: 'Exemplo: MC_PRO',
        required: false,
        autoComplete: 'nickname',
    },
    {
        name: 'email',
        label: 'Email',
        placeholder: 'usuario@mco.gg',
        required: true,
        type: 'email',
        autoComplete: 'email',
    },
];

const getInitials = (name) => {
    if (!name) return '';
    const parts = name.split(' ').filter(Boolean);
    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }
    return `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase();
};

const mapPlayerToForm = (player) => ({
    nome: player?.nome ?? '',
    nickname: player?.nickname ?? '',
    email: player?.email ?? '',
    geracao_id: player?.geracao_id ?? '',
    plataforma_id: player?.plataforma_id ?? '',
    jogo_id: player?.jogo_id ?? '',
});

const DAY_OPTIONS = [
    { value: 0, label: 'Domingo' },
    { value: 1, label: 'Segunda' },
    { value: 2, label: 'Terça' },
    { value: 3, label: 'Quarta' },
    { value: 4, label: 'Quinta' },
    { value: 5, label: 'Sexta' },
    { value: 6, label: 'Sábado' },
];

export default function Perfil() {
    const initialPlayer = window.__PLAYER__ ?? {};
    const platformOptions = window.__PLATAFORMAS__ ?? [];
    const gameOptions = window.__JOGOS__ ?? [];
    const generationOptions = window.__GERACOES__ ?? [];
    const appContext = window.__APP_CONTEXT__ ?? {};
    const preferredTimezone =
        appContext?.liga?.timezone ||
        Intl.DateTimeFormat().resolvedOptions().timeZone ||
        'UTC';

    const [playerData, setPlayerData] = useState(initialPlayer);
    const [formData, setFormData] = useState(() => mapPlayerToForm(initialPlayer));
    const [isEditing, setIsEditing] = useState(false);
    const [errors, setErrors] = useState({});
    const [statusMessage, setStatusMessage] = useState('');
    const [statusVariant, setStatusVariant] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [activeTab, setActiveTab] = useState('perfil'); // perfil | horarios

    // Disponibilidades
    const [availability, setAvailability] = useState([]);
    const [availabilityMessage, setAvailabilityMessage] = useState('');
    const [availabilityError, setAvailabilityError] = useState('');
    const [availabilityForm, setAvailabilityForm] = useState({
        dia_semana: '',
        hora_inicio: '',
        hora_fim: '',
    });

    useEffect(() => {
        if (!isEditing) {
            setFormData(mapPlayerToForm(playerData));
        }
    }, [playerData, isEditing]);

    const handleEditStart = () => {
        setIsEditing(true);
        setStatusMessage('');
        setStatusVariant('');
        setErrors({});
        setFormData(mapPlayerToForm(playerData));
    };

    const handleCancel = () => {
        setIsEditing(false);
        setStatusMessage('');
        setStatusVariant('');
        setErrors({});
        setFormData(mapPlayerToForm(playerData));
    };

    const handleChange = (event) => {
        const { name, value } = event.target;
        setFormData((previous) => ({
            ...previous,
            [name]: value,
        }));
    };

    const handleSubmit = async (event) => {
        event.preventDefault();

        setSubmitting(true);
        setErrors({});
        setStatusMessage('');
        setStatusVariant('');

        try {
            const { data } = await window.axios.put('/perfil', formData, {
                headers: { Accept: 'application/json' },
            });
            const updatedPlayer = data.player ?? { ...playerData, ...formData };
            setPlayerData(updatedPlayer);
            setStatusMessage(data.message ?? 'Perfil atualizado com sucesso.');
            setStatusVariant('success');
            setIsEditing(false);
        } catch (error) {
            if (error.response?.status === 422) {
                setErrors(error.response.data.errors ?? {});
            } else {
                setStatusMessage('Não foi possível atualizar o perfil. Tente novamente.');
                setStatusVariant('error');
            }
        } finally {
            setSubmitting(false);
        }
    };

    const displayName = isEditing ? formData.nome || playerData.nome : playerData.nome;
    const displayNickname = isEditing ? formData.nickname || playerData.nickname : playerData.nickname;
    const platformLabel = [playerData.plataforma, playerData.geracao].filter(Boolean).join(' · ') || 'Plataforma não informada';
    const gameLabel = playerData.jogo || 'Jogo não informado';
    const emailLabel = playerData.email || 'Email não informado';

    const profileFields = [
        { label: 'Nome', value: displayName || 'Nome não informado' },
        { label: 'Nickname', value: displayNickname ? `#${displayNickname}` : 'Nickname não informado' },
        { label: 'Email', value: emailLabel },
        { label: 'Plataforma & Geração', value: platformLabel },
        { label: 'Jogo', value: gameLabel },
    ];

    const initials = getInitials(displayName);

    const groupedAvailability = useMemo(() => {
        const groups = {};
        availability.forEach((item) => {
            const day = Number(item.dia_semana);
            if (!groups[day]) {
                groups[day] = [];
            }
            groups[day].push(item);
        });

        Object.keys(groups).forEach((day) => {
            groups[day].sort((a, b) => a.hora_inicio.localeCompare(b.hora_inicio));
        });

        return groups;
    }, [availability]);

    const fetchAvailability = async () => {
        try {
            const { data } = await window.axios.get('/api/me/disponibilidades');
            setAvailability(Array.isArray(data) ? data : []);
        } catch (error) {
            setAvailabilityError('Não foi possível carregar suas disponibilidades.');
        }
    };

    useEffect(() => {
        fetchAvailability();
    }, []);

    const handleAvailabilityChange = (event) => {
        const { name, value } = event.target;
        setAvailabilityForm((prev) => ({ ...prev, [name]: value }));
    };

    const handleAvailabilitySubmit = async (event) => {
        event.preventDefault();
        setAvailabilityMessage('');
        setAvailabilityError('');

        if (!availabilityForm.dia_semana || !availabilityForm.hora_inicio || !availabilityForm.hora_fim) {
            setAvailabilityError('Preencha dia, início e fim.');
            return;
        }

        try {
            await window.axios.post('/api/me/disponibilidades', {
                dia_semana: Number(availabilityForm.dia_semana),
                hora_inicio: availabilityForm.hora_inicio,
                hora_fim: availabilityForm.hora_fim,
            });
            setAvailabilityMessage('Horário salvo');
            setAvailabilityForm({
                dia_semana: '',
                hora_inicio: '',
                hora_fim: '',
            });
            fetchAvailability();
        } catch (error) {
            const msg =
                error.response?.data?.message ??
                error.response?.data?.errors?.hora_inicio?.[0] ??
                'Não foi possível salvar. Verifique conflitos.';
            setAvailabilityError(msg);
        }
    };

    const handleAvailabilityDelete = async (id) => {
        setAvailabilityMessage('');
        setAvailabilityError('');

        try {
            await window.axios.delete(`/api/me/disponibilidades/${id}`);
            setAvailabilityMessage('Horário removido');
            fetchAvailability();
        } catch (error) {
            const msg = error.response?.data?.message ?? 'Não foi possível remover este horário.';
            setAvailabilityError(msg);
        }
    };

    return (
        <main className="mco-screen" aria-label="Perfil do jogador">
            <div className="profile-tabs" role="tablist" aria-label="Seções do perfil">
                <button
                    type="button"
                    className={`profile-tab${activeTab === 'perfil' ? ' active' : ''}`}
                    onClick={() => setActiveTab('perfil')}
                    role="tab"
                    aria-selected={activeTab === 'perfil'}
                >
                    Perfil
                </button>
                <button
                    type="button"
                    className={`profile-tab${activeTab === 'horarios' ? ' active' : ''}`}
                    onClick={() => setActiveTab('horarios')}
                    role="tab"
                    aria-selected={activeTab === 'horarios'}
                >
                    Horários
                </button>
            </div>

            {activeTab === 'perfil' && (
            <section className="profile-panel" aria-label="Dados do jogador">
                <div className="profile-avatar" aria-hidden="true">
                    <span>{initials || '??'}</span>
                </div>
                <p className="profile-name">{displayName || 'Jogador'}</p>
                <p className="profile-nickname">#{displayNickname || 'nickname'}</p>
                {isEditing ? (
                    <form className="profile-form" onSubmit={handleSubmit} noValidate>
                        {TEXT_FIELDS.map((field) => (
                            <label key={field.name} className="profile-form-field" htmlFor={`perfil-${field.name}`}>
                                <span className="profile-label">{field.label}</span>
                                <input
                                    className="profile-input"
                                    id={`perfil-${field.name}`}
                                    name={field.name}
                                    type={field.type ?? 'text'}
                                    placeholder={field.placeholder}
                                    required={field.required}
                                    value={formData[field.name]}
                                    onChange={handleChange}
                                    autoComplete={field.autoComplete}
                                />
                                {errors[field.name]?.[0] && (
                                    <span className="profile-error">{errors[field.name][0]}</span>
                                )}
                            </label>
                        ))}
                        <label className="profile-form-field" htmlFor="perfil-geracao">
                            <span className="profile-label">Geração</span>
                            <select
                                className="profile-input"
                                id="perfil-geracao"
                                name="geracao_id"
                                value={formData.geracao_id}
                                onChange={handleChange}
                            >
                                <option value="">Selecione a geração</option>
                                {generationOptions.map((option) => (
                                    <option key={option.id} value={option.id}>
                                        {option.nome}
                                    </option>
                                ))}
                            </select>
                            {errors.geracao_id?.[0] && (
                                <span className="profile-error">{errors.geracao_id[0]}</span>
                            )}
                        </label>
                        <label className="profile-form-field" htmlFor="perfil-plataforma">
                            <span className="profile-label">Plataforma</span>
                            <select
                                className="profile-input"
                                id="perfil-plataforma"
                                name="plataforma_id"
                                value={formData.plataforma_id}
                                onChange={handleChange}
                            >
                                <option value="">Escolha a plataforma</option>
                                {platformOptions.map((option) => (
                                    <option key={option.id} value={option.id}>
                                        {option.nome}
                                    </option>
                                ))}
                            </select>
                            {errors.plataforma_id?.[0] && (
                                <span className="profile-error">{errors.plataforma_id[0]}</span>
                            )}
                        </label>
                        <label className="profile-form-field" htmlFor="perfil-jogo">
                            <span className="profile-label">Jogo</span>
                            <select
                                className="profile-input"
                                id="perfil-jogo"
                                name="jogo_id"
                                value={formData.jogo_id}
                                onChange={handleChange}
                            >
                                <option value="">Escolha um jogo</option>
                                {gameOptions.map((option) => (
                                    <option key={option.id} value={option.id}>
                                        {option.nome}
                                    </option>
                                ))}
                            </select>
                            {errors.jogo_id?.[0] && (
                                <span className="profile-error">{errors.jogo_id[0]}</span>
                            )}
                        </label>
                        {statusMessage && (
                            <p className={`profile-status${statusVariant === 'error' ? ' profile-status--error' : ''}`}>
                                {statusMessage}
                            </p>
                        )}
                        <div className="profile-form-actions">
                            <button className="btn-primary" type="submit" disabled={submitting}>
                                {submitting ? 'Salvando...' : 'Salvar alterações'}
                            </button>
                            <button className="btn-outline" type="button" onClick={handleCancel} disabled={submitting}>
                                Cancelar
                            </button>
                        </div>
                    </form>
                ) : (
                    <>
                        <div className="profile-details">
                            {profileFields.map((field) => (
                                <article key={field.label} className="profile-field">
                                    <span className="profile-label">{field.label}</span>
                                    <span className="profile-value">{field.value}</span>
                                </article>
                            ))}
                        </div>
                        {statusMessage && (
                            <p className={`profile-status${statusVariant === 'error' ? ' profile-status--error' : ''}`}>
                                {statusMessage}
                            </p>
                        )}
                    </>
                )}
                <div className="profile-footer">
                    {!isEditing && (
                        <DashboardButton label="Editar Perfil" paths={EDIT_ICON_PATHS} onClick={handleEditStart} />
                    )}
                </div>
            </section>
            )}

            {activeTab === 'horarios' && (
                <section className="availability-panel" aria-label="Disponibilidade do jogador">
                    <div className="availability-header">
                        <div>
                            <p className="availability-eyebrow">Disponibilidade</p>
                            <h2>Adicione seus horários</h2>
                            <p className="availability-caption">
                                Fuso: {preferredTimezone || 'UTC'}
                            </p>
                        </div>
                    </div>

                    <form className="availability-form" onSubmit={handleAvailabilitySubmit}>
                        <label className="availability-field">
                            <span>Dia</span>
                            <select
                                name="dia_semana"
                                value={availabilityForm.dia_semana}
                                onChange={handleAvailabilityChange}
                                required
                            >
                                <option value="">Selecione</option>
                                {DAY_OPTIONS.map((day) => (
                                    <option key={day.value} value={day.value}>
                                        {day.label}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label className="availability-field">
                            <span>Início</span>
                            <input
                                type="time"
                                name="hora_inicio"
                                value={availabilityForm.hora_inicio}
                                onChange={handleAvailabilityChange}
                                required
                            />
                        </label>
                        <label className="availability-field">
                            <span>Fim</span>
                            <input
                                type="time"
                                name="hora_fim"
                                value={availabilityForm.hora_fim}
                                onChange={handleAvailabilityChange}
                                required
                            />
                        </label>
                        <button className="btn-primary" type="submit">Adicionar</button>
                    </form>

                    {availabilityMessage && (
                        <Alert
                            variant="success"
                            title="Pronto"
                            onClose={() => setAvailabilityMessage('')}
                        >
                            {availabilityMessage}
                        </Alert>
                    )}
                    {availabilityError && (
                        <Alert
                            variant="danger"
                            title="Erro"
                            onClose={() => setAvailabilityError('')}
                        >
                            {availabilityError}
                        </Alert>
                    )}

                    <div className="availability-list">
                        {DAY_OPTIONS.map((day) => {
                            const entries = groupedAvailability[day.value] || [];
                            if (entries.length === 0) {
                                return null;
                            }

                            return (
                                <div key={day.value} className="availability-day">
                                    <p className="availability-day-label">{day.label}</p>
                                    <ul>
                                        {entries.map((item) => (
                                            <li key={item.id} className="availability-item">
                                                <span>{item.hora_inicio} – {item.hora_fim}</span>
                                                <button
                                                    type="button"
                                                    className="btn-outline"
                                                    onClick={() => handleAvailabilityDelete(item.id)}
                                                >
                                                    Excluir
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            );
                        })}
                        {Object.keys(groupedAvailability).length === 0 && (
                            <p className="availability-empty">Nenhum horário cadastrado.</p>
                        )}
                    </div>
                </section>
            )}
            <Navbar active="home" />
        </main>
    );
}
