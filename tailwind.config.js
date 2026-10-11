import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Models/**/*.php',
        './app/Livewire/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                navy: {
                    50: '#f3f6fc', 100: '#e6ecf8', 200: '#c9d5f1', 300: '#9fb3e3', 400: '#6c88cf',
                    500: '#2f55b8', 600: '#1f4096', 700: '#16327a', 800: '#0f2557', 900: '#0a1a42', 950: '#061231',
                },
                ink: { DEFAULT: '#12224d', soft: '#263a6b', muted: '#6b7a9c' },
                brand: { red: '#c9485e', pink: '#ec7fa8' },
            },
        },
    },

    plugins: [forms],
};