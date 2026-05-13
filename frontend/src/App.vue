<template>
  <div :class="{ 'light': themeStore.isLight }">
    <router-view v-slot="{ Component }">
      <transition name="page" mode="out-in">
        <component :is="Component" />
      </transition>
    </router-view>
  </div>
</template>

<script setup>
import { onMounted, reactive, watch, provide } from 'vue';
import { useSettingStore } from './stores/setting';
import { baseUrl } from './api';

const settingStore = useSettingStore();

// Minimal theme management in a reactive object for now, 
// can be moved to a proper store if needed.
const themeStore = reactive({
  isLight: localStorage.getItem('theme') === 'light',
  toggle() {
    this.isLight = !this.isLight;
    localStorage.setItem('theme', this.isLight ? 'light' : 'dark');
    document.documentElement.classList.toggle('light', this.isLight);
  }
});

const updateFavicon = (url) => {
  if (!url) return;
  let link = document.querySelector("link[rel~='icon']");
  if (!link) {
    link = document.createElement('link');
    link.rel = 'icon';
    document.getElementsByTagName('head')[0].appendChild(link);
  }
  // Add timestamp as cache buster to force refresh
  link.href = `${baseUrl}/storage/${url}?v=${new Date().getTime()}`;
};

watch(() => settingStore.settings.shop_favicon, (newFavicon) => {
  if (newFavicon) {
    updateFavicon(newFavicon);
  }
});

watch(() => settingStore.settings.shop_name, (newName) => {
  if (newName && !document.title.includes('|')) {
    document.title = `${newName} | POS System`;
  }
});

onMounted(async () => {
  document.documentElement.classList.toggle('light', themeStore.isLight);
  await settingStore.fetchPublicSettings();
  
  const favicon = settingStore.settings.shop_favicon || settingStore.settings.app_favicon;
  if (favicon) {
    updateFavicon(favicon);
  }
});

// Provide theme to children
provide('theme', themeStore);
</script>

<style>
/* App-wide styles already in index.css */

/* Page Transition */
.page-enter-active,
.page-leave-active {
  transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-enter-from,
.page-leave-to {
  opacity: 0;
}
</style>
