import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useThemeStore = defineStore('theme', () => {
  const saved = localStorage.getItem('app-theme') || localStorage.getItem('theme')
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
  
  const isDark = ref<boolean>(saved ? saved === 'dark' : prefersDark)

  const applyTheme = () => {
    const htmlElement = document.documentElement
    const bodyElement = document.body

    if (isDark.value) {
      htmlElement.classList.add('dark')
      if (bodyElement) bodyElement.classList.add('dark')
      localStorage.setItem('app-theme', 'dark')
      localStorage.setItem('theme', 'dark')
    } else {
      htmlElement.classList.remove('dark')
      if (bodyElement) bodyElement.classList.remove('dark')
      localStorage.setItem('app-theme', 'light')
      localStorage.setItem('theme', 'light')
    }
  }

  const initializeTheme = () => {
    const s = localStorage.getItem('app-theme') || localStorage.getItem('theme')
    if (s) {
      isDark.value = s === 'dark'
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
    initializeTheme,
    initTheme: initializeTheme,
    toggleTheme,
    setDarkMode,
    applyTheme,
  }
})
