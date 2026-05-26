<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { RouterView } from 'vue-router';
import SiteFooter from './components/layout/SiteFooter.vue';
import SiteHeader from './components/layout/SiteHeader.vue';
import AlertMessage from './components/ui/AlertMessage.vue';
import { loadSession, sessionState } from './session';

const errorText = ref('');

onMounted(async () => {
  if (!sessionState.initialized) {
    try {
      await loadSession();
    } catch {
      sessionState.initialized = true;
    }
  }
});
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <SiteHeader @error="errorText = $event" />

    <main class="flex-grow relative">
      <div class="absolute inset-0 bg-grid-pattern opacity-[0.03] pointer-events-none z-0" />

      <div v-if="errorText" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 relative z-10">
        <AlertMessage title="Ошибка" variant="banner">
          <p>{{ errorText }}</p>
        </AlertMessage>
      </div>

      <RouterView />
    </main>

    <SiteFooter />
  </div>
</template>
