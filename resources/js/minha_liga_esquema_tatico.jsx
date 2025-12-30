import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import EsquemaTatico from './pages/EsquemaTatico';

const rootElement = document.getElementById('minha-liga-esquema-tatico-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <EsquemaTatico />
        </React.StrictMode>,
    );
}
