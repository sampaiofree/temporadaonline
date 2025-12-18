import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import LigaClubePerfil from './pages/LigaClubePerfil';

const rootElement = document.getElementById('liga-clube-perfil-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <LigaClubePerfil />
        </React.StrictMode>,
    );
}
