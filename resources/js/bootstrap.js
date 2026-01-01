import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const csrfToken = document.head.querySelector('meta[name="csrf-token"]');

if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
} else {
    console.warn('CSRF token not found: forms may fail to submit.');
}

const LOADER_ID = 'page-loader';
const LOADER_ACTIVE_CLASS = 'is-loading';
const getAppAssets = () => window.__APP_ASSETS__ ?? null;

const ensureLoader = () => {
    if (!document.body || document.getElementById(LOADER_ID)) {
        return;
    }

    const appAssets = getAppAssets();
    const logoUrl = appAssets?.logo_dark_url || appAssets?.logo_padrao_url || null;
    const logoMarkup = logoUrl
        ? `<img class="page-loader-logo" src="${logoUrl}" alt="" aria-hidden="true">`
        : '';

    const loader = document.createElement('div');
    loader.id = LOADER_ID;
    loader.className = 'page-loader';
    loader.setAttribute('aria-hidden', 'true');
    loader.innerHTML = `
        <div class="page-loader-spinner" role="status" aria-live="polite">
            ${logoMarkup}
            <span class="page-loader-text">Carregando...</span>
        </div>
    `;
    document.body.appendChild(loader);
};

const showLoader = () => {
    if (!document.body) return;
    ensureLoader();
    document.body.classList.add(LOADER_ACTIVE_CLASS);
};

const hideLoader = () => {
    if (!document.body) return;
    document.body.classList.remove(LOADER_ACTIVE_CLASS);
};

const navigateWithLoader = (url) => {
    if (!url) return;
    showLoader();
    requestAnimationFrame(() => {
        window.location.href = url;
    });
};

window.MCOPageLoader = {
    show: showLoader,
    hide: hideLoader,
    navigate: navigateWithLoader,
};
window.navigateWithLoader = navigateWithLoader;

const shouldIgnoreLink = (link) => {
    if (!link) return true;
    if (link.dataset.noLoader === 'true') return true;
    if (link.hasAttribute('download')) return true;
    if (link.getAttribute('aria-disabled') === 'true') return true;
    if (link.classList.contains('disabled')) return true;

    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return true;
    if (href.startsWith('mailto:') || href.startsWith('tel:')) return true;

    const target = link.getAttribute('target');
    if (target && target !== '_self') return true;

    return false;
};

const initPageLoader = () => {
    ensureLoader();
    hideLoader();

    document.addEventListener('click', (event) => {
        if (event.button !== 0) return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const link = event.target.closest('a');
        if (!link || shouldIgnoreLink(link)) return;

        setTimeout(() => {
            if (!event.defaultPrevented) {
                showLoader();
            }
        }, 0);
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.dataset.noLoader === 'true') return;

        setTimeout(() => {
            if (!event.defaultPrevented) {
                showLoader();
            }
        }, 0);
    });

    window.addEventListener('pageshow', hideLoader);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPageLoader);
} else {
    initPageLoader();
}
