import { useState } from 'react';

const LOGIN_FIELDS = [
    {
        id: 'login-email',
        name: 'email',
        label: 'Email',
        type: 'email',
        placeholder: 'Informe seu email',
        autoComplete: 'email',
    },
    {
        id: 'login-password',
        name: 'password',
        label: 'Senha',
        type: 'password',
        placeholder: 'Digite sua senha',
        autoComplete: 'current-password',
    },
];

export default function Login() {
    const http = window.axios;
    const [formData, setFormData] = useState({
        email: '',
        password: '',
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
                '/login',
                { ...formData },
                { headers: { Accept: 'application/json' } },
            );

            window.location.href = response?.data?.redirect ?? '/dashboard';
        } catch (error) {
            if (error.response?.status === 422) {
                setErrors(error.response.data.errors ?? {});
            } else {
                setStatusMessage(
                    error.response?.data?.message || 'Não foi possível acessar a conta. Tente novamente.',
                );
            }
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <main className="mco-screen" aria-label="Acesso ao painel do jogador">
            <section className="auth-card" aria-labelledby="login-title">
                <header className="auth-header">
                    <p className="auth-kicker">Operador veterano</p>
                    <h1 id="login-title" className="auth-title">
                        Entrar
                    </h1>
                    <p className="auth-description">
                        Conecte-se para continuar acompanhando suas partidas, recompensas e a glória nas ligas oficiais.
                    </p>
                </header>
                <form className="auth-form" aria-label="Formulário de login" autoComplete="off" onSubmit={handleSubmit}>
                    {LOGIN_FIELDS.map((field) => {
                        const { id, name, label, type, placeholder, autoComplete } = field;
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
                                    required
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
                        {submitting ? 'Conectando...' : 'Acessar conta'}
                    </button>
                </form>
                <p className="auth-note">
                    Novo por aqui?{' '}
                    <a className="auth-link" href="/register">
                        Criar conta
                    </a>
                </p>
            </section>
            
        </main>
    );
}
