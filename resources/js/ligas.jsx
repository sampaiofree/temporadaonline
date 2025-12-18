import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import Ligas from './pages/Ligas';

const rootElement = document.getElementById('ligas-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <Ligas />
        </React.StrictMode>,
    );
}
