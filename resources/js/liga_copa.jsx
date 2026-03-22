import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import LigaCopa from './pages/LigaCopa';

const rootElement = document.getElementById('liga-copa-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <LigaCopa />
        </React.StrictMode>,
    );
}
