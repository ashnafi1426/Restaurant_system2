import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export const useThemeStore = defineStore('theme', () => {
  /*
  |--------------------------------------------------------------------------
  | STATE
  |--------------------------------------------------------------------------
  */

  // Theme: 'light' or 'dark'
  const isDarkMode = ref<boolean>(false)

  // Load theme from localStorage on initialization
  const initializeTheme = () => {
    const savedTheme = localStorage.getItem('app-theme')
    if (savedTheme) {
      isDarkMode.value = savedTheme === 'dark'
      console.log('[themeStore] 🎨 Theme loaded from localStorage:', savedTheme)
    } else {
      // Check system preference
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
      isDarkMode.value = prefersDark
      console.log('[themeStore] 🎨 Theme set to system preference:', prefersDark ? 'dark' : 'light')
    }
    applyTheme()
  }

  /*
  |--------------------------------------------------------------------------
  | METHODS
  |--------------------------------------------------------------------------
  */

  const applyTheme = () => {
    const htmlElement = document.documentElement

    if (isDarkMode.value) {
      console.log('[themeStore] 🌙 Applying dark mode')
      htmlElement.classList.add('dark')
      localStorage.setItem('app-theme', 'dark')
    } else {
      console.log('[themeStore] ☀️ Applying light mode')
      htmlElement.classList.remove('dark')
      localStorage.setItem('app-theme', 'light')
    }
  }

  const toggleTheme = () => {
    isDarkMode.value = !isDarkMode.value
    console.log('[themeStore] 🔄 Theme toggled:', isDarkMode.value ? 'dark' : 'light')
  }

  const setDarkMode = (value: boolean) => {
    isDarkMode.value = value
    console.log('[themeStore] 🎨 Theme set to:', value ? 'dark' : 'light')
  }

  /*
  |--------------------------------------------------------------------------
  | WATCHERS
  |--------------------------------------------------------------------------
  */

  watch(isDarkMode, () => {
    applyTheme()
  })

  return {
    isDarkMode,
    initializeTheme,
    toggleTheme,
    setDarkMode,
    applyTheme,
  }
})
