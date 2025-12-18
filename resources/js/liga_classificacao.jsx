import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import LigaClassificacao from './pages/LigaClassificacao';

const rootElement = document.getElementById('liga-classificacao-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <LigaClassificacao />
        </React.StrictMode>,
    );
}
