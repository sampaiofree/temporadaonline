import Navbar from '../components/app_publico/Navbar';
import backgroundDefault from '../../../storage/app/public/app/background/fundopadrao.jpg';
import backgroundVertical from '../../../storage/app/public/app/background/fundopadrao.jpg';

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

    const backgroundStyles = {
        '--mco-cover': `url(${backgroundDefault})`,
        '--mco-cover-mobile': `url(${backgroundVertical})`,
    };

    const handleAction = (href) => {
        if (!href) {
            return;
        }

        window.location.href = href;
    };

    return (
        <main className="mco-screen" style={backgroundStyles} aria-label="Tela inicial do MCO">
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
                                    {item.done ? '✅' : '❌'}
                                </span>
                                <div className="dashboard-checklist-card-text">
                                    <h3>{item.title}</h3>
                                    <p>{item.description}</p>
                                </div>
                            </div>
                            <div className="dashboard-checklist-card-actions">
                                <span
                                    className={`dashboard-checklist-card-chip ${item.done ? 'is-done' : 'is-pending'}`}
                                >
                                    {item.done ? 'Concluído' : 'Pendente'}
                                </span>
                                <button
                                    type="button"
                                    className="btn-primary dashboard-checklist-cta"
                                    onClick={() => handleAction(item.actionHref)}
                                    disabled={item.done}
                                >
                                    {item.actionLabel}
                                </button>
                            </div>
                        </article>
                    ))}
                </section>
            )}
            <Navbar active="home" />
        </main>
    );
}
