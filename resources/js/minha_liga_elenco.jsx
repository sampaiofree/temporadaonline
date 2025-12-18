import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import MinhaLigaElenco from './pages/MinhaLigaElenco';

const rootElement = document.getElementById('minha-liga-elenco-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <MinhaLigaElenco />
        </React.StrictMode>,
    );
}
