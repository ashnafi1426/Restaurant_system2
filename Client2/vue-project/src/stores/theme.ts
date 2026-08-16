import { defineStore } from 'pinia'

export const useThemeStore = defineStore('theme', {
  state: () => ({
    isDark: (localStorage.getItem('app-theme') || localStorage.getItem('theme')) === 'dark' || 
            (!('app-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),
  }),

  actions: {
    initTheme() {
      if (this.isDark) {
        document.documentElement.classList.add('dark')
      } else {
        document.documentElement.classList.remove('dark')
      }
    },

    toggleTheme() {
      this.isDark = !this.isDark
      if (this.isDark) {
        document.documentElement.classList.add('dark')
        localStorage.setItem('app-theme', 'dark')
        localStorage.setItem('theme', 'dark')
      } else {
        document.documentElement.classList.remove('dark')
        localStorage.setItem('app-theme', 'light')
        localStorage.setItem('theme', 'light')
      }
    },
  },
})

