import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import LigaMercado from './pages/LigaMercado';

const rootElement = document.getElementById('liga-mercado-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <LigaMercado />
        </React.StrictMode>,
    );
}
