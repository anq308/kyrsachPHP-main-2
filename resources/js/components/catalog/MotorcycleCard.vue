<script setup lang="ts">
import { RouterLink } from 'vue-router';
import type { Motorcycle } from '../../types';

interface Props {
  motorcycle: Motorcycle;
  variant?: 'catalog' | 'favorites';
  compareActive?: boolean;
  favoriteActive?: boolean;
  showFavorite?: boolean;
  animationDelay?: string;
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'catalog',
  compareActive: false,
  favoriteActive: false,
  showFavorite: true,
  animationDelay: '',
});

const emit = defineEmits<{
  'toggle-compare': [id: number];
  'toggle-favorite': [id: number];
  'add-cart': [id: number];
  'create-request': [motorcycle: Motorcycle];
}>();

function toggleCompare() {
  emit('toggle-compare', props.motorcycle.id);
}

function toggleFavorite() {
  emit('toggle-favorite', props.motorcycle.id);
}

function addToCart() {
  emit('add-cart', props.motorcycle.id);
}

function createRequest() {
  emit('create-request', props.motorcycle);
}
</script>

<template>
  <div
    class="group relative bg-dark-lighter border border-white/5 hover:border-primary/50 hover:shadow-2xl hover:shadow-primary/10 transition-all duration-500 overflow-hidden"
    :style="animationDelay ? `animation-delay:${animationDelay}` : undefined"
  >
    <template v-if="variant === 'favorites'">
      <div class="relative aspect-[4/3] overflow-hidden">
        <div class="absolute inset-0 bg-dark-lighter/50 z-10 transition-opacity duration-500 group-hover:opacity-0" />
        <img :src="motorcycle.image_url" :alt="motorcycle.model" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700 filter grayscale group-hover:grayscale-0" />
        <div class="absolute top-4 left-0 bg-primary/90 backdrop-blur-sm text-white text-xs font-bold font-display uppercase px-3 py-1 tracking-wider z-20 transform -skew-x-12">
          <span class="inline-block skew-x-12">{{ motorcycle.type }}</span>
        </div>

        <button type="button" class="absolute top-4 right-4 z-20 w-10 h-10 bg-red-500/80 hover:bg-red-500 text-white rounded-full flex items-center justify-center transition-colors shadow-lg" @click="toggleFavorite">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
        </button>
      </div>

      <div class="p-6 relative">
        <div class="mb-4">
          <h3 class="text-2xl font-display font-bold text-white italic leading-none group-hover:text-primary transition-colors">
            <RouterLink :to="`/motorcycle/${motorcycle.id}`">
              {{ motorcycle.brand }}
              <span class="block text-gray-400 text-lg not-italic mt-1 font-sans font-normal group-hover:text-white transition-colors">{{ motorcycle.model }}</span>
            </RouterLink>
          </h3>
        </div>
        <div class="flex justify-between items-end">
          <div>
            <span class="text-xs text-gray-500 font-bold uppercase tracking-wider block mb-1">Цена</span>
            <span class="text-2xl font-display font-bold text-primary">{{ motorcycle.price.toLocaleString('ru-RU') }} ₽</span>
          </div>
          <RouterLink :to="`/motorcycle/${motorcycle.id}`" class="w-12 h-12 flex items-center justify-center border border-white/10 text-gray-400 hover:border-primary hover:bg-primary hover:text-white transition-all duration-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
          </RouterLink>
        </div>
      </div>
    </template>

    <template v-else>
      <div class="relative aspect-[4/3] overflow-hidden">
        <div class="absolute inset-0 bg-dark-lighter/50 z-10 transition-opacity duration-500 group-hover:opacity-0" />
        <img :src="motorcycle.image_url" :alt="motorcycle.model" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700 filter grayscale group-hover:grayscale-0" />

        <div class="absolute top-4 left-0 bg-primary/90 backdrop-blur-sm text-white text-xs font-bold font-display uppercase px-3 py-1 tracking-wider z-20 transform -skew-x-12 -translate-x-2 group-hover:translate-x-0 transition-transform duration-300">
          <span class="inline-block skew-x-12">{{ motorcycle.type }}</span>
        </div>

        <div class="absolute top-4 right-4 z-20 px-3 py-1 text-[11px] font-bold uppercase tracking-wider border backdrop-blur-sm" :class="motorcycle.is_available ? 'bg-green-500/15 text-green-300 border-green-500/30' : 'bg-red-500/15 text-red-300 border-red-500/30'">
          {{ motorcycle.is_available ? 'В наличии' : 'Нет в наличии' }}
        </div>

        <div class="absolute inset-0 bg-dark/75 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-500 z-20 backdrop-blur-sm">
          <div class="absolute left-0 top-0 h-full w-1 bg-primary" />
          <RouterLink :to="`/motorcycle/${motorcycle.id}`" class="btn btn-primary transform translate-y-4 group-hover:translate-y-0 opacity-0 group-hover:opacity-100 transition-all duration-500 delay-100">
            <span>Подробнее</span>
          </RouterLink>
        </div>
      </div>

      <div class="p-6 relative">
        <div class="absolute top-0 right-0 w-20 h-full bg-gradient-to-l from-white/5 to-transparent skew-x-[-20deg] transform translate-x-full group-hover:translate-x-0 transition-transform duration-700" />

        <div class="relative z-10">
          <div class="mb-4 min-h-[4rem]">
            <h3 class="text-2xl font-display font-bold text-white leading-none group-hover:text-primary transition-colors">
              <RouterLink :to="`/motorcycle/${motorcycle.id}`">
                {{ motorcycle.brand }}
                <span class="block text-gray-400 text-lg mt-1 font-sans font-normal group-hover:text-white transition-colors">{{ motorcycle.model }}</span>
              </RouterLink>
            </h3>
          </div>

          <div v-if="motorcycle.type !== 'Parts'" class="flex items-center text-sm text-gray-500 font-mono mb-6 space-x-4 border-t border-white/10 pt-4">
            <span v-if="motorcycle.year">{{ motorcycle.year }}</span>
            <span v-if="motorcycle.year" class="w-1 h-1 bg-primary rounded-full" />
            <span v-if="motorcycle.engine_capacity">{{ motorcycle.engine_capacity }}cc</span>
            <span v-if="motorcycle.engine_capacity" class="w-1 h-1 bg-primary rounded-full" />
            <span v-if="motorcycle.power">{{ motorcycle.power }}hp</span>
          </div>
          <div v-else class="h-[2px] w-full bg-white/5 mb-6 mt-4" />

          <div class="grid grid-cols-2 gap-2 mb-5">
            <button
              type="button"
              class="h-11 bg-primary/90 hover:bg-primary text-white text-xs font-bold font-display uppercase tracking-wider transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="!motorcycle.is_available"
              @click="addToCart"
            >
              В корзину
            </button>
            <button
              type="button"
              class="h-11 border border-white/10 bg-dark text-gray-300 hover:border-primary/50 hover:text-white hover:bg-primary/10 text-xs font-bold font-display uppercase tracking-wider transition-all"
              @click="createRequest"
            >
              Заявка
            </button>
          </div>

          <div class="flex justify-between items-end">
            <div>
              <span class="text-xs text-gray-500 font-bold uppercase tracking-wider block mb-1">Цена</span>
              <span class="text-2xl font-display font-bold text-primary">{{ motorcycle.price.toLocaleString('ru-RU') }} ₽</span>
            </div>
            <div class="flex items-center gap-2">
              <button
                type="button"
                title="Сравнить"
                class="w-10 h-10 flex items-center justify-center border transition-all duration-300"
                :class="compareActive ? 'border-blue-500 bg-blue-500/20 text-blue-400' : 'border-white/10 text-gray-500 hover:border-blue-500 hover:text-blue-400'"
                @click="toggleCompare"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
              </button>

              <button
                v-if="showFavorite"
                type="button"
                title="В избранное"
                class="w-10 h-10 flex items-center justify-center border transition-all duration-300"
                :class="favoriteActive ? 'border-red-500 bg-red-500/20 text-red-400' : 'border-white/10 text-gray-500 hover:border-red-500 hover:text-red-400'"
                @click="toggleFavorite"
              >
                <svg class="w-4 h-4" :fill="favoriteActive ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
              </button>

              <RouterLink :to="`/motorcycle/${motorcycle.id}`" class="w-10 h-10 flex items-center justify-center border border-white/10 text-gray-400 hover:border-primary hover:bg-primary hover:text-white transition-all duration-300 group/btn">
                <svg class="w-4 h-4 transform group-hover/btn:rotate-45 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
