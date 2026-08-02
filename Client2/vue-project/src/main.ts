import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { useThemeStore } from './stores/theme'
import './assets/main.css'
import './styles/dark-mode.css'
import App from './App.vue'
import router from './router'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Initialize theme after pinia is created
const themeStore = useThemeStore()
themeStore.initTheme()

app.mount('#app')
