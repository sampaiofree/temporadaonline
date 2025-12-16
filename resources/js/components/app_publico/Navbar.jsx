import { useEffect, useState } from 'react';

const NAV_ITEMS = [
    {
        id: 'home',
        label: 'INÍCIO',
        href: '#home',
        iconPath: 'M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z',
    },
    {
        id: 'ligas',
        label: 'LIGAS',
        href: '#ligas',
        iconPath:
            'M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94.63 1.5 1.98 2.63 3.61 2.96V19H7v2h10v-2h-4v-3.1c1.63-.33 2.98-1.46 3.61-2.96C19.08 12.63 21 10.55 21 8V7c0-1.1-.9-2-2-2zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z',
    },
    {
        id: 'menu',
        label: 'MENU',
        href: '#menu',
        iconPath: 'M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z',
    },
];

export default function Navbar({ active: controlledActive }) {
    const getHashActive = () => {
        const hash = window.location.hash?.replace('#', '');
        return hash || 'home';
    };

    const [active, setActive] = useState(controlledActive || getHashActive());

    useEffect(() => {
        const handleHashChange = () => setActive(getHashActive());
        window.addEventListener('hashchange', handleHashChange);
        return () => window.removeEventListener('hashchange', handleHashChange);
    }, []);

    useEffect(() => {
        if (controlledActive) {
            setActive(controlledActive);
        }
    }, [controlledActive]);

    return (
        <nav className="mco-navbar" aria-label="Menu principal">
            {NAV_ITEMS.map((item) => {
                const isActive = active === item.id;

                const handleClick = (event) => {
                    event.preventDefault();
                    window.location.hash = item.id;
                    setActive(item.id);
                };

                return (
                    <a
                        key={item.id}
                        href={item.href}
                        className={`nav-item${isActive ? ' active' : ''}`}
                        aria-current={isActive ? 'page' : undefined}
                        onClick={handleClick}
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
