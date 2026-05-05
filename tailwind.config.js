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
                /** Public marketing shell (layouts.app) — matches Medca clinical typography */
                'medca-sans': ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            spacing: {
                4.5: '18px',
                5.5: '22px',
            },
            /**
             * `mom-chrome` — same as cards (`--radius-card`): inputs, CTAs, tables, search, modals.
             * True circular icon targets keep `rounded-full` in Blade, not this token.
             */
            borderRadius: {
                mom: '22px',
                'mom-sm': '10px',
                'mom-md': '16px',
                'mom-lg': '22px',
                'mom-xl': '28px',
                'mom-pill': '999px',
                'mom-chrome': 'var(--radius-card)',
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
                luxury: '0 20px 45px -25px rgba(10, 25, 47, 0.45)',
                'mom-surface': 'var(--shadow-surface)',
                'mom-elevated': 'var(--shadow-elevated)',
                'mom-hover': 'var(--shadow-hover)',
                'mom-glow': 'var(--shadow-glow)',
                'mom-inner': 'var(--shadow-inner)',
            },
            colors: {
                /** Public marketing / Medca clinical tokens (mirrors medca-healthcare CDN Tailwind config) */
                clinical: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    800: '#1e40af',
                    900: '#1e3a8a',
                    950: '#172554',
                },
                brand: {
                    blue: '#0055FF',
                    'blue-dark': '#0033AA',
                    white: '#F8FAFC',
                    accent: '#E0F2FE',
                },
                'medical-navy': '#0f172a',
                'surgical-silver': '#e2e8f0',
                'mom-bg-app': 'var(--bg-app)',
                'mom-sidebar': 'var(--bg-sidebar)',
                'mom-surface': 'var(--bg-surface)',
                'mom-elevated': 'var(--bg-elevated)',
                'mom-hover': 'var(--bg-hover)',
                'mom-gold': 'var(--accent-gold)',
                'mom-wordmark': 'var(--mom-wordmark)',
                'mom-primary': 'var(--text-primary)',
                'mom-secondary': 'var(--text-secondary)',
                'mom-muted': 'var(--text-muted)',
                'mom-success': 'var(--success)',
                'mom-danger': 'var(--danger)',
                'mom-warning': 'var(--warning)',
            },
            backgroundImage: {
                'mom-sidebar-edge':
                    'linear-gradient(180deg, rgba(197,160,89,0.04) 0%, transparent 38%)',
            },
            backdropBlur: {
                glass: '12px',
            },
        },
    },

    plugins: [
        forms,
        function ({ addUtilities }) {
            addUtilities({
                '.glassmorphism': {
                    background:
                        'linear-gradient(135deg, rgba(255,255,255,0.18), rgba(255,255,255,0.08))',
                    'backdrop-filter': 'blur(12px)',
                    '-webkit-backdrop-filter': 'blur(12px)',
                    border: '1px solid rgba(226,232,240,0.35)',
                },
            });
        },
    ],
};
