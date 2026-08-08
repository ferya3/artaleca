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
 * The header is hidden at the top of the page and slides in once the visitor
 * scrolls, so the hero is seen without a bar across it.
 *
 * The hidden state itself lives in app.css keyed off the *absence* of
 * `data-revealed`, which is what keeps the bar out of the way at first paint
 * instead of flashing in and then jumping away.
 *
 * The two thresholds are not the same number on purpose. Revealing at 140px
 * and hiding again only below 40px gives the toggle enough hysteresis that a
 * trackpad hovering around the boundary cannot strobe it.
 */
function bindHeaderReveal() {
    const header = document.querySelector('[data-site-header]');
    if (!header) return;

    const menu = document.querySelector('[data-mobile-menu]');
    const REVEAL_AT = 140;
    const HIDE_BELOW = 40;

    let ticking = false;

    const update = () => {
        ticking = false;
        const y = window.scrollY;

        // The mobile menu's close button lives inside the header, so the bar
        // has to stay put while the menu is open however far the page has
        // scrolled — sliding it away would strand whoever opened it.
        if (menu?.open || y >= REVEAL_AT) {
            header.setAttribute('data-revealed', '');
        } else if (y <= HIDE_BELOW) {
            header.removeAttribute('data-revealed');
        }
    };

    addEventListener(
        'scroll',
        () => {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(update);
        },
        { passive: true },
    );

    /*
     * Switching language keeps your place.
     *
     * A language link navigates to a different URL, so the new page would
     * otherwise start at the top — and the header, which is `fixed` and out of
     * flow, would then sit over the first 118px of the page while the visitor
     * looks at content they did not ask to be taken to. Forcing the bar open
     * there only makes it worse: it covers the eyebrow and half the headline.
     *
     * Restoring the offset solves the whole thing at once. The visitor stays
     * where they were reading, `update()` reveals the header on its own because
     * the page is scrolled, and nothing is covered by it.
     *
     * Persian, English and Arabic set the same content at different lengths, so
     * the offset is close rather than exact — which is the right trade for the
     * alternative of being thrown back to the top.
     */
    const MARK = 'header:offset';

    document.querySelectorAll('[data-keep-header]').forEach((link) => {
        link.addEventListener('click', () => {
            try {
                sessionStorage.setItem(MARK, String(window.scrollY));
            } catch {
                // Storage denied in private mode: the page lands at the top,
                // which is the ordinary behaviour rather than a failure.
            }
        });
    });

    let restored = null;
    try {
        restored = sessionStorage.getItem(MARK);
        sessionStorage.removeItem(MARK);
    } catch {
        /* storage unavailable */
    }

    if (restored !== null && Number(restored) > 0) {
        // The browser's own restoration would fight this one on a reload.
        history.scrollRestoration = 'manual';
        window.scrollTo({ top: Number(restored), behavior: 'instant' });
    }

    // Also covers a reload that restores a position well down the page, where
    // the header should be showing before the first scroll event fires.
    update();
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

dismissOnOutsideInteraction();
lockScrollWithMobileMenu();
bindHeaderReveal();
bindGalleries();
bindAutoSubmitFilters();
