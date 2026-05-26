<script setup lang="ts">
import { onMounted, ref } from 'vue';
import api from '../api';
import AlertMessage from '../components/ui/AlertMessage.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import PageHero from '../components/ui/PageHero.vue';
import { loadSession } from '../session';
import type { Motorcycle } from '../types';

const loading = ref(true);
const errorText = ref('');
const motorcycles = ref<Motorcycle[]>([]);

const specs = [
  { label: 'Тип', key: 'type' as keyof Motorcycle, suffix: '' },
  { label: 'Год выпуска', key: 'year' as keyof Motorcycle, suffix: '' },
  { label: 'Объём двигателя', key: 'engine_capacity' as keyof Motorcycle, suffix: ' см³' },
  { label: 'Мощность', key: 'power' as keyof Motorcycle, suffix: ' л.с.' },
  { label: 'КПП', key: 'transmission' as keyof Motorcycle, suffix: '' },
  { label: 'Охлаждение', key: 'cooling' as keyof Motorcycle, suffix: '' },
  { label: 'Подача топлива', key: 'fuel_system' as keyof Motorcycle, suffix: '' },
  { label: 'Вес', key: 'weight' as keyof Motorcycle, suffix: ' кг' },
  { label: 'Объём бака', key: 'tank_capacity' as keyof Motorcycle, suffix: ' л' },
];

async function loadCompare() {
  loading.value = true;
  errorText.value = '';

  try {
    const { data } = await api.get('/compare');
    motorcycles.value = data.motorcycles ?? [];
  } catch {
    errorText.value = 'Не удалось загрузить сравнение.';
  } finally {
    loading.value = false;
  }
}

async function removeFromCompare(id: number) {
  try {
    await api.post(`/compare/${id}`);
    await loadSession();
    await loadCompare();
  } catch {
    errorText.value = 'Не удалось удалить товар из сравнения.';
  }
}

function specValue(motorcycle: Motorcycle, key: keyof Motorcycle): string {
  const value = motorcycle[key];
  if (value === null || value === undefined || value === '') {
    return '—';
  }
  return String(value);
}

onMounted(loadCompare);
</script>

<template>
  <div>
    <PageHero center>
      <h1 class="text-5xl md:text-7xl font-bold font-display italic text-white leading-none mb-4 tracking-normal">
        <span class="text-gradient">СРАВНЕНИЕ</span> <span class="text-transparent text-stroke">ТОВАРОВ</span>
      </h1>
    </PageHero>

    <div class="bg-dark min-h-screen pb-24 relative">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <p v-if="loading" class="text-gray-500 py-8">Загрузка...</p>
        <AlertMessage v-if="errorText">{{ errorText }}</AlertMessage>

        <EmptyState v-if="!loading && !motorcycles.length" title="Нет товаров для сравнения" action-to="/catalog" action-label="Перейти в каталог">
          <template #icon>
            <svg class="w-16 h-16 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
          </template>
        </EmptyState>

        <div v-else-if="!loading" class="overflow-x-auto">
          <table class="w-full min-w-[600px]">
            <thead>
              <tr>
                <th class="text-left p-4 text-gray-500 font-bold uppercase text-sm tracking-wider w-48 bg-dark-lighter border-b border-white/10">Характеристика</th>
                <th v-for="motorcycle in motorcycles" :key="motorcycle.id" class="p-4 bg-dark-lighter border-b border-white/10 text-center min-w-[200px]">
                  <div class="space-y-3">
                    <div class="w-full aspect-video overflow-hidden rounded-sm border border-white/10 mb-3">
                      <img :src="motorcycle.image_url" :alt="motorcycle.model" class="w-full h-full object-cover" />
                    </div>
                    <h3 class="text-lg font-display font-bold text-white italic">{{ motorcycle.brand }} {{ motorcycle.model }}</h3>
                    <span class="text-primary font-bold font-display text-xl block">{{ motorcycle.price.toLocaleString('ru-RU') }} ₽</span>
                    <button type="button" class="text-xs text-red-400 hover:text-red-300 uppercase tracking-wider font-bold transition-colors" @click="removeFromCompare(motorcycle.id)">
                      Убрать
                    </button>
                  </div>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="spec in specs" :key="spec.key" class="border-b border-white/5 hover:bg-white/[0.02] transition-colors">
                <td class="p-4 text-sm font-bold text-gray-500 uppercase tracking-wider">{{ spec.label }}</td>
                <td v-for="motorcycle in motorcycles" :key="`${motorcycle.id}-${spec.key}`" class="p-4 text-center text-white font-medium">
                  <template v-if="specValue(motorcycle, spec.key) !== '—'">
                    {{ specValue(motorcycle, spec.key) }}{{ spec.suffix }}
                  </template>
                  <span v-else class="text-gray-600">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
