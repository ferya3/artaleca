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
 * Add a hairline + elevation to the sticky header once the page scrolls, so it
 * sits flush with the hero at rest and separates from content in motion.
 */
function elevateHeaderOnScroll() {
    const header = document.querySelector('[data-site-header]');
    if (!header) return;

    // A sentinel + IntersectionObserver avoids a scroll listener firing on
    // every frame.
    const sentinel = document.createElement('div');
    sentinel.setAttribute('aria-hidden', 'true');
    sentinel.style.cssText = 'position:absolute;top:0;height:1px;width:1px;';
    document.body.prepend(sentinel);

    new IntersectionObserver(
        ([entry]) => header.toggleAttribute('data-scrolled', !entry.isIntersecting),
        { threshold: 0 },
    ).observe(sentinel);
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
elevateHeaderOnScroll();
bindGalleries();
bindAutoSubmitFilters();
