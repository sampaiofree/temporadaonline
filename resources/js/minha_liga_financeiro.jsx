import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import FinanceiroClube from './pages/FinanceiroClube';

const rootElement = document.getElementById('minha-liga-financeiro-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <FinanceiroClube />
        </React.StrictMode>,
    );
}

