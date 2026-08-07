import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        /*
         * No remote-font plugin: the site self-hosts a single Vazirmatn
         * variable font from public/fonts, which covers Persian, Arabic and
         * Latin in one 111 KB request. Fetching a font family from a CDN would
         * add a third-party origin to the CSP, a DNS lookup on the critical
         * path, and a second family the other two scripts cannot use.
         */
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        // Hashed filenames are served with a long max-age, so a slightly larger
        // inline threshold trades a request for a few hundred cached bytes.
        assetsInlineLimit: 2048,
        rollupOptions: {
            output: {
                assetFileNames: 'assets/[name]-[hash][extname]',
                chunkFileNames: 'assets/[name]-[hash].js',
                entryFileNames: 'assets/[name]-[hash].js',
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
