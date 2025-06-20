import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import livewire from '@defstudio/vite-livewire-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: ['resources/views/**', 'app/Livewire/**'],
        }),
        tailwindcss(),
        livewire({
            refresh: ['resources/views/**', 'app/Livewire/**']
        }),
    ],
});
