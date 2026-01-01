import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import LigaMercadoPropostas from './pages/LigaMercadoPropostas';

const rootElement = document.getElementById('liga-mercado-propostas-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <LigaMercadoPropostas />
        </React.StrictMode>,
    );
}
