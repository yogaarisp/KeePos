import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import './index.css'
import App from './App.vue'
import { initNativeApp } from './utils/native'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')

// Initialize native plugins (Capacitor) after mount
initNativeApp()

// Service Worker dinonaktifkan - unregister semua SW yang ada
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.getRegistrations().then(registrations => {
    registrations.forEach(reg => reg.unregister());
  });
}
