import type { Config } from 'tailwindcss'

/**
 * Brand color scale sampled from the Atma Jaya crest (docs/Logo_unika-atmajaya.gif):
 * deep green shield + gold/orange dove-and-rays. Semantic + surface tokens
 * are CSS variables (see src/styles/tokens.css) so light/dark mode only
 * needs to swap variable values, not Tailwind classes.
 */
const config: Config = {
  darkMode: 'class',
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#eaf6ee',
          100: '#cdead6',
          200: '#9cd5af',
          300: '#68bb88',
          400: '#3f9e68',
          500: '#237f4c',
          600: '#166534',
          700: '#114f29',
          800: '#0d3d20',
          900: '#0a2f19',
          950: '#051a0e',
          DEFAULT: 'var(--color-primary)',
          hover: 'var(--color-primary-hover)',
        },
        accent: {
          50: '#fdf3e7',
          100: '#fbe4c4',
          200: '#f6c989',
          300: '#f0ad53',
          400: '#e9932e',
          500: '#d9791a',
          600: '#b35f12',
          700: '#8c4a0f',
          800: '#66360b',
          900: '#452507',
          DEFAULT: 'var(--color-accent)',
          hover: 'var(--color-accent-hover)',
        },
        success: { DEFAULT: 'var(--color-success)' },
        warning: { DEFAULT: 'var(--color-warning)' },
        danger: { DEFAULT: 'var(--color-danger)' },
        info: { DEFAULT: 'var(--color-info)' },
        background: 'var(--color-background)',
        surface: {
          DEFAULT: 'var(--color-surface)',
          hover: 'var(--color-surface-hover)',
        },
        border: {
          DEFAULT: 'var(--color-border)',
          strong: 'var(--color-border-strong)',
        },
        ink: {
          primary: 'var(--color-text-primary)',
          secondary: 'var(--color-text-secondary)',
          tertiary: 'var(--color-text-tertiary)',
          inverted: 'var(--color-text-inverted)',
        },
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      borderRadius: {
        xl: '0.875rem',
        '2xl': '1.125rem',
      },
      boxShadow: {
        card: '0 1px 2px 0 rgb(0 0 0 / 0.04), 0 1px 3px 1px rgb(0 0 0 / 0.06)',
        popover: '0 4px 6px -1px rgb(0 0 0 / 0.08), 0 10px 15px -3px rgb(0 0 0 / 0.08)',
      },
      keyframes: {
        'fade-in': { from: { opacity: '0' }, to: { opacity: '1' } },
        'slide-in': { from: { transform: 'translateY(-4px)', opacity: '0' }, to: { transform: 'translateY(0)', opacity: '1' } },
      },
      animation: {
        'fade-in': 'fade-in 0.15s ease-out',
        'slide-in': 'slide-in 0.15s ease-out',
      },
    },
  },
  plugins: [],
}

export default config
