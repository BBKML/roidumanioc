import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',      // Breeze / Tailwind (résiduel)
                'resources/js/app.js',
                'resources/css/vitrine.css',  // site public
                'resources/js/vitrine.js',
                'resources/css/admin.css',    // back-office + espace apprenant (Livewire)
                'resources/js/admin.js',
                'resources/css/auth.css',     // connexion / inscription
                'resources/js/auth.js',
            ],
            refresh: true,
        }),
    ],
});
