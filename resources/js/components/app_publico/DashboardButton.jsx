export default function DashboardButton({ label, paths = [], onClick }) {
    return (
        <button type="button" className="dashboard-btn" onClick={onClick}>
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
