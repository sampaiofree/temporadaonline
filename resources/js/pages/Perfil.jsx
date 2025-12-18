import { useEffect, useState } from 'react';
import Navbar from '../components/app_publico/Navbar';
import DashboardButton from '../components/app_publico/DashboardButton';
import backgroundDefault from '../../../storage/app/public/app/background/fundopadrao.jpg';
import backgroundVertical from '../../../storage/app/public/app/background/fundopadrao.jpg';

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

export default function Perfil() {
    const initialPlayer = window.__PLAYER__ ?? {};
    const platformOptions = window.__PLATAFORMAS__ ?? [];
    const gameOptions = window.__JOGOS__ ?? [];
    const generationOptions = window.__GERACOES__ ?? [];
    const [playerData, setPlayerData] = useState(initialPlayer);
    const [formData, setFormData] = useState(() => mapPlayerToForm(initialPlayer));
    const [isEditing, setIsEditing] = useState(false);
    const [errors, setErrors] = useState({});
    const [statusMessage, setStatusMessage] = useState('');
    const [statusVariant, setStatusVariant] = useState('');
    const [submitting, setSubmitting] = useState(false);

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
    const backgroundStyles = {
        '--mco-cover': `url(${backgroundDefault})`,
        '--mco-cover-mobile': `url(${backgroundVertical})`,
    };

    return (
        <main className="mco-screen" style={backgroundStyles} aria-label="Perfil do jogador">
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
            <Navbar active="home" />
        </main>
    );
}
