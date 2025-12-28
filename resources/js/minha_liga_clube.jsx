import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import MinhaLigaClube from './pages/MinhaLigaClube';

const rootElement = document.getElementById('minha-liga-clube-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <MinhaLigaClube />
        </React.StrictMode>,
    );
}
