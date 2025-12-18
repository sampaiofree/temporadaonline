const VARIANT_CLASS = {
    info: 'alert-info',
    success: 'alert-success',
    warning: 'alert-warning',
    danger: 'alert-danger',
};

const VARIANT_ICON = {
    info: 'ℹ',
    success: '✓',
    warning: '⚠',
    danger: '✕',
};

export default function Alert({ variant = 'info', title, description, children, onClose, floating = false }) {
    const tone = VARIANT_CLASS[variant] ?? VARIANT_CLASS.info;
    const icon = VARIANT_ICON[variant] ?? VARIANT_ICON.info;
    const content = description || children;
    const floatingClass = floating ? 'alert-floating' : '';

    return (
        <div className={`alert ${tone} ${floatingClass}`}>
            <div className="alert-icon" aria-hidden="true">
                {icon}
            </div>
            <div className="alert-body">
                {title && <strong>{title}</strong>}
                {content && <p>{content}</p>}
            </div>
            {onClose && (
                <button type="button" className="alert-close" onClick={onClose} aria-label="Fechar aviso">
                    ×
                </button>
            )}
        </div>
    );
}
