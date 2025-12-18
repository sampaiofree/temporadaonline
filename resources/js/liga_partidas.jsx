import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import LigaPartidas from './pages/LigaPartidas';

const rootElement = document.getElementById('liga-partidas-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <LigaPartidas />
        </React.StrictMode>,
    );
}
