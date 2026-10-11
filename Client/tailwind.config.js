function withOpacity(variableName) {
  return ({ opacityValue }) => {
    return opacityValue !== undefined
      ? `rgba(var(${variableName}), ${opacityValue})`
      : `rgb(var(${variableName}))`
  }
}

export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        sans: ['"Plus Jakarta Sans"', 'Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', '"Segoe UI"', 'Roboto', 'sans-serif'],
      },
      colors: {
        // Abstract Semantic Brand Tokens (Dynamic Multi-Property System)
        brand: {
          canvas: withOpacity('--brand-canvas-rgb'),
          surface: withOpacity('--brand-surface-rgb'),
          'surface-elevated': withOpacity('--brand-surface-elevated-rgb'),
          'surface-subtle': withOpacity('--brand-surface-subtle-rgb'),
          headline: withOpacity('--brand-headline-rgb'),
          body: withOpacity('--brand-body-rgb'),
          muted: withOpacity('--brand-muted-rgb'),
          border: withOpacity('--brand-border-rgb'),
          'border-subtle': withOpacity('--brand-border-subtle-rgb'),
          primary: withOpacity('--brand-primary-rgb'),
          'primary-hover': withOpacity('--brand-primary-hover-rgb'),
          'primary-active': withOpacity('--brand-primary-active-rgb'),
          accent: withOpacity('--brand-accent-rgb'),
          'cta-text': withOpacity('--brand-cta-text-rgb'),
        },
        primary: {
          50: '#fffbeb',
          100: '#fef3c7',
          200: '#fde68a',
          300: '#fcd34d',
          400: '#fbbf24',
          500: '#f59e0b',
          600: '#d97706',
          700: '#b45309',
          800: '#92400e',
          900: '#78350f',
          DEFAULT: 'var(--color-primary, #f59e0b)',
          hover: 'var(--color-primary-hover, #d97706)',
          foreground: 'var(--color-primary-foreground, #111827)',
          subtle: 'var(--color-primary-subtle, #fef3c7)',
        },
        app: {
          bg: 'var(--color-background, #f8fafc)',
          surface: 'var(--color-surface, #ffffff)',
          'surface-secondary': 'var(--color-surface-secondary, #f1f5f9)',
          text: 'var(--color-text, #0f172a)',
          'text-secondary': 'var(--color-text-secondary, #475569)',
          'text-muted': 'var(--color-text-muted, #64748b)',
          border: 'var(--color-border, #e2e8f0)',
          'border-subtle': 'var(--color-border-subtle, #f1f5f9)',
          focus: 'var(--color-focus, #2563eb)',
        },
        semantic: {
          success: 'var(--color-success, #16a34a)',
          'success-hover': 'var(--color-success-hover, #15803d)',
          'success-subtle': 'var(--color-success-subtle, #dcfce7)',
          'success-fg': 'var(--color-success-foreground, #ffffff)',
          warning: 'var(--color-warning, #d97706)',
          'warning-hover': 'var(--color-warning-hover, #b45309)',
          'warning-subtle': 'var(--color-warning-subtle, #fef3c7)',
          'warning-fg': 'var(--color-warning-foreground, #ffffff)',
          error: 'var(--color-error, #dc2626)',
          'error-hover': 'var(--color-error-hover, #b91c1c)',
          'error-subtle': 'var(--color-error-subtle, #fee2e2)',
          'error-fg': 'var(--color-error-foreground, #ffffff)',
          info: 'var(--color-info, #2563eb)',
          'info-hover': 'var(--color-info-hover, #1d4ed8)',
          'info-subtle': 'var(--color-info-subtle, #dbeafe)',
          'info-fg': 'var(--color-info-foreground, #ffffff)',
        },
        accent: {
          50: '#ecfdf5',
          100: '#d1fae5',
          500: '#10b981',
          600: '#059669',
          700: '#047857',
          DEFAULT: '#059669',
        },
        surface: {
          DEFAULT: 'var(--color-surface, #ffffff)',
          secondary: 'var(--color-surface-secondary, #f1f5f9)',
          light: '#ffffff',
          soft: '#f8fafc',
          dark: '#111827',
          darker: '#0b1120',
        }
      },
      boxShadow: {
        'brand-glow': 'var(--shadow-brand-glow)',
        'brand-glow-lg': 'var(--shadow-brand-glow-lg)',
        'brand-surface': 'var(--shadow-brand-surface)',
      }
    },
  },
  plugins: [],
}