// A kiosk project's play link card (projects/partials/play-link.blade.php):
// Copy puts the link on the clipboard, and Share (where the device can share,
// as on phones) opens the share sheet.
const link = () => document.querySelector('[data-play-link]');

document.addEventListener('click', async (event) => {
    const copy = event.target.closest('[data-copy-play-link]');
    if (copy && link()) {
        const label = copy.querySelector('[data-copy-label]');
        try {
            await navigator.clipboard.writeText(link().value);
        } catch {
            link().select();
            document.execCommand('copy');
        }
        label.textContent = 'Copied!';
        clearTimeout(copy.resetTimer);
        copy.resetTimer = setTimeout(() => { label.textContent = 'Copy'; }, 2000);
        return;
    }

    const share = event.target.closest('[data-share-play-link]');
    if (share && link()) {
        navigator.share({ title: share.dataset.title, url: link().value }).catch(() => {});
    }
});

// Selecting the whole link on focus makes it easy to copy by hand too.
document.addEventListener('focusin', (event) => {
    if (event.target.matches('[data-play-link]')) event.target.select();
});

if (navigator.share) {
    document.querySelectorAll('[data-share-play-link]').forEach((button) => { button.hidden = false; });
}
