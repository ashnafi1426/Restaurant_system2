export interface PropertyThemeTokens {
  id: string
  name: string
  primaryHex: string
  primaryHoverHex: string
  primaryActiveHex?: string
  accentHex?: string
  canvasLightHex?: string
  canvasDarkHex?: string
}
export function hexToRgb(hex: string): { r: number; g: number; b: number } | null {
  const clean = hex.replace('#', '').trim()
  if (clean.length === 3) {
    const r = parseInt(clean[0] + clean[0], 16)
    const g = parseInt(clean[1] + clean[1], 16)
    const b = parseInt(clean[2] + clean[2], 16)
    return { r, g, b }
  }
  if (clean.length === 6) {
    const r = parseInt(clean.substring(0, 2), 16)
    const g = parseInt(clean.substring(2, 4), 16)
    const b = parseInt(clean.substring(4, 6), 16)
    return { r, g, b }
  }
  return null
}
export function hexToRgbString(hex: string): string {
  const rgb = hexToRgb(hex)
  if (!rgb) return '233, 161, 26'
  return `${rgb.r}, ${rgb.g}, ${rgb.b}`
}
export function getRelativeLuminance(r: number, g: number, b: number): number {
  const [rs, gs, bs] = [r, g, b].map((val) => {
    const s = val / 255
    return s <= 0.04045 ? s / 12.92 : Math.pow((s + 0.055) / 1.055, 2.4)
  })
  return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs
}
export function getCompliantCtaTextColor(backgroundHex: string): {
  hex: string
  rgbString: string
  ratio: number
} {
  const rgb = hexToRgb(backgroundHex)
  if (!rgb) {
    return { hex: '#FFFFFF', rgbString: '255, 255, 255', ratio: 21 }
  }

  const bgLum = getRelativeLuminance(rgb.r, rgb.g, rgb.b)
  const whiteLum = 1.0
  const darkInkLum = getRelativeLuminance(11, 27, 53) // #0B1B35

  const contrastWithWhite = (whiteLum + 0.05) / (bgLum + 0.05)
  const contrastWithDark = (bgLum + 0.05) / (darkInkLum + 0.05)

  // Choose the one with the superior contrast ratio
  if (contrastWithDark >= contrastWithWhite && contrastWithDark >= 4.5) {
    return {
      hex: '#0B1B35',
      rgbString: '11, 27, 53',
      ratio: contrastWithDark,
    }
  }

  return {
    hex: '#FFFFFF',
    rgbString: '255, 255, 255',
    ratio: contrastWithWhite,
  }
}

/**
 * Built-in Property Themes
 */
export const HOTEL_PRESET_THEMES: Record<string, PropertyThemeTokens> = {
  'luxury-gold': {
    id: 'luxury-gold',
    name: 'Modern Luxury / Sheraton',
    primaryHex: '#E9A11A',
    primaryHoverHex: '#D49214',
    accentHex: '#F5C869',
    canvasLightHex: '#FFFFFF',
    canvasDarkHex: '#07101E',
  },
  'heritage-wine': {
    id: 'heritage-wine',
    name: 'Dire Dawa Ras Heritage',
    primaryHex: '#802325',
    primaryHoverHex: '#691A1C',
    accentHex: '#E5B54F',
    canvasLightHex: '#FDFCFB',
    canvasDarkHex: '#160809',
  },
}

/**
 * Determine preset theme identifier based on hotel slug or name
 */
export function resolveHotelThemeId(hotelNameOrSlug?: string | null): string {
  if (!hotelNameOrSlug) return 'luxury-gold'
  const normalized = hotelNameOrSlug.toLowerCase()
  if (
    normalized.includes('ras') ||
    normalized.includes('heritage') ||
    normalized.includes('dire') ||
    normalized.includes('wine')
  ) {
    return 'heritage-wine'
  }
  return 'luxury-gold'
}
