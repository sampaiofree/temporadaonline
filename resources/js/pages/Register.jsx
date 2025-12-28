import { useState } from 'react';

const REGISTER_FIELDS = [
    {
        id: 'register-nome',
        name: 'nome',
        label: 'Nome',
        type: 'text',
        placeholder: 'Digite seu nome completo',
        autoComplete: 'name',
        required: true,
    },
    {
        id: 'register-email',
        name: 'email',
        label: 'Email',
        type: 'email',
        placeholder: 'Digite seu melhor email',
        autoComplete: 'email',
        required: true,
    },
    {
        id: 'register-whatsapp',
        name: 'whatsapp',
        label: 'WhatsApp',
        type: 'text',
        placeholder: 'Informe seu WhatsApp (apenas números)',
        autoComplete: 'tel',
        inputMode: 'numeric',
        required: true,
    },
    {
        id: 'register-password',
        name: 'password',
        label: 'Senha',
        type: 'password',
        placeholder: 'Crie uma senha forte',
        autoComplete: 'new-password',
        required: true,
    },
    {
        id: 'register-password-confirmation',
        name: 'password_confirmation',
        label: 'Confirmar Senha',
        type: 'password',
        placeholder: 'Repita sua senha',
        autoComplete: 'new-password',
        required: true,
    },
];

export default function Register() {
    const http = window.axios;
    const [formData, setFormData] = useState({
        nome: '',
        email: '',
        whatsapp: '',
        password: '',
        password_confirmation: '',
    });
    const [errors, setErrors] = useState({});
    const [statusMessage, setStatusMessage] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const handleChange = (event) => {
        const { name, value } = event.target;

        setFormData((previous) => ({
            ...previous,
            [name]: value,
        }));
    };

    const handleSubmit = async (event) => {
        event.preventDefault();

        if (!http) {
            setStatusMessage('Cliente HTTP não inicializado.');
            return;
        }

        setSubmitting(true);
        setErrors({});
        setStatusMessage('');

        try {
            const response = await http.post(
                '/register',
                { ...formData },
                { headers: { Accept: 'application/json' } },
            );

            window.location.href = response?.data?.redirect ?? '/dashboard';
        } catch (error) {
            if (error.response?.status === 422) {
                setErrors(error.response.data.errors ?? {});
            } else {
                setStatusMessage(
                    error.response?.data?.message || 'Não foi possível concluir o cadastro. Tente novamente.',
                );
            }
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <main className="mco-screen register-screen" aria-label="Cadastro de jogador">
            <section className="auth-card" aria-labelledby="register-title">
                <header className="auth-header">
                    <p className="auth-kicker">Operador novo na área</p>
                    <h1 id="register-title" className="auth-title register-title">
                        Cadastre-se
                    </h1>
                    <p className="auth-description">
                        Ative sua conta para participar das ligas, acompanhar seus jogos e dominar o ranking.
                    </p>
                </header>
                <form className="auth-form" aria-label="Formulário de cadastro" autoComplete="off" onSubmit={handleSubmit}>
                    {REGISTER_FIELDS.map((field) => {
                        const { id, name, label, type, placeholder, autoComplete, inputMode } = field;
                        const fieldError = errors[name]?.[0];

                        return (
                            <div key={id} className="auth-field">
                                <label className="auth-label" htmlFor={id}>
                                    {label}
                                </label>
                                <input
                                    className={`auth-input${fieldError ? ' has-error' : ''}`}
                                    id={id}
                                    name={name}
                                    type={type}
                                    placeholder={placeholder}
                                    autoComplete={autoComplete}
                                    inputMode={inputMode}
                                    required={field.required}
                                    minLength={type === 'password' ? 6 : undefined}
                                    value={formData[name]}
                                    onChange={handleChange}
                                />
                                {fieldError && <p className="auth-error">{fieldError}</p>}
                            </div>
                        );
                    })}
                    {statusMessage && <p className="auth-error">{statusMessage}</p>}
                    <button type="submit" className="btn-primary auth-submit" disabled={submitting} aria-busy={submitting}>
                        {submitting ? 'Enviando...' : 'Criar conta'}
                    </button>
                </form>
                <p className="auth-note">
                    Já faz parte da tropa?{' '}
                    <a className="auth-link" href="/login">
                        Entrar
                    </a>
                </p>
            </section>
        </main>
    );
}
