(function () {
    'use strict';
    const Salus = {
        csrf: document.documentElement.dataset.csrf || '',
        escape(value) {
            const node = document.createElement('div');
            node.textContent = value ?? '';
            return node.innerHTML;
        },
        form(extra = {}) {
            const data = new FormData();
            data.append('csrf_token', this.csrf);
            Object.entries(extra).forEach(([key, value]) => data.append(key, value));
            return data;
        },
        async json(url, options = {}) {
            const response = await fetch(url, options);
            let data;
            try { data = await response.json(); }
            catch (_) { throw new Error('El servidor devolvió una respuesta no válida.'); }
            if (response.status === 401) {
                location.href = 'login.php';
                throw new Error('La sesión expiró.');
            }
            if (!response.ok || data?.status === 'error') throw new Error(data?.mensaje || 'No se pudo completar la operación.');
            return data;
        },
        toast(message, type = 'ok') {
            let region = document.querySelector('.toast-region');
            if (!region) {
                region = document.createElement('div');
                region.className = 'toast-region';
                document.body.appendChild(region);
            }
            const item = document.createElement('div');
            item.className = `toast ${type === 'error' ? 'error' : ''}`;
            item.textContent = message;
            region.appendChild(item);
            setTimeout(() => item.remove(), 3500);
        }
    };
    window.Salus = Salus;
    window.escaparHtml = Salus.escape;
    window.formatoGrupoSanguineo = function (texto) {
        const valor = String(texto || '').toUpperCase().trim();
        const grupo = valor.startsWith('AB') ? 'AB' : ['A', 'B', 'O'].find(x => valor.startsWith(x)) || '';
        const signo = valor.includes('POSITIVO') ? '+' : valor.includes('NEGATIVO') ? '-' : '';
        return grupo + signo;
    };

    function iniciarApp() {
        Salus.csrf = document.documentElement.dataset.csrf || Salus.csrf;
        document.addEventListener('input', event => {
            const campo = event.target.closest?.('[data-uppercase]');
            if (!campo || typeof campo.value !== 'string') return;
            const inicio = campo.selectionStart;
            const fin = campo.selectionEnd;
            const mayusculas = campo.value.toLocaleUpperCase('es');
            if (campo.value === mayusculas) return;
            campo.value = mayusculas;
            if (inicio !== null && fin !== null && typeof campo.setSelectionRange === 'function') {
                campo.setSelectionRange(inicio, fin);
            }
        });
        document.addEventListener('change', event => {
            const control = event.target.closest?.('[data-password-toggle]');
            if (!control) return;
            document.querySelectorAll(control.dataset.passwordToggle).forEach(campo => {
                if (campo.matches('[data-password-field]')) {
                    campo.type = control.checked ? 'text' : 'password';
                }
            });
        });
        document.addEventListener('reset', event => {
            setTimeout(() => {
                event.target.querySelectorAll?.('[data-password-field]').forEach(campo => { campo.type = 'password'; });
            }, 0);
        });
        document.querySelectorAll('[data-sidebar-toggle]').forEach(button => {
            button.addEventListener('click', () => document.body.classList.toggle('sidebar-open'));
        });
        document.querySelector('.sidebar-backdrop')?.addEventListener('click', () => document.body.classList.remove('sidebar-open'));
        document.querySelector('[data-theme-toggle]')?.addEventListener('click', async () => {
            try {
                await Salus.json('backend/usuariosDAO.php?q=cambiar_fuente', { method: 'POST', body: Salus.form() });
                document.body.classList.toggle('theme-dark');
            } catch (error) { Salus.toast(error.message, 'error'); }
        });
        document.querySelector('[data-logout]')?.addEventListener('click', async () => {
            try {
                await Salus.json('backend/usuariosDAO.php?q=logout', { method: 'POST', body: Salus.form() });
            } catch (_) { /* La sesión local se abandona igualmente. */ }
            location.href = 'login.php';
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciarApp);
    else iniciarApp();
})();
