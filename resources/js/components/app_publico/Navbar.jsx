const GLOBAL_NAV_ITEMS = [
    {
        id: 'home',
        label: 'INÍCIO',
        href: '/dashboard',
        iconPath: 'M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z',
    },
    {
        id: 'ligas',
        label: 'LIGAS',
        href: '/ligas',
        iconPath:
            'M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94.63 1.5 1.98 2.63 3.61 2.96V19H7v2h10v-2h-4v-3.1c1.63-.33 2.98-1.46 3.61-2.96C19.08 12.63 21 10.55 21 8V7c0-1.1-.9-2-2-2zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z',
    },
    {
        id: 'perfil',
        label: 'PERFIL',
        href: '/perfil',
        iconPath: 'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z',
    },
];

const withLigaId = (href, ligaId) => {
    if (!ligaId) {
        return href;
    }

    const separator = href.includes('?') ? '&' : '?';
    return `${href}${separator}liga_id=${ligaId}`;
};

const buildLigaNavItems = (ligaId, clubeId) => [
    {
        id: 'home',
        label: 'INÍCIO',
        href: '/dashboard',
        iconPath: 'M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z',
    },
    {
        id: 'liga',
        label: 'LIGA',
        href: withLigaId('/minha_liga', ligaId),
        iconPath: 'M12 2L4 5v6c0 5 3.8 9.7 8 11 4.2-1.3 8-6 8-11V5l-8-3z'

    },
    {
        id: 'partidas',
        label: 'PARTIDAS',
        href: withLigaId('/liga/partidas', ligaId),
        iconPath: 'M12 3a6 6 0 100 12 6 6 0 000-12zm7 13l2 2-2 2-2-2 2-2z'

    },
    {
        id: 'tabela',
        label: 'TABELA',
        href: withLigaId('/liga/classificacao', ligaId),
        iconPath: 'M7 10h4v10H7V10zm6-4h4v14h-4V6zM1 14h4v6H1v-6z'

    },
    
];

const getAppContext = () => window.__APP_CONTEXT__ ?? { mode: 'global', liga: null, clube: null };

const resolveActiveId = (mode) => {
    const path = window.location.pathname;

    if (mode === 'liga') {
        if (path.startsWith('/liga/mercado')) {
            return 'mercado';
        }
        if (path.startsWith('/liga/partidas')) {
            return 'partidas';
        }
        if (path.startsWith('/liga/classificacao')) {
            return 'tabela';
        }
        if (path.startsWith('/liga/clubes')) {
            return 'clube';
        }
        if (path.startsWith('/minha_liga')) {
            return 'liga';
        }

        return '';
    }

    if (path === '/' || path.startsWith('/dashboard')) {
        return 'home';
    }
    if (path.startsWith('/ligas')) {
        return 'ligas';
    }
    if (path.startsWith('/perfil') || path.startsWith('/profile')) {
        return 'perfil';
    }

    return '';
};

export default function Navbar({ active: controlledActive }) {
    const appContext = getAppContext();
    const isLigaMode = appContext.mode === 'liga';
    const ligaId = appContext.liga?.id ?? null;
    const clubeId = appContext.clube?.id ?? null;
    const items = isLigaMode ? buildLigaNavItems(ligaId, clubeId) : GLOBAL_NAV_ITEMS;

    const resolvedActive = resolveActiveId(isLigaMode ? 'liga' : 'global');
    const shouldUseControlledActive = Boolean(
        controlledActive && items.some((item) => item.id === controlledActive) && !resolvedActive,
    );
    const activeId = shouldUseControlledActive ? controlledActive : resolvedActive;

    return (
        <nav className="mco-navbar" aria-label="Menu principal">
            {items.map((item) => {
                const isActive = activeId === item.id;

                return (
                    <a
                        key={item.id}
                        href={item.href}
                        className={`nav-item${isActive ? ' active' : ''}`}
                        aria-current={isActive ? 'page' : undefined}
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d={item.iconPath} />
                        </svg>
                        {item.label}
                    </a>
                );
            })}
        </nav>
    );
}
