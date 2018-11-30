import userLogin from '../../components/security/login-form.js';

export default { enhance };

function enhance() {
    const view = document.querySelector('[data-view="user-login"]');
    const formVariant = new URLSearchParams(new URL(window.location.href).search).get('v') || 'stepped';

    if (view) {
        userLogin.enhanceWithin(view, formVariant);
    }
}
