import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    define: {
        REVERB_HOST: JSON.stringify(process.env.REVERB_HOST || 'localhost'),
        REVERB_PORT: JSON.stringify(process.env.REVERB_PORT || 8080),
        REVERB_SCHEME: JSON.stringify(process.env.REVERB_SCHEME || 'http'),
    },
});
