/*
 * Progressive enhancement only.
 *
 * The site is server-rendered and every page works with JavaScript disabled:
 * the mobile menu and the language switcher are <details> elements, the FAQ is
 * a <details> accordion, and forms are plain POSTs. This file adds the few
 * behaviours HTML cannot express on its own. No framework, no runtime — it
 * compiles to roughly 2 KB.
 */

/**
 * Close a <details> when focus or a click lands outside it.
 * Used by the language switcher and the desktop products menu, both of which
 * would otherwise stay open after the pointer leaves.
 */
function dismissOnOutsideInteraction() {
    document.addEventListener('click', (event) => {
        document.querySelectorAll('details[data-dismissable][open]').forEach((details) => {
            if (!details.contains(event.target)) {
                details.open = false;
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;

        const open = document.querySelector('details[data-dismissable][open]');
        if (!open) return;

        open.open = false;
        open.querySelector('summary')?.focus();
    });
}

/**
 * Lock body scroll while the full-screen mobile menu is open, so the page
 * behind it does not scroll away under the overlay on iOS.
 */
function lockScrollWithMobileMenu() {
    const menu = document.querySelector('[data-mobile-menu]');
    if (!menu) return;

    menu.addEventListener('toggle', () => {
        document.body.style.overflow = menu.open ? 'hidden' : '';
    });
}

/**
 * Product/project image gallery: swap the main image when a thumbnail is
 * chosen. The main image is a real <img> in the markup, so the first (and
 * usually only) image a visitor sees needs no JavaScript at all.
 */
function bindGalleries() {
    document.querySelectorAll('[data-gallery]').forEach((gallery) => {
        const frame = gallery.querySelector('[data-gallery-main]');
        if (!frame) return;

        const image = frame.querySelector('img');
        if (!image) return;

        // The main image is a <picture>: a <source srcset> outranks the <img>
        // src, so swapping src alone would change nothing on screen. Both have
        // to move together, which is why the thumbnail carries the srcset too.
        const source = frame.querySelector('source');

        gallery.querySelectorAll('[data-gallery-thumb]').forEach((thumb) => {
            thumb.addEventListener('click', () => {
                const full = thumb.dataset.galleryThumb;
                if (!full) return;

                if (source) source.srcset = thumb.dataset.gallerySrcset ?? '';
                image.src = full;
                image.alt = thumb.querySelector('img')?.alt ?? image.alt;

                gallery
                    .querySelectorAll('[data-gallery-thumb]')
                    .forEach((other) => other.setAttribute('aria-current', String(other === thumb)));
            });
        });
    });
}

/**
 * Catalogue filters submit on change, so choosing a category or a sort order
 * does not need a separate "Apply" button. The <noscript> fallback keeps the
 * submit button visible when this never runs.
 */
function bindAutoSubmitFilters() {
    document.querySelectorAll('[data-auto-submit]').forEach((form) => {
        form.querySelectorAll('select').forEach((select) => {
            select.addEventListener('change', () => form.requestSubmit());
        });

        form.querySelector('[data-filter-submit]')?.classList.add('hidden');
    });
}

/**
 * Day/night toggle.
 *
 * The theme itself is CSS: `prefers-color-scheme` handles a visitor who has
 * never chosen, and `data-theme` on <html> overrides it in either direction.
 * This adds only the choosing — so with the bundle blocked the site still
 * follows the operating system, it simply cannot be argued with.
 *
 * The current theme is read back from the document rather than tracked in a
 * variable, because the first click has to flip whatever the system decided,
 * not whatever this file assumed.
 */
function bindThemeToggle() {
    const buttons = document.querySelectorAll('[data-theme-toggle]');
    if (buttons.length === 0) return;

    const root = document.documentElement;

    const current = () =>
        root.getAttribute('data-theme') ??
        (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

    buttons.forEach((button) => {
        button.addEventListener('click', (event) => {
            const next = current() === 'dark' ? 'light' : 'dark';

            root.setAttribute('data-theme', next);

            try {
                localStorage.setItem('theme', next);
            } catch (error) {
                // Private mode, or storage full. The choice still applies to
                // this page; it just will not survive the next one.
            }

            /*
             * Hand focus back after a pointer click.
             *
             * The header reveals itself for anything focused inside it — an
             * escape hatch so a keyboard user tabbing in never lands on a bar
             * that is hidden. A mouse click also leaves focus on the button,
             * so after switching theme the header stayed pinned open at the
             * top of the page, where it is supposed to hide. Every other
             * control up there navigates away, which is why nothing had hit
             * this before: the toggle is the first one that stays.
             *
             * `event.detail` is the click count for a pointer and 0 for a
             * button activated from the keyboard, which is exactly the
             * distinction needed — the keyboard user keeps focus and keeps the
             * header, the mouse user gets the bar back out of the way.
             */
            if (event.detail > 0) {
                button.blur();
            }
        });
    });
}

/*
 * Each one is isolated. These are independent progressive enhancements, and a
 * throw in any of them used to take out every one that had not run yet — which
 * is how a small failure in one place turned into a missing feature somewhere
 * unrelated. The header's own behaviour is not in this list at all: it is the
 * only navigation on a phone, so it lives inline in the head where it does not
 * depend on this bundle arriving.
 */
[
    dismissOnOutsideInteraction,
    lockScrollWithMobileMenu,
    bindGalleries,
    bindAutoSubmitFilters,
    bindThemeToggle,
].forEach((enhance) => {
    try {
        enhance();
    } catch (error) {
        console.error(`[artaleca] ${enhance.name} failed`, error);
    }
});
