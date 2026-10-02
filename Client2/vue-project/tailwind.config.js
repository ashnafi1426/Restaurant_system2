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
          DEFAULT: '#d97706',
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
          light: '#ffffff',
          soft: '#f8fafc',
          dark: '#0f172a',
          darker: '#020617',
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