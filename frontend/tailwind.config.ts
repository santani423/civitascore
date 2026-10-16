import type { Config } from 'tailwindcss'
import plugin from 'tailwindcss/plugin'

/**
 * Landing page type scale (src/landing). Registered as components so it
 * beats preflight's heading reset yet still yields to utilities, whatever
 * order the stylesheets load in. Fonts come from `--lp-font-*` on `.lp-root`;
 * Mandarin spacing tweaks live in src/landing/styles/landing.css.
 */
const landingTypography = plugin(({ addComponents }) => {
  const display = { fontFamily: 'var(--lp-font-display)' }
  addComponents({
    '.lp-display': {
      ...display,
      fontWeight: '700',
      fontSize: 'clamp(2.25rem, 1.35rem + 3.6vw, 4.375rem)',
      lineHeight: '1.04',
      letterSpacing: '-0.035em',
      textWrap: 'balance',
    },
    '.lp-h2': {
      ...display,
      fontWeight: '700',
      fontSize: 'clamp(1.75rem, 1.2rem + 2vw, 2.875rem)',
      lineHeight: '1.1',
      letterSpacing: '-0.028em',
      textWrap: 'balance',
    },
    '.lp-h3': { ...display, fontWeight: '650', fontSize: 'clamp(1.125rem, 1.05rem + 0.3vw, 1.3125rem)', lineHeight: '1.3', letterSpacing: '-0.012em' },
    '.lp-h4': { ...display, fontWeight: '650', fontSize: '1rem', lineHeight: '1.4', letterSpacing: '-0.006em' },
    '.lp-lead': { fontSize: 'clamp(1.0625rem, 1rem + 0.3vw, 1.25rem)', lineHeight: '1.62', textWrap: 'pretty' },
    '.lp-body': { fontSize: '1rem', lineHeight: '1.65', textWrap: 'pretty' },
    '.lp-small': { fontSize: '0.875rem', lineHeight: '1.55' },
    '.lp-eyebrow': { fontSize: '0.75rem', lineHeight: '1.3', fontWeight: '650', letterSpacing: '0.14em', textTransform: 'uppercase' },
  })
})

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
        // Civitas One design tokens (src/one/styles/one.css). Namespaced under
        // `one-*` so they never collide with the legacy palette.
        one: {
          canvas: 'rgb(var(--one-canvas) / <alpha-value>)',
          card: 'rgb(var(--one-card) / <alpha-value>)',
          elev: 'rgb(var(--one-elev) / <alpha-value>)',
          sunken: 'rgb(var(--one-sunken) / <alpha-value>)',
          line: 'rgb(var(--one-line) / <alpha-value>)',
          line2: 'rgb(var(--one-line2) / <alpha-value>)',
          fg: 'rgb(var(--one-fg) / <alpha-value>)',
          muted: 'rgb(var(--one-muted) / <alpha-value>)',
          subtle: 'rgb(var(--one-subtle) / <alpha-value>)',
          brand: 'rgb(var(--one-brand) / <alpha-value>)',
          onbrand: 'rgb(var(--one-onbrand) / <alpha-value>)',
          hero: 'rgb(var(--one-hero) / <alpha-value>)',
          royal: 'rgb(var(--one-royal) / <alpha-value>)',
          sky: 'rgb(var(--one-sky) / <alpha-value>)',
          teal: 'rgb(var(--one-teal) / <alpha-value>)',
          gold: 'rgb(var(--one-gold) / <alpha-value>)',
          spark: 'rgb(var(--one-spark) / <alpha-value>)',
          ink: 'rgb(var(--one-ink) / <alpha-value>)',
          ok: 'rgb(var(--one-ok) / <alpha-value>)',
          warn: 'rgb(var(--one-warn) / <alpha-value>)',
          bad: 'rgb(var(--one-bad) / <alpha-value>)',
        },
        // Civitas One Atma landing page tokens (src/landing/styles/landing.css),
        // scoped to `.lp-root` like the `one-*` set above.
        lp: {
          canvas: 'rgb(var(--lp-canvas) / <alpha-value>)',
          surface: 'rgb(var(--lp-surface) / <alpha-value>)',
          sunken: 'rgb(var(--lp-sunken) / <alpha-value>)',
          elev: 'rgb(var(--lp-elev) / <alpha-value>)',
          line: 'rgb(var(--lp-line) / <alpha-value>)',
          strong: 'rgb(var(--lp-line-strong) / <alpha-value>)',
          fg: 'rgb(var(--lp-fg) / <alpha-value>)',
          muted: 'rgb(var(--lp-muted) / <alpha-value>)',
          subtle: 'rgb(var(--lp-subtle) / <alpha-value>)',
          brand: 'rgb(var(--lp-brand) / <alpha-value>)',
          'brand-hover': 'rgb(var(--lp-brand-hover) / <alpha-value>)',
          'brand-text': 'rgb(var(--lp-brand-text) / <alpha-value>)',
          'brand-soft': 'rgb(var(--lp-brand-soft) / <alpha-value>)',
          onbrand: 'rgb(var(--lp-onbrand) / <alpha-value>)',
          gold: 'rgb(var(--lp-gold) / <alpha-value>)',
          'gold-text': 'rgb(var(--lp-gold-text) / <alpha-value>)',
          band: 'rgb(var(--lp-band) / <alpha-value>)',
          'band-elev': 'rgb(var(--lp-band-elev) / <alpha-value>)',
          'band-fg': 'rgb(var(--lp-band-fg) / <alpha-value>)',
          'band-muted': 'rgb(var(--lp-band-muted) / <alpha-value>)',
          'band-line': 'rgb(var(--lp-band-line) / <alpha-value>)',
          ok: 'rgb(var(--lp-ok) / <alpha-value>)',
          warn: 'rgb(var(--lp-warn) / <alpha-value>)',
          bad: 'rgb(var(--lp-bad) / <alpha-value>)',
          info: 'rgb(var(--lp-info) / <alpha-value>)',
          viz1: 'rgb(var(--lp-viz-1) / <alpha-value>)',
          viz2: 'rgb(var(--lp-viz-2) / <alpha-value>)',
          viz3: 'rgb(var(--lp-viz-3) / <alpha-value>)',
          ring: 'rgb(var(--lp-ring) / <alpha-value>)',
          scrim: 'rgb(var(--lp-scrim) / <alpha-value>)',
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
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'PingFang SC', 'Microsoft YaHei', 'sans-serif'],
        display: ['"Plus Jakarta Sans"', 'Inter', 'ui-sans-serif', 'system-ui', 'PingFang SC', 'Microsoft YaHei', 'sans-serif'],
      },
      borderRadius: {
        xl: '0.875rem',
        '2xl': '1.125rem',
        'lp-sm': 'var(--lp-radius-sm)',
        lp: 'var(--lp-radius)',
        'lp-lg': 'var(--lp-radius-lg)',
        'lp-xl': 'var(--lp-radius-xl)',
      },
      boxShadow: {
        card: '0 1px 2px 0 rgb(0 0 0 / 0.04), 0 1px 3px 1px rgb(0 0 0 / 0.06)',
        popover: '0 4px 6px -1px rgb(0 0 0 / 0.08), 0 10px 15px -3px rgb(0 0 0 / 0.08)',
        soft: 'var(--one-shadow-soft)',
        lift: 'var(--one-shadow-lift)',
        overlay: 'var(--one-shadow-overlay)',
        'lp-sm': 'var(--lp-shadow-sm)',
        'lp-md': 'var(--lp-shadow-md)',
        'lp-lg': 'var(--lp-shadow-lg)',
        'lp-xl': 'var(--lp-shadow-xl)',
      },
      keyframes: {
        'fade-in': { from: { opacity: '0' }, to: { opacity: '1' } },
        'slide-in': { from: { transform: 'translateY(-4px)', opacity: '0' }, to: { transform: 'translateY(0)', opacity: '1' } },
        'rise-in': { from: { transform: 'translateY(8px)', opacity: '0' }, to: { transform: 'translateY(0)', opacity: '1' } },
        'drawer-left': { from: { transform: 'translateX(-100%)' }, to: { transform: 'translateX(0)' } },
        'drawer-right': { from: { transform: 'translateX(100%)' }, to: { transform: 'translateX(0)' } },
        shimmer: { '100%': { transform: 'translateX(100%)' } },
      },
      animation: {
        'fade-in': 'fade-in 0.15s ease-out',
        'slide-in': 'slide-in 0.15s ease-out',
        'rise-in': 'rise-in 0.25s cubic-bezier(0.2, 0.8, 0.2, 1)',
        'drawer-left': 'drawer-left 0.25s cubic-bezier(0.2, 0.8, 0.2, 1)',
        'drawer-right': 'drawer-right 0.25s cubic-bezier(0.2, 0.8, 0.2, 1)',
        shimmer: 'shimmer 1.6s infinite',
      },
    },
  },
  plugins: [landingTypography],
}

export default config
