import React from 'react';
import { createRoot } from 'react-dom/client';
import '../bootstrap';
import OnboardingClube from '../pages/OnboardingClube';

const rootElement = document.getElementById('legacy-onboarding-clube-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <OnboardingClube />
        </React.StrictMode>,
    );
}
