import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { resolveHotelThemeId, HOTEL_PRESET_THEMES, type PropertyThemeTokens } from '@/utils/colorTokens'

export const useThemeStore = defineStore('theme', () => {
  const savedMode = localStorage.getItem('app-theme') || localStorage.getItem('theme')
  const prefersDark = typeof window !== 'undefined' && window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)').matches : false
  const isDark = ref<boolean>(savedMode ? savedMode === 'dark' : prefersDark)

  const savedHotelTheme = localStorage.getItem('guest_hotel_theme') || 'luxury-gold'
  const hotelTheme = ref<string>(savedHotelTheme)
  const updateMobileMetaThemeColor = () => {
    if (typeof document === 'undefined') return
    let metaTag = document.querySelector('meta[name="theme-color"]')
    if (!metaTag) {
      metaTag = document.createElement('meta')
      metaTag.setAttribute('name', 'theme-color')
      document.head.appendChild(metaTag)
    }

    // Determine current canvas background color for browser chrome
    const currentPreset = HOTEL_PRESET_THEMES[hotelTheme.value] || HOTEL_PRESET_THEMES['luxury-gold']
    const color = isDark.value
      ? (currentPreset.canvasDarkHex || '#07101E')
      : (currentPreset.canvasLightHex || '#FFFFFF')

    metaTag.setAttribute('content', color)
  }
  const applyTheme = () => {
    if (typeof document === 'undefined') return
    const root = document.documentElement
    const body = document.body

    if (isDark.value) {
      root.classList.add('dark')
      if (body) body.classList.add('dark')
      localStorage.setItem('app-theme', 'dark')
      localStorage.setItem('theme', 'dark')
    } else {
      root.classList.remove('dark')
      if (body) body.classList.remove('dark')
      localStorage.setItem('app-theme', 'light')
      localStorage.setItem('theme', 'light')
    }
    root.setAttribute('data-hotel-theme', hotelTheme.value)
    localStorage.setItem('guest_hotel_theme', hotelTheme.value)
    updateMobileMetaThemeColor()
  }
  const setHotelTheme = (themeId: string) => {
    hotelTheme.value = themeId
    applyTheme()
  }
  const syncWithHotel = (hotel: { name?: string; slug?: string } | null) => {
    if (!hotel) return
    const resolvedId = resolveHotelThemeId(hotel.slug || hotel.name)
    setHotelTheme(resolvedId)
  }

  const initializeTheme = () => {
    const s = localStorage.getItem('app-theme') || localStorage.getItem('theme')
    if (s) {
      isDark.value = s === 'dark'
    }
    const t = localStorage.getItem('guest_hotel_theme')
    if (t) {
      hotelTheme.value = t
    }
    applyTheme()
  }

  const toggleTheme = () => {
    isDark.value = !isDark.value
    applyTheme()
  }

  const setDarkMode = (val: boolean) => {
    isDark.value = val
    applyTheme()
  }

  return {
    isDark,
    isDarkMode: computed(() => isDark.value),
    hotelTheme,
    initializeTheme,
    initTheme: initializeTheme,
    toggleTheme,
    setDarkMode,
    setHotelTheme,
    syncWithHotel,
    applyTheme,
  }
})
