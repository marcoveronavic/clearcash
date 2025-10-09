import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/sass/admin.scss',
                'resources/sass/customer.scss',
                'resources/sass/login.scss',
                'resources/sass/staff.scss',
                'resources/js/app.js',
                'resources/js/admin.js',
                'resources/js/customer.js',
                'resources/js/staff.js'
            ],
            refresh: true,
        }),
    ],
});
