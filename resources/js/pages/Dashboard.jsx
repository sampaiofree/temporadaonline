import Navbar from '../components/app_publico/Navbar';

const STATUS_ICONS = {
    done: (
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path
                d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm4.3 6.3-5.1 5.1a1 1 0 0 1-1.4 0l-2.2-2.2a1 1 0 1 1 1.4-1.4l1.5 1.5 4.4-4.4a1 1 0 0 1 1.4 1.4z"
                fill="currentColor"
            />
        </svg>
    ),
    pending: (
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path
                d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm3 13.41L13.41 15 12 13.59 10.59 15 9 13.41 10.41 12 9 10.59 10.59 9 12 10.41 13.41 9 15 10.59 13.59 12z"
                fill="currentColor"
            />
        </svg>
    ),
};

const getChecklistData = () => {
    const raw = window.__CHECKLIST__ ?? {};
    const items = Array.isArray(raw.items) ? raw.items : [];
    return {
        show: Boolean(raw.show) && items.length > 0,
        items,
    };
};

export default function Dashboard() {
    const checklist = getChecklistData();

    const handleAction = (href) => {
        if (!href) {
            return;
        }

        window.location.href = href;
    };

    return (
        <main className="mco-screen" aria-label="Tela inicial do MCO">
            {checklist.show && (
                <section className="dashboard-checklist" aria-label="Checklist de jornada">
                    <header className="dashboard-checklist-header">
                        <h2>Checklist</h2>
                        <p>Complete os próximos passos para estar 100% pronto para jogar.</p>
                    </header>
                    {checklist.items.map((item) => (
                        <article key={item.id} className="dashboard-checklist-card">
                            <div className="dashboard-checklist-card-header">
                                <span
                                    className={`dashboard-checklist-card-status ${item.done ? 'done' : 'pending'}`}
                                    aria-hidden="true"
                                >
                                    {item.done ? STATUS_ICONS.done : STATUS_ICONS.pending}
                                </span>
                                <div className="dashboard-checklist-card-text">
                                    <h3>{item.title}</h3>
                                    <p>{item.description}</p>
                                </div>
                            </div>
                        </article>
                    ))}
                </section>
            )}
            <Navbar active="home" />
        </main>
    );
}
