import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import LigaPartidaFinalizar from './pages/LigaPartidaFinalizar';

const rootElement = document.getElementById('liga-partida-finalizar-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <LigaPartidaFinalizar />
        </React.StrictMode>,
    );
}
