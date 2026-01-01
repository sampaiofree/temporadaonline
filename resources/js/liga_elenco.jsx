import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import LigaElenco from './pages/LigaElenco';

const rootElement = document.getElementById('liga-elenco-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <LigaElenco />
        </React.StrictMode>,
    );
}
