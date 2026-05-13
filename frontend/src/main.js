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

// Register Service Worker for PWA
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch(err => {
      console.log('SW registration failed: ', err);
    });
  });
}
