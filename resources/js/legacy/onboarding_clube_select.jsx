import React from 'react';
import { createRoot } from 'react-dom/client';
import '../bootstrap';
import OnboardingClubeSelect from '../pages/legacy/OnboardingClubeSelect';

const rootElement = document.getElementById('legacy-onboarding-clube-select-app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <OnboardingClubeSelect />
        </React.StrictMode>,
    );
}
