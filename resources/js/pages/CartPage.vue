<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import api from '../api';
import AlertMessage from '../components/ui/AlertMessage.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import PageHero from '../components/ui/PageHero.vue';
import { loadSession } from '../session';
import type { CartPayload } from '../types';

const loading = ref(true);
const errorText = ref('');
const cart = ref<CartPayload>({ items: [], total: 0, count: 0 });

async function loadCart() {
  loading.value = true;
  errorText.value = '';

  try {
    const { data } = await api.get<CartPayload>('/cart');
    cart.value = data;
  } catch {
    errorText.value = 'Не удалось загрузить корзину.';
  } finally {
    loading.value = false;
  }
}

async function removeItem(id: number | string) {
  try {
    const { data } = await api.delete(`/cart/${id}`);
    cart.value = data.cart;
    await loadSession();
  } catch {
    errorText.value = 'Не удалось удалить товар.';
  }
}

onMounted(loadCart);
</script>

<template>
  <div>
    <PageHero center>
      <h1 class="text-5xl md:text-7xl font-bold font-display italic text-white leading-none mb-4 tracking-normal"><span class="text-gradient">КОРЗИНА</span></h1>
    </PageHero>

    <div class="bg-dark min-h-screen pb-24 relative">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <p v-if="loading" class="text-gray-500 py-8">Загрузка...</p>
        <AlertMessage v-if="errorText">{{ errorText }}</AlertMessage>

        <div v-if="!loading && cart.items.length" class="bg-dark-lighter border border-white/5 overflow-hidden">
          <div class="divide-y divide-white/5">
            <div v-for="item in cart.items" :key="item.id" class="p-6 flex items-center gap-6 hover:bg-white/[0.02] transition-colors">
              <div class="flex-shrink-0 h-20 w-20 bg-dark rounded overflow-hidden border border-white/10">
                <img :src="item.image" :alt="item.name" class="h-full w-full object-cover" />
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="text-lg font-bold font-display text-white italic uppercase">{{ item.name }}</h3>
                <p class="text-sm text-gray-500 mt-1">Количество: {{ item.quantity }}</p>
              </div>
              <div class="text-right flex items-center gap-6">
                <span class="text-xl font-bold text-primary font-display">{{ item.line_total.toLocaleString('ru-RU') }} ₽</span>
                <button type="button" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-red-400 hover:bg-red-500/10 rounded transition-all" @click="removeItem(item.id)">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
              </div>
            </div>
          </div>

          <div class="p-6 bg-dark border-t border-white/10 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div>
              <span class="text-gray-500 text-sm uppercase tracking-wider font-bold">Итого:</span>
              <span class="text-3xl font-bold text-primary font-display ml-3">{{ cart.total.toLocaleString('ru-RU') }} ₽</span>
            </div>
            <RouterLink to="/checkout" class="btn btn-primary text-xl shadow-lg shadow-primary/20"><span>Оформить заказ</span></RouterLink>
          </div>
        </div>

        <EmptyState v-else-if="!loading" title="Ваша корзина пуста" action-to="/catalog" action-label="Перейти в каталог">
          <template #icon>
            <svg class="w-16 h-16 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0" /></svg>
          </template>
        </EmptyState>
      </div>
    </div>
  </div>
</template>
