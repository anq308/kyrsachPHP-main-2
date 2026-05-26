<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import api from '../api';
import { loadSession, sessionState } from '../session';
import type { Motorcycle, SalesRequestType } from '../types';

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const errorText = ref('');
const successText = ref('');

const motorcycle = ref<Motorcycle | null>(null);
const isFavorite = ref(false);
const isInCompare = ref(false);
const requestModalOpen = ref(false);
const submittingRequest = ref(false);

const requestForm = reactive({
  name: sessionState.user?.name ?? '',
  phone: '',
  email: sessionState.user?.email ?? '',
  type: 'consultation' as SalesRequestType,
  comment: '',
});

async function loadMotorcycle() {
  loading.value = true;
  errorText.value = '';

  try {
    const { data } = await api.get(`/motorcycles/${route.params.id}`);
    motorcycle.value = data.motorcycle;
    isFavorite.value = Boolean(data.is_favorite);
    isInCompare.value = Boolean(data.is_in_compare);
  } catch {
    errorText.value = 'Товар не найден.';
  } finally {
    loading.value = false;
  }
}

async function addToCart(buyNow = false) {
  if (!motorcycle.value) {
    return;
  }

  errorText.value = '';
  successText.value = '';

  try {
    await api.post(`/cart/${motorcycle.value.id}`, { buy_now: buyNow });
    await loadSession();

    if (buyNow) {
      await router.push('/checkout');
      return;
    }

    successText.value = 'Товар добавлен в корзину!';
  } catch {
    errorText.value = 'Не удалось добавить товар в корзину.';
  }
}

function openRequestModal(type: SalesRequestType = 'consultation') {
  requestForm.type = type;
  requestForm.name = requestForm.name || sessionState.user?.name || '';
  requestForm.email = requestForm.email || sessionState.user?.email || '';
  requestModalOpen.value = true;
}

async function submitSalesRequest() {
  if (!motorcycle.value) {
    return;
  }

  errorText.value = '';
  successText.value = '';
  submittingRequest.value = true;

  try {
    const { data } = await api.post('/sales-requests', {
      ...requestForm,
      motorcycle_id: motorcycle.value.id,
    });

    successText.value = data.message ?? 'Заявка отправлена.';
    requestForm.phone = '';
    requestForm.comment = '';
    requestModalOpen.value = false;
  } catch (error: any) {
    errorText.value = error?.response?.data?.message ?? 'Не удалось отправить заявку.';
  } finally {
    submittingRequest.value = false;
  }
}

async function toggleCompare() {
  if (!motorcycle.value) {
    return;
  }

  errorText.value = '';
  successText.value = '';

  try {
    const { data } = await api.post(`/compare/${motorcycle.value.id}`);
    isInCompare.value = !isInCompare.value;
    successText.value = data.message ?? 'Сравнение обновлено.';
    await loadSession();
  } catch (error: any) {
    errorText.value = error?.response?.data?.message ?? 'Ошибка при сравнении.';
  }
}

async function toggleFavorite() {
  if (!motorcycle.value) {
    return;
  }

  if (!sessionState.user) {
    await router.push('/login');
    return;
  }

  errorText.value = '';
  successText.value = '';

  try {
    const { data } = await api.post(`/favorites/${motorcycle.value.id}`);
    isFavorite.value = Boolean(data.is_favorite);
    successText.value = data.message ?? 'Избранное обновлено.';
  } catch {
    errorText.value = 'Ошибка при изменении избранного.';
  }
}

onMounted(loadMotorcycle);
</script>

<template>
  <div>
    <div class="bg-dark-lighter py-6 border-b border-white/5 relative z-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <RouterLink to="/catalog" class="inline-flex items-center text-sm font-bold text-gray-500 hover:text-primary transition-colors uppercase tracking-wider group">
          <svg class="w-4 h-4 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
          Назад в каталог
        </RouterLink>
      </div>
    </div>

    <div class="min-h-screen bg-dark py-12 relative overflow-hidden">
      <div class="absolute top-0 right-0 w-1/3 h-full bg-gradient-to-l from-primary/5 to-transparent skew-x-[-12deg] pointer-events-none" />

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <p v-if="loading" class="text-gray-500">Загрузка...</p>
        <p v-if="errorText" class="mb-5 bg-red-500/10 border border-red-500/30 text-red-400 p-4 text-sm">{{ errorText }}</p>
        <p v-if="successText" class="mb-5 bg-green-500/10 border border-green-500/30 text-green-400 p-4 text-sm">{{ successText }}</p>

        <div v-if="motorcycle" class="lg:grid lg:grid-cols-2 lg:gap-x-16 lg:items-start">
          <div class="flex flex-col">
            <div class="w-full aspect-[4/3] rounded-sm overflow-hidden bg-dark-lighter border border-white/5 relative group shadow-2xl">
              <img :src="motorcycle.image_url" :alt="motorcycle.model" class="w-full h-full object-center object-cover transform group-hover:scale-110 transition-transform duration-700" />
              <div class="absolute inset-0 bg-gradient-to-t from-dark/80 via-transparent to-transparent opacity-60" />

              <div class="absolute top-4 left-4">
                <span class="bg-primary/90 backdrop-blur-sm text-white text-xs font-bold font-display uppercase px-4 py-2 tracking-wider transform -skew-x-12 shadow-lg">
                  <span class="inline-block skew-x-12">{{ motorcycle.type }}</span>
                </span>
              </div>

              <div class="absolute top-4 right-4 flex gap-2">
                <button
                  v-if="sessionState.user"
                  type="button"
                  class="w-10 h-10 flex items-center justify-center rounded-full transition-all shadow-lg"
                  :class="isFavorite ? 'bg-red-500 text-white' : 'bg-dark/80 text-gray-300 hover:bg-red-500 hover:text-white'"
                  @click="toggleFavorite"
                >
                  <svg class="w-5 h-5" :fill="isFavorite ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                </button>

                <button
                  type="button"
                  class="w-10 h-10 flex items-center justify-center rounded-full transition-all shadow-lg"
                  :class="isInCompare ? 'bg-blue-500 text-white' : 'bg-dark/80 text-gray-300 hover:bg-blue-500 hover:text-white'"
                  @click="toggleCompare"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                </button>
              </div>
            </div>
          </div>

          <div class="mt-10 px-4 sm:px-0 sm:mt-16 lg:mt-0">
            <h1 class="text-5xl md:text-6xl font-bold font-display uppercase italic text-white leading-none mb-2 tracking-wide">
              {{ motorcycle.brand }}
              <span class="text-transparent text-stroke">{{ motorcycle.model }}</span>
            </h1>

            <div class="mb-8 flex flex-wrap items-end gap-4 border-b border-white/10 pb-8">
              <p class="text-4xl font-bold text-primary font-display tracking-wider">{{ motorcycle.price.toLocaleString('ru-RU') }} ₽</p>
              <p class="text-sm text-gray-500 mb-2 uppercase tracking-widest hidden sm:block">Цена актуальна</p>
              <span
                class="mb-1 px-3 py-1 text-xs font-bold uppercase tracking-wider border"
                :class="motorcycle.is_available ? 'bg-green-500/10 text-green-300 border-green-500/30' : 'bg-red-500/10 text-red-300 border-red-500/30'"
              >
                {{ motorcycle.is_available ? 'В наличии' : 'Нет в наличии' }}
              </span>
            </div>

            <div class="prose prose-invert prose-lg text-gray-400 mb-10 leading-relaxed font-light">
              <p>{{ motorcycle.description }}</p>
            </div>

            <div class="bg-dark-lighter border border-white/5 p-6 mb-10 relative overflow-hidden group">
              <div class="absolute top-0 right-0 w-2 h-full bg-primary transform scale-y-0 group-hover:scale-y-100 transition-transform duration-300 origin-bottom" />

              <h3 class="text-xl font-bold uppercase font-display text-white mb-6 italic">Характеристики</h3>
              <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                <div class="flex justify-between border-b border-white/5 pb-2">
                  <dt class="text-sm text-gray-500 font-bold uppercase">Тип</dt>
                  <dd class="text-sm font-bold text-white text-right">{{ motorcycle.type }}</dd>
                </div>
                <div class="flex justify-between border-b border-white/5 pb-2">
                  <dt class="text-sm text-gray-500 font-bold uppercase">Год выпуска</dt>
                  <dd class="text-sm font-bold text-white text-right">{{ motorcycle.year }}</dd>
                </div>
                <div class="flex justify-between border-b border-white/5 pb-2">
                  <dt class="text-sm text-gray-500 font-bold uppercase">Объем двигателя</dt>
                  <dd class="text-sm font-bold text-white text-right">{{ motorcycle.engine_capacity }} см³</dd>
                </div>
                <div class="flex justify-between border-b border-white/5 pb-2">
                  <dt class="text-sm text-gray-500 font-bold uppercase">Мощность</dt>
                  <dd class="text-sm font-bold text-white text-right">{{ motorcycle.power }} л.с.</dd>
                </div>
                <div v-if="motorcycle.transmission" class="flex justify-between border-b border-white/5 pb-2">
                  <dt class="text-sm text-gray-500 font-bold uppercase">КПП</dt>
                  <dd class="text-sm font-bold text-white text-right">{{ motorcycle.transmission }}</dd>
                </div>
                <div v-if="motorcycle.cooling" class="flex justify-between border-b border-white/5 pb-2">
                  <dt class="text-sm text-gray-500 font-bold uppercase">Охлаждение</dt>
                  <dd class="text-sm font-bold text-white text-right">{{ motorcycle.cooling }}</dd>
                </div>
                <div v-if="motorcycle.fuel_system" class="flex justify-between border-b border-white/5 pb-2">
                  <dt class="text-sm text-gray-500 font-bold uppercase">Подача топлива</dt>
                  <dd class="text-sm font-bold text-white text-right">{{ motorcycle.fuel_system }}</dd>
                </div>
                <div v-if="motorcycle.weight" class="flex justify-between border-b border-white/5 pb-2">
                  <dt class="text-sm text-gray-500 font-bold uppercase">Вес</dt>
                  <dd class="text-sm font-bold text-white text-right">{{ motorcycle.weight }} кг</dd>
                </div>
                <div v-if="motorcycle.tank_capacity" class="flex justify-between border-b border-white/5 pb-2">
                  <dt class="text-sm text-gray-500 font-bold uppercase">Объем бака</dt>
                  <dd class="text-sm font-bold text-white text-right">{{ motorcycle.tank_capacity }} л</dd>
                </div>
              </dl>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
              <div class="bg-dark-lighter border border-white/5 p-5">
                <p class="text-primary text-xs font-bold uppercase tracking-wider mb-2">Покупка у дилера</p>
                <p class="text-gray-400 text-sm leading-relaxed">Предпродажная подготовка, гарантия и помощь с подбором расходников.</p>
              </div>
              <div class="bg-dark-lighter border border-white/5 p-5">
                <p class="text-primary text-xs font-bold uppercase tracking-wider mb-2">Сервис после покупки</p>
                <p class="text-gray-400 text-sm leading-relaxed">Запись на ТО, диагностику и подготовку техники к сезону прямо из системы.</p>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <button type="button" class="btn btn-primary text-xl shadow-lg shadow-primary/20" @click="addToCart(false)">
                <span>Добавить в корзину</span>
              </button>
              <button type="button" class="btn btn-outline text-xl text-center" @click="openRequestModal('purchase')">
                <span>Оставить заявку</span>
              </button>
              <button
                type="button"
                class="h-14 border border-white/10 bg-dark-lighter text-gray-300 hover:text-white hover:border-red-500/50 hover:bg-red-500/10 transition-all font-display font-bold uppercase tracking-wider"
                @click="toggleFavorite"
              >
                {{ isFavorite ? 'В избранном' : 'Добавить в избранное' }}
              </button>
              <button
                type="button"
                class="h-14 border border-white/10 bg-dark-lighter text-gray-300 hover:text-white hover:border-blue-500/50 hover:bg-blue-500/10 transition-all font-display font-bold uppercase tracking-wider"
                @click="toggleCompare"
              >
                {{ isInCompare ? 'В сравнении' : 'Добавить в сравнение' }}
              </button>
            </div>

            <button type="button" class="mt-4 text-sm font-bold uppercase tracking-wider text-gray-500 hover:text-primary transition-colors" @click="addToCart(true)">
              Оформить покупку сразу
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="requestModalOpen && motorcycle" class="fixed inset-0 z-[80] flex items-center justify-center px-4 py-8 bg-black/70 backdrop-blur-sm" @click.self="requestModalOpen = false">
      <form class="w-full max-w-2xl bg-dark-lighter border border-white/10 shadow-2xl p-6 md:p-8 relative" @submit.prevent="submitSalesRequest">
        <button type="button" class="absolute right-4 top-4 w-9 h-9 border border-white/10 text-gray-500 hover:text-white hover:border-primary transition-colors" @click="requestModalOpen = false">
          ×
        </button>

        <p class="text-primary text-xs font-bold uppercase tracking-[0.25em] mb-3">Заявка на технику</p>
        <h2 class="text-3xl md:text-4xl font-display font-bold text-white uppercase italic mb-2">{{ motorcycle.brand }} {{ motorcycle.model }}</h2>
        <p class="text-gray-500 mb-6">Менеджер уточнит наличие, комплектацию и условия покупки или консультации.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <input v-model="requestForm.name" type="text" required placeholder="Ваше имя" class="field-dark" />
          <input v-model="requestForm.phone" type="text" required placeholder="+7 (___) ___-__-__" class="field-dark" />
          <input v-model="requestForm.email" type="email" placeholder="email" class="field-dark" />
          <select v-model="requestForm.type" class="field-dark">
            <option value="purchase">Покупка</option>
            <option value="consultation">Консультация</option>
            <option value="availability">Уточнить наличие</option>
            <option value="preorder">Предзаказ</option>
          </select>
          <textarea v-model="requestForm.comment" rows="4" placeholder="Комментарий к заявке" class="field-dark md:col-span-2 resize-none" />
        </div>

        <button type="submit" class="btn btn-primary w-full mt-6 text-lg" :disabled="submittingRequest">
          <span>{{ submittingRequest ? 'Отправка...' : 'Отправить заявку' }}</span>
        </button>
      </form>
    </div>
  </div>
</template>
