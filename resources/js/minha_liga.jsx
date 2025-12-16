import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import MinhaLiga from './pages/MinhaLiga';

const rootElement = document.getElementById('minha-liga-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <MinhaLiga />
        </React.StrictMode>,
    );
}
