import './kiosk-settings';
import './brand-scene';
import './password-fields';
import './kiosk-studio';
import './tabs';
import './play-link';

// Panel sidebar on small screens: any [data-sidebar-toggle] opens/closes it,
// and Escape closes it.
document.addEventListener('click', (event) => {
    if (event.target.closest('[data-sidebar-toggle]')) {
        document.documentElement.classList.toggle('sidebar-open');
    }
});

// Forms with data-confirm ask before submitting (used for deletes).
document.addEventListener('submit', (event) => {
    const message = event.target.dataset.confirm;

    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

// Menus built on <details data-menu> (the user's menu in the top bar) close on
// a click anywhere else.
document.addEventListener('click', (event) => {
    document.querySelectorAll('details[data-menu][open]').forEach((menu) => {
        if (! menu.contains(event.target)) menu.open = false;
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.documentElement.classList.remove('sidebar-open');
        document.querySelectorAll('details[data-menu][open]').forEach((menu) => {
            menu.open = false;
            menu.querySelector('summary').focus();
        });
    }
});
