import { createApp } from 'vue'
import { createPinia, setActivePinia } from 'pinia'
import { useThemeStore } from './stores/theme'
import { useLanguageStore } from './stores/language'
import './assets/main.css'
import './styles/dark-mode.css'
import './plugins/echo' // Initialize Laravel Echo WebSocket connection
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

router.isReady().then(() => {
  app.mount('#app')

  if (initialLoader) {
    initialLoader.style.opacity = '0'
    setTimeout(() => {
      initialLoader.remove()
    }, 200)
  }
})
