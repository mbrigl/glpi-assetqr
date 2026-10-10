/**
 * -------------------------------------------------------------------------
 * Theme Switch plugin for GLPI
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2026 by Markus Brigl.
 * @license   GPLv3+ https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/mbrigl/glpi-themeswitch
 * -------------------------------------------------------------------------
 */

/* global $, glpi_toast_error */

(function () {
    const meta = document.querySelector('meta[name="glpi-plugin-themeswitch"]');
    if (meta === null) {
        return;
    }

    const config = JSON.parse(meta.content);
    const media  = window.matchMedia('(prefers-color-scheme: dark)');
    const icons  = {
        light:  'ti-sun',
        dark:   'ti-moon',
        system: 'ti-device-desktop',
    };

    // null = the user never used the switch, GLPI's own palette preference applies
    let mode = config.mode;

    const getSystemScheme = () => (media.matches ? 'dark' : 'light');

    // Tell the server the OS preference, so the "system" mode is rendered right on the next page load
    const storeSystemScheme = () => {
        document.cookie = `${config.cookie}=${getSystemScheme()}; path=/; max-age=31536000; SameSite=Lax`;
    };

    const applyTheme = () => {
        if (mode === null) {
            return;
        }
        const palette = config.palettes[mode === 'system' ? getSystemScheme() : mode];
        document.documentElement.setAttribute('data-glpi-theme', palette.key);
        document.documentElement.setAttribute('data-glpi-theme-dark', palette.dark ? '1' : '0');
    };

    // GLPI renders the user menu twice (desktop and mobile header), so every switch is kept in sync
    const renderSwitches = () => {
        document.querySelectorAll('button[data-themeswitch-mode]').forEach((button) => {
            const active = button.dataset.themeswitchMode === mode;
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    };

    const setMode = (new_mode) => {
        const previous = mode;
        mode = new_mode;
        applyTheme();
        renderSwitches();

        $.post(config.url, {mode: mode}).fail(() => {
            mode = previous;
            if (mode === null) {
                window.location.reload();
                return;
            }
            applyTheme();
            renderSwitches();
            glpi_toast_error(config.labels.error);
        });
    };

    const buildSwitch = (menu) => {
        const container = document.createElement('div');
        container.className = 'dropdown-item plugin-themeswitch';

        const icon = document.createElement('i');
        icon.className = 'ti ti-sun-moon';
        container.append(icon, document.createTextNode(config.labels.title));

        const group = document.createElement('div');
        group.className = 'btn-group';
        group.setAttribute('role', 'group');
        group.setAttribute('aria-label', config.labels.title);

        Object.keys(icons).forEach((key) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-outline-secondary';
            button.title = config.labels[key];
            button.setAttribute('aria-label', config.labels[key]);
            button.dataset.themeswitchMode = key;
            button.innerHTML = `<i class="ti ${icons[key]} m-0"></i>`;
            group.append(button);
        });
        container.append(group);

        group.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-themeswitch-mode]');
            if (button === null) {
                return;
            }
            // keep the user menu open
            event.stopPropagation();
            setMode(button.dataset.themeswitchMode);
        });

        // Place the switch next to the language selector
        const language = menu.querySelector('.ti-language')?.closest('.dropdown-item');
        if (language) {
            language.after(container);
        } else {
            menu.append(container);
        }
    };

    const buildSwitches = () => {
        document.querySelectorAll('[data-testid="user-menu-dropdown"]').forEach(buildSwitch);
        renderSwitches();
    };

    storeSystemScheme();
    applyTheme();

    media.addEventListener('change', () => {
        storeSystemScheme();
        if (mode === 'system') {
            applyTheme();
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', buildSwitches);
    } else {
        buildSwitches();
    }
})();
