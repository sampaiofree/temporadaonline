import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import Register from './pages/Register';

const rootElement = document.getElementById('register-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <Register />
        </React.StrictMode>,
    );
}
