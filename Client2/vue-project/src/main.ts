import { createApp } from 'vue'
import { createPinia } from 'pinia'
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

app.use(pinia)
app.use(router)

const themeStore = useThemeStore()
themeStore.initTheme()

const languageStore = useLanguageStore()
app.config.globalProperties.$t = (key: string, fallback?: string) => languageStore.t(key, fallback)
app.config.globalProperties.t = (key: string, fallback?: string) => languageStore.t(key, fallback)

const loaderStore = usePageLoaderStore()
loaderStore.showLoader('Loading...')

router.isReady().then(async () => {
  const authStore = useAuthStore()
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
