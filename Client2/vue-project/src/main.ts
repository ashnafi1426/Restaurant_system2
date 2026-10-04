import { createApp } from 'vue'
import { createPinia, setActivePinia } from 'pinia'
import { useThemeStore } from './stores/theme'
import { usePageLoaderStore } from './stores/pageLoaderStore'
import { useAuthStore } from './stores/auth'
import { useLanguageStore } from './stores/language'
import './assets/main.css'
import './styles/dark-mode.css'
import App from './App.vue'
import router from './router'

const initialLoader = document.getElementById('initial-loader')

const app = createApp(App)
const pinia = createPinia()

// Activate Pinia for usage outside Vue components (main.ts, router guards)
setActivePinia(pinia)
app.use(pinia)
app.use(router)

const themeStore = useThemeStore(pinia)
themeStore.initTheme()

const languageStore = useLanguageStore(pinia)
app.config.globalProperties.$t = (key: string, fallback?: string) => languageStore.t(key, fallback)
app.config.globalProperties.t = (key: string, fallback?: string) => languageStore.t(key, fallback)

const loaderStore = usePageLoaderStore(pinia)
loaderStore.showLoader('Loading...')

// OPTIMIZED: Don't block app initialization on auth
router.isReady().then(async () => {
  // Mount app immediately - don't wait for auth
  if (initialLoader) {
    initialLoader.style.opacity = '0'
    setTimeout(() => {
      initialLoader.remove()
    }, 300)
  }
  
  app.mount('#app')
  
  // Initialize auth in background (non-blocking)
  const authStore = useAuthStore(pinia)
  if (authStore.token) {
    // Don't await - let it happen in background
    authStore.initializeAuth().catch((e) => {
      console.error('[MAIN] Session initialization error:', e)
    })
  }
  
  // Hide loader quickly
  setTimeout(() => {
    loaderStore.hideLoader()
  }, 500) // Reduced from 1000ms to 500ms
})