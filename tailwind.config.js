import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Noto Sans"', ...defaultTheme.fontFamily.sans],
            },
            spacing: {
                4.5: '18px',
                5.5: '22px',
            },
            borderRadius: {
                mom: '22px',
                'mom-sm': '10px',
                'mom-md': '16px',
                'mom-lg': '22px',
                'mom-xl': '28px',
                'mom-pill': '999px',
            },
            transitionTimingFunction: {
                premium: 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
            transitionDuration: {
                280: '280ms',
                320: '320ms',
                400: '400ms',
            },
            boxShadow: {
                'mom-surface': 'var(--shadow-surface)',
                'mom-elevated': 'var(--shadow-elevated)',
                'mom-hover': 'var(--shadow-hover)',
                'mom-glow': 'var(--shadow-glow)',
                'mom-inner': 'var(--shadow-inner)',
            },
            colors: {
                'mom-bg-app': 'var(--bg-app)',
                'mom-sidebar': 'var(--bg-sidebar)',
                'mom-surface': 'var(--bg-surface)',
                'mom-elevated': 'var(--bg-elevated)',
                'mom-hover': 'var(--bg-hover)',
                'mom-gold': 'var(--accent-gold)',
                'mom-primary': 'var(--text-primary)',
                'mom-secondary': 'var(--text-secondary)',
                'mom-muted': 'var(--text-muted)',
                'mom-success': 'var(--success)',
                'mom-danger': 'var(--danger)',
                'mom-warning': 'var(--warning)',
            },
            backgroundImage: {
                'mom-sidebar-edge':
                    'linear-gradient(180deg, rgba(212,169,95,0.04) 0%, transparent 38%)',
            },
        },
    },

    plugins: [forms],
};
