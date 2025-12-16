import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap';
import Login from './pages/Login';

const rootElement = document.getElementById('login-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <Login />
        </React.StrictMode>,
    );
}
