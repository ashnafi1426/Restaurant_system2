import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { useThemeStore } from './stores/theme'
import { usePageLoaderStore } from './stores/pageLoaderStore'
import './assets/main.css'
import './styles/dark-mode.css'
import App from './App.vue'
import router from './router'

// Show initial loader
const initialLoader = document.getElementById('initial-loader')

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Initialize theme after pinia is created
const themeStore = useThemeStore()
themeStore.initTheme()

// Initialize the page loader store with loading state
const loaderStore = usePageLoaderStore()
loaderStore.showLoader('Loading...')

// Wait for router to be ready and hide initial loader
router.isReady().then(() => {
  // Hide the initial HTML loader if it exists
  if (initialLoader) {
    initialLoader.style.opacity = '0'
    setTimeout(() => {
      initialLoader.remove()
    }, 300)
  }
  
  app.mount('#app')
  
  // Hide the Vue loader after mount - with longer delay for content to render
  setTimeout(() => {
    loaderStore.hideLoader()
  }, 1000) // Increased to 1 second to ensure page is fully rendered
})
