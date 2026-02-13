import React from 'react';
import ReactDOM from 'react-dom/client';
import PrimeiroAcesso from '../pages/legacy/PrimeiroAcesso';

const rootElement = document.getElementById('legacy-primeiro-acesso-app');

if (rootElement) {
    ReactDOM.createRoot(rootElement).render(
        <React.StrictMode>
            <PrimeiroAcesso />
        </React.StrictMode>,
    );
}
