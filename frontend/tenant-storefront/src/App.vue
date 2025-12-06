<script setup lang="ts">
import { RouterView, useRoute } from 'vue-router'
import { onMounted, watch } from 'vue'
import { trackEvent } from '@/services/tracking'

const route = useRoute();

// Track the initial page view
onMounted(() => {
  // Use a short delay to ensure the route is fully resolved
  setTimeout(() => {
    trackEvent('page_view', {
      path: route.path,
      name: route.name?.toString() || 'unknown',
      params: route.params,
    });
  }, 100);
});

// Track subsequent page views as the user navigates
watch(() => route.path, (newPath, oldPath) => {
  // Avoid tracking the initial load twice
  if (newPath !== oldPath) {
    trackEvent('page_view', {
      path: newPath,
      name: route.name?.toString() || 'unknown',
      params: route.params,
    });
  }
});
</script>

<template>
  <RouterView />
</template>

<style scoped>
</style>
