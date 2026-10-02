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

router.isReady().then(async () => {
  const authStore = useAuthStore(pinia)
  if (authStore.token) {
    try {
      await authStore.initializeAuth()
    } catch (e) {
      console.error('[MAIN] Session initialization error:', e)
    }
  }

  if (initialLoader) {
    initialLoader.style.opacity = '0'
    setTimeout(() => {
      initialLoader.remove()
    }, 300)
  }
  
  app.mount('#app')
  
  setTimeout(() => {
    loaderStore.hideLoader()
  }, 1000)
})
