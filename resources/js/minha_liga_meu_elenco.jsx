import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import MeuElenco from './pages/MeuElenco';

const rootElement = document.getElementById('minha-liga-meu-elenco-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <MeuElenco />
        </React.StrictMode>,
    );
}
