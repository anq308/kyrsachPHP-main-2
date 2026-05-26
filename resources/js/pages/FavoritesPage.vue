<script setup lang="ts">
import { onMounted, ref } from 'vue';
import api from '../api';
import MotorcycleCard from '../components/catalog/MotorcycleCard.vue';
import AlertMessage from '../components/ui/AlertMessage.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import PageHero from '../components/ui/PageHero.vue';
import type { Motorcycle } from '../types';

const loading = ref(true);
const errorText = ref('');
const favorites = ref<Motorcycle[]>([]);

async function loadFavorites() {
  loading.value = true;
  errorText.value = '';

  try {
    const { data } = await api.get('/favorites');
    favorites.value = data.favorites ?? [];
  } catch {
    errorText.value = 'Не удалось загрузить избранное.';
  } finally {
    loading.value = false;
  }
}

async function toggleFavorite(id: number) {
  try {
    await api.post(`/favorites/${id}`);
    favorites.value = favorites.value.filter((item) => item.id !== id);
  } catch {
    errorText.value = 'Не удалось изменить избранное.';
  }
}

onMounted(loadFavorites);
</script>

<template>
  <div>
    <PageHero center>
      <h1 class="text-5xl md:text-7xl font-bold font-display italic text-white leading-none mb-4 tracking-normal">
        <span class="text-gradient">ИЗБРАННОЕ</span>
      </h1>
    </PageHero>

    <div class="bg-dark min-h-screen pb-24 relative">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <p v-if="loading" class="text-gray-500 py-8">Загрузка...</p>
        <AlertMessage v-if="errorText">{{ errorText }}</AlertMessage>

        <EmptyState v-if="!loading && !favorites.length" title="Список избранного пуст" action-to="/catalog" action-label="Перейти в каталог">
          <template #icon>
            <svg class="w-16 h-16 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
          </template>
        </EmptyState>

        <div v-else-if="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <MotorcycleCard
            v-for="(motorcycle, index) in favorites"
            :key="motorcycle.id"
            :motorcycle="motorcycle"
            variant="favorites"
            :animation-delay="`${(index % 3) * 100}ms`"
            @toggle-favorite="toggleFavorite"
          />
        </div>
      </div>
    </div>
  </div>
</template>
