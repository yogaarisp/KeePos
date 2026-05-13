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
import { updatePlatformMeta } from './utils/metaTags';

const settingStore = useSettingStore();

// Theme management
const themeStore = reactive({
  isLight: localStorage.getItem('theme') === 'light',
  toggle() {
    this.isLight = !this.isLight;
    localStorage.setItem('theme', this.isLight ? 'light' : 'dark');
    document.documentElement.classList.toggle('light', this.isLight);
  }
});

// Watch for settings changes and update all meta tags
watch(() => settingStore.settings, (settings) => {
  if (settings && (settings.app_name || settings.shop_name)) {
    updatePlatformMeta(settings, baseUrl);
  }
}, { deep: true });

onMounted(async () => {
  document.documentElement.classList.toggle('light', themeStore.isLight);

  // Fetch public settings (logo, favicon, app name, dll)
  await settingStore.fetchPublicSettings();

  // Apply all meta tags from settings
  const s = settingStore.settings;
  if (s && (s.app_name || s.shop_name || s.app_logo || s.app_favicon)) {
    updatePlatformMeta(s, baseUrl);
  }
});

// Provide theme to children
provide('theme', themeStore);
</script>

<style>
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
