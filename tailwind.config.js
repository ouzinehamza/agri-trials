import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.tsx',
    ],

    theme: {
        extend: {
            colors: {
                brand: 'rgb(var(--brand-primary) / <alpha-value>)',
                'brand-hover': 'rgb(var(--brand-hover) / <alpha-value>)',
                'brand-fg': 'rgb(var(--brand-fg) / <alpha-value>)',
                accent: 'rgb(var(--accent) / <alpha-value>)',
                page: 'rgb(var(--page) / <alpha-value>)',
                surface: 'rgb(var(--surface) / <alpha-value>)',
                'surface-2': 'rgb(var(--surface-2) / <alpha-value>)',
                line: 'rgb(var(--line) / <alpha-value>)',
                ink: 'rgb(var(--ink) / <alpha-value>)',
                'ink-muted': 'rgb(var(--ink-muted) / <alpha-value>)',
                'ink-faint': 'rgb(var(--ink-faint) / <alpha-value>)',
                success: 'rgb(var(--success) / <alpha-value>)',
                'success-soft': 'rgb(var(--success-soft) / <alpha-value>)',
                warning: 'rgb(var(--warning) / <alpha-value>)',
                'warning-soft': 'rgb(var(--warning-soft) / <alpha-value>)',
                danger: 'rgb(var(--danger) / <alpha-value>)',
                'danger-soft': 'rgb(var(--danger-soft) / <alpha-value>)',
                info: 'rgb(var(--info) / <alpha-value>)',
                'info-soft': 'rgb(var(--info-soft) / <alpha-value>)',
            },
            fontFamily: {
                sans: ['var(--font-sans)', 'system-ui', 'sans-serif'],
            },
            borderRadius: {
                DEFAULT: 'var(--radius)',
                md: 'var(--radius)',
                lg: 'calc(var(--radius) + 4px)',
                xl: 'calc(var(--radius) + 8px)',
            },
            keyframes: {
                'fade-in': { from: { opacity: '0' }, to: { opacity: '1' } },
                'fade-in-up': { from: { opacity: '0', transform: 'translateY(8px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
                'scale-in': { from: { opacity: '0', transform: 'scale(0.97)' }, to: { opacity: '1', transform: 'scale(1)' } },
                shimmer: { '100%': { transform: 'translateX(100%)' } },
            },
            animation: {
                'fade-in': 'fade-in 0.25s ease-out both',
                'fade-in-up': 'fade-in-up 0.3s cubic-bezier(0.22, 1, 0.36, 1) both',
                'scale-in': 'scale-in 0.18s ease-out both',
            },
        },
    },

    plugins: [forms],
};
