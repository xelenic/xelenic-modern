// Password fields (components/form/input.blade.php): a button to show or hide
// what's typed, a strength meter on new passwords, and a check that the
// confirmation matches.

// The server's minimum (Password::default(), Laravel's default of 8).
const MIN_LENGTH = 8;

// Easy to guess however they're dressed up.
const COMMON = /^(password|passw0rd|qwerty|letmein|welcome|admin|iloveyou|abc123|xelenic|123456|111111|123123)/i;

const LEVELS = [
    null,
    { label: 'Weak', bar: 'bg-red-500', text: 'text-red-600' },
    { label: 'Fair', bar: 'bg-amber-500', text: 'text-amber-600' },
    { label: 'Good', bar: 'bg-sky-500', text: 'text-sky-600' },
    { label: 'Strong', bar: 'bg-emerald-500', text: 'text-emerald-600' },
];

// 1 (weak) to 4 (strong), with the one change that would help most.
function rate(value) {
    if (value.length < MIN_LENGTH) {
        return { score: 1, label: 'Too short', tip: `Use at least ${MIN_LENGTH} characters.` };
    }

    const mixedCase = /[a-z]/.test(value) && /[A-Z]/.test(value);
    const digit = /\d/.test(value);
    const symbol = /[^A-Za-z0-9]/.test(value);
    let points = [value.length >= 12, value.length >= 16, mixedCase, digit, symbol].filter(Boolean).length;

    if (/(.)\1\1/.test(value)) points -= 1; // "aaa", "111"
    if (COMMON.test(value) || new Set(value.toLowerCase()).size < 4) points = 0;

    const score = Math.min(4, Math.max(1, points));
    const tip = COMMON.test(value)
        ? 'Avoid common passwords.'
        : value.length < 12 ? 'Make it longer.'
        : ! mixedCase ? 'Mix upper and lower case.'
        : ! digit ? 'Add a number.'
        : ! symbol ? 'Add a symbol.'
        : '';

    return { score, label: LEVELS[score].label, tip: score < 4 ? tip : '' };
}

function showStrength(input) {
    const meter = document.querySelector(`[data-password-strength="${CSS.escape(input.id)}"]`);
    if (! meter) return;

    meter.hidden = input.value === '';
    if (meter.hidden) return;

    const { score, label, tip } = rate(input.value);
    const level = LEVELS[score];

    meter.querySelectorAll('span.h-1\\.5').forEach((bar, i) => {
        bar.className = `h-1.5 flex-1 rounded-full transition-colors ${i < score ? level.bar : 'bg-slate-200'}`;
    });
    const labelEl = meter.querySelector('[data-strength-label]');
    labelEl.className = `font-medium ${level.text}`;
    labelEl.textContent = label;
    meter.querySelector('[data-strength-tip]').textContent = tip ? ` · ${tip}` : '';
}

function showMatch(check) {
    const form = check.closest('form');
    const original = form?.elements.namedItem(check.dataset.passwordMatch);
    const confirmation = document.getElementById(check.dataset.for);
    if (! original || ! confirmation) return;

    check.hidden = confirmation.value === '';
    if (check.hidden) return;

    const matches = confirmation.value === original.value;
    // Still typing it: no need to call it wrong yet.
    const finished = confirmation.value.length >= original.value.length;

    check.classList.toggle('text-emerald-600', matches);
    check.classList.toggle('text-red-600', ! matches && finished);
    check.classList.toggle('text-slate-500', ! matches && ! finished);
    check.querySelector('[data-match-icon]').toggleAttribute('hidden', ! matches);
    check.querySelector('[data-match-text]').textContent = matches
        ? 'Passwords match'
        : finished ? "Passwords don't match" : "Passwords don't match yet";
}

function setShown(button, shown) {
    const input = document.getElementById(button.dataset.passwordToggle);
    if (! input) return;

    input.type = shown ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(shown));
    button.setAttribute('aria-label', shown ? 'Hide password' : 'Show password');
    button.querySelectorAll('[data-password-shown]').forEach((icon) => {
        icon.toggleAttribute('hidden', icon.dataset.passwordShown !== String(shown));
    });
}

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.hidden = false;
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-password-toggle]');
    if (button) setShown(button, button.getAttribute('aria-pressed') !== 'true');
});

// Clicking the button keeps the cursor in the field.
document.addEventListener('mousedown', (event) => {
    if (event.target.closest('[data-password-toggle]')) event.preventDefault();
});

document.addEventListener('input', (event) => {
    const input = event.target;
    if (! (input instanceof HTMLInputElement) || ! input.form) return;

    showStrength(input);
    input.form.querySelectorAll('[data-password-match]').forEach(showMatch);
});

// Hide them again before sending, so password managers still offer to save.
document.addEventListener('submit', (event) => {
    event.target.querySelectorAll('[data-password-toggle][aria-pressed="true"]').forEach((button) => setShown(button, false));
});
