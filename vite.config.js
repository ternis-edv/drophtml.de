import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/landing.css', 'resources/js/app.js', 'resources/css/editor.css', 'resources/css/site-workspace.css', 'resources/css/dashboard.css', 'resources/js/editor.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    css: {
        // Flatten native CSS nesting (landing.css) in dev as well, so the dev
        // server serves the same output as the production build instead of raw
        // nested syntax that older browsers drop entirely.
        transformer: 'lightningcss',
    },
    server: {
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
