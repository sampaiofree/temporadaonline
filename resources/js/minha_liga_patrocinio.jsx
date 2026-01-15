import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import MinhaLigaPatrocinio from './pages/MinhaLigaPatrocinio';

const rootElement = document.getElementById('minha-liga-patrocinio-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <MinhaLigaPatrocinio />
        </React.StrictMode>,
    );
}
