export default function DashboardButton({ label, paths = [], onClick, href }) {
    const handleClick = (event) => {
        if (onClick) {
            onClick(event);
        }

        if (href && !event.defaultPrevented) {
            window.navigateWithLoader(href);
        }
    };

    return (
        <button type="button" className="dashboard-btn" onClick={handleClick}>
            <svg className="btn-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                {paths.map((d, index) => (
                    <path key={index} d={d} />
                ))}
            </svg>
            <span className="btn-text">{label}</span>
            <div className="btn-accent" aria-hidden="true" />
        </button>
    );
}
