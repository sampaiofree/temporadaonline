import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import Perfil from './pages/Perfil';

const rootElement = document.getElementById('perfil-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <Perfil />
        </React.StrictMode>,
    );
}
