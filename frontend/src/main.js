import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './style.css'

const app = createApp(App)
const pinia = createPinia()
app.use(pinia)
app.use(router)

// Register $t as a global helper available in every component template
// We use a getter so it always reads the current lang from the reactive store
import { useLangStore } from './stores/lang'
app.config.globalProperties.$t = (key) => useLangStore().t(key)
app.config.globalProperties.$lang = useLangStore

app.mount('#app')

// Initialize direction (RTL/LTR) on load
useLangStore().init()
