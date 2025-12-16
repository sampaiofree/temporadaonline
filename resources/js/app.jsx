import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import Dashboard from './pages/Dashboard';

const rootElement = document.getElementById('app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <Dashboard />
        </React.StrictMode>,
    );
}
