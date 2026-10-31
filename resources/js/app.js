import './kiosk-settings';
import './project-steps';

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

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.documentElement.classList.remove('sidebar-open');
    }
});
