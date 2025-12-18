import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import LigaDashboard from './pages/LigaDashboard';

const rootElement = document.getElementById('liga-dashboard-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <LigaDashboard />
        </React.StrictMode>,
    );
}
