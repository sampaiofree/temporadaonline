import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import MinhaLigaConquistas from './pages/MinhaLigaConquistas';

const rootElement = document.getElementById('minha-liga-conquistas-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <MinhaLigaConquistas />
        </React.StrictMode>,
    );
}
