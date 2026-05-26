<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../api';
import MotorcycleCard from '../components/catalog/MotorcycleCard.vue';
import AlertMessage from '../components/ui/AlertMessage.vue';
import PageHero from '../components/ui/PageHero.vue';
import { loadSession, sessionState } from '../session';
import type { Motorcycle, SalesRequestType } from '../types';

interface CatalogResponse {
  data: Motorcycle[];
  current_page: number;
  last_page: number;
  total: number;
  filters?: CatalogFilters;
}

interface CatalogFilters {
  brands: string[];
  years: number[];
  price: {
    min: number;
    max: number;
  };
  engine: {
    min: number;
    max: number;
  };
  power: {
    min: number;
    max: number;
  };
}

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const toggling = ref(false);
const errorText = ref('');
const successText = ref('');

const motorcycles = ref<Motorcycle[]>([]);
const total = ref(0);
const currentPage = ref(1);
const lastPage = ref(1);

const type = ref('');
const brand = ref('');
const year = ref('');
const availability = ref('available');
const priceMin = ref('');
const priceMax = ref('');
const engineMin = ref('');
const engineMax = ref('');
const powerMin = ref('');
const powerMax = ref('');
const search = ref('');
const sort = ref('newest');
const filterMeta = ref<CatalogFilters>({
  brands: [],
  years: [],
  price: { min: 0, max: 0 },
  engine: { min: 0, max: 0 },
  power: { min: 0, max: 0 },
});

const compareIds = ref<number[]>([]);
const favoriteIds = ref<number[]>([]);
const requestModalOpen = ref(false);
const selectedMotorcycle = ref<Motorcycle | null>(null);
const submittingRequest = ref(false);

const requestForm = reactive({
  name: sessionState.user?.name ?? '',
  phone: '',
  email: sessionState.user?.email ?? '',
  type: 'consultation' as SalesRequestType,
  comment: '',
});

const sortOptions = [
  { value: 'newest', label: 'Новинки' },
  { value: 'price_asc', label: 'Цена ↑' },
  { value: 'price_desc', label: 'Цена ↓' },
  { value: 'popular', label: 'Популярные' },
  { value: 'oldest', label: 'Сначала старые' },
];

function syncFromQuery() {
  type.value = typeof route.query.type === 'string' ? route.query.type : '';
  brand.value = typeof route.query.brand === 'string' ? route.query.brand : '';
  year.value = typeof route.query.year === 'string' ? route.query.year : '';
  availability.value = typeof route.query.availability === 'string' ? route.query.availability : 'available';
  priceMin.value = typeof route.query.price_min === 'string' ? route.query.price_min : '';
  priceMax.value = typeof route.query.price_max === 'string' ? route.query.price_max : '';
  engineMin.value = typeof route.query.engine_min === 'string' ? route.query.engine_min : '';
  engineMax.value = typeof route.query.engine_max === 'string' ? route.query.engine_max : '';
  powerMin.value = typeof route.query.power_min === 'string' ? route.query.power_min : '';
  powerMax.value = typeof route.query.power_max === 'string' ? route.query.power_max : '';
  search.value = typeof route.query.search === 'string' ? route.query.search : '';
  sort.value = typeof route.query.sort === 'string' ? route.query.sort : 'newest';
  currentPage.value = Math.max(1, Number(route.query.page ?? 1) || 1);
}

function buildQuery(page = 1) {
  return {
    ...(type.value ? { type: type.value } : {}),
    ...(brand.value ? { brand: brand.value } : {}),
    ...(year.value ? { year: year.value } : {}),
    ...(availability.value && availability.value !== 'available' ? { availability: availability.value } : {}),
    ...(priceMin.value ? { price_min: priceMin.value } : {}),
    ...(priceMax.value ? { price_max: priceMax.value } : {}),
    ...(engineMin.value ? { engine_min: engineMin.value } : {}),
    ...(engineMax.value ? { engine_max: engineMax.value } : {}),
    ...(powerMin.value ? { power_min: powerMin.value } : {}),
    ...(powerMax.value ? { power_max: powerMax.value } : {}),
    ...(search.value ? { search: search.value } : {}),
    ...(sort.value && sort.value !== 'newest' ? { sort: sort.value } : {}),
    ...(page > 1 ? { page } : {}),
  };
}

function hasActiveFilters() {
  return Boolean(
    type.value ||
      brand.value ||
      year.value ||
      availability.value !== 'available' ||
      priceMin.value ||
      priceMax.value ||
      engineMin.value ||
      engineMax.value ||
      powerMin.value ||
      powerMax.value ||
      search.value,
  );
}

async function resetFilters() {
  type.value = '';
  brand.value = '';
  year.value = '';
  availability.value = 'available';
  priceMin.value = '';
  priceMax.value = '';
  engineMin.value = '';
  engineMax.value = '';
  powerMin.value = '';
  powerMax.value = '';
  search.value = '';
  await applyFilters(1);
}

async function loadCompareAndFavorites() {
  try {
    const { data } = await api.get('/compare');
    compareIds.value = data.ids ?? [];
  } catch {
    compareIds.value = [];
  }

  if (sessionState.user) {
    try {
      const { data } = await api.get('/favorites');
      favoriteIds.value = (data.favorites ?? []).map((m: Motorcycle) => m.id);
    } catch {
      favoriteIds.value = [];
    }
  } else {
    favoriteIds.value = [];
  }
}

async function loadCatalog() {
  loading.value = true;
  errorText.value = '';

  try {
    const { data } = await api.get<CatalogResponse>('/catalog', {
      params: {
        page: currentPage.value,
        type: type.value || undefined,
        brand: brand.value || undefined,
        year: year.value || undefined,
        availability: availability.value || undefined,
        price_min: priceMin.value || undefined,
        price_max: priceMax.value || undefined,
        engine_min: engineMin.value || undefined,
        engine_max: engineMax.value || undefined,
        power_min: powerMin.value || undefined,
        power_max: powerMax.value || undefined,
        search: search.value || undefined,
        sort: sort.value,
      },
    });

    motorcycles.value = data.data ?? [];
    currentPage.value = data.current_page ?? 1;
    lastPage.value = data.last_page ?? 1;
    total.value = data.total ?? motorcycles.value.length;
    filterMeta.value = data.filters ?? filterMeta.value;
    await loadCompareAndFavorites();
  } catch {
    errorText.value = 'Не удалось загрузить каталог.';
  } finally {
    loading.value = false;
  }
}

async function applyFilters(page = 1) {
  await router.push({ path: '/catalog', query: buildQuery(page) });
}

async function toggleCompare(id: number) {
  if (toggling.value) {
    return;
  }

  toggling.value = true;
  errorText.value = '';
  successText.value = '';

  try {
    const { data } = await api.post(`/compare/${id}`);
    compareIds.value = data.ids ?? compareIds.value;
    successText.value = data.message ?? 'Список сравнения обновлен.';
  } catch (error: any) {
    errorText.value = error?.response?.data?.message ?? 'Не удалось изменить список сравнения.';
  } finally {
    toggling.value = false;
  }
}

async function toggleFavorite(id: number) {
  if (!sessionState.user) {
    await router.push('/login');
    return;
  }

  if (toggling.value) {
    return;
  }

  toggling.value = true;
  errorText.value = '';
  successText.value = '';

  try {
    const { data } = await api.post(`/favorites/${id}`);
    if (data.is_favorite) {
      favoriteIds.value = [...new Set([...favoriteIds.value, id])];
    } else {
      favoriteIds.value = favoriteIds.value.filter((itemId) => itemId !== id);
    }
    successText.value = data.message ?? 'Список избранного обновлён.';
  } catch {
    errorText.value = 'Не удалось изменить избранное.';
  } finally {
    toggling.value = false;
  }
}

async function addToCart(id: number) {
  errorText.value = '';
  successText.value = '';

  try {
    await api.post(`/cart/${id}`);
    await loadSession();
    successText.value = 'Товар добавлен в корзину.';
  } catch {
    errorText.value = 'Не удалось добавить товар в корзину.';
  }
}

function openRequestModal(motorcycle: Motorcycle, requestType: SalesRequestType = 'consultation') {
  selectedMotorcycle.value = motorcycle;
  requestForm.type = requestType;
  requestForm.name = requestForm.name || sessionState.user?.name || '';
  requestForm.email = requestForm.email || sessionState.user?.email || '';
  requestModalOpen.value = true;
}

async function submitSalesRequest() {
  if (!selectedMotorcycle.value) {
    return;
  }

  errorText.value = '';
  successText.value = '';
  submittingRequest.value = true;

  try {
    const { data } = await api.post('/applications', {
      ...requestForm,
      motorcycle_id: selectedMotorcycle.value.id,
    });

    successText.value = data.message ?? 'Заявка отправлена. Менеджер свяжется с вами для консультации.';
    requestForm.phone = '';
    requestForm.comment = '';
    requestModalOpen.value = false;
  } catch (error: any) {
    errorText.value = error?.response?.data?.message ?? 'Не удалось отправить заявку.';
  } finally {
    submittingRequest.value = false;
  }
}

const noResultsForSearch = computed(() => !loading.value && hasActiveFilters() && motorcycles.value.length === 0);

watch(
  () => route.query,
  async () => {
    syncFromQuery();
    await loadCatalog();
  },
  { deep: true },
);

onMounted(async () => {
  syncFromQuery();
  await loadCatalog();
});
</script>

<template>
  <div>
    <PageHero
      background-image="https://commons.wikimedia.org/wiki/Special:FilePath/Motocross-action.jpg"
      image-alt="Catalog Background"
      image-class="opacity-30 scale-110"
      overlay-class="bg-gradient-to-b from-dark via-dark/80 to-dark"
      :compact="false"
      center
    >
      <p class="text-primary font-bold text-sm md:text-base tracking-[0.3em] uppercase mb-4 pl-1">Наш Гараж</p>
      <h1 class="text-5xl md:text-7xl font-bold font-display italic text-white leading-none mb-6 tracking-normal shadow-orange-glow">
        <span class="text-gradient">ТОВАРЫ</span> <span class="text-transparent text-stroke">AVANTIS</span>
      </h1>
      <p class="max-w-2xl mx-auto text-gray-400 text-lg md:text-xl font-light leading-relaxed">
        Только проверенные бойцы. Эндуро, квадроциклы и комплектующие, готовые к самым жестким испытаниям.
      </p>
    </PageHero>

    <div class="bg-dark min-h-screen pb-24 relative">
      <div class="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-white/10 to-transparent" />

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8 relative z-20">
        <form class="bg-dark-lighter border border-white/10 p-4 md:p-6 mb-8 shadow-2xl" @submit.prevent="applyFilters(1)">
          <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
            <div class="relative md:col-span-4">
              <input
                v-model="search"
                type="text"
                class="field-dark pl-12"
                placeholder="Поиск по бренду, модели, описанию"
              />
              <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" /></svg>
            </div>

            <select v-model="brand" class="field-dark md:col-span-2">
              <option value="">Все бренды</option>
              <option v-for="item in filterMeta.brands" :key="item" :value="item">{{ item }}</option>
            </select>

            <select v-model="year" class="field-dark md:col-span-2">
              <option value="">Любой год</option>
              <option v-for="item in filterMeta.years" :key="item" :value="String(item)">{{ item }}</option>
            </select>

            <select v-model="availability" class="field-dark md:col-span-2">
              <option value="available">В наличии</option>
              <option value="all">Все статусы</option>
              <option value="unavailable">Нет в наличии</option>
            </select>

            <select v-model="sort" class="field-dark md:col-span-2">
              <option v-for="option in sortOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>

            <button type="submit" class="btn btn-primary md:col-span-2">
              <span>Показать</span>
            </button>
          </div>

          <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mt-3">
            <input v-model="priceMin" type="number" min="0" class="field-dark" placeholder="Цена от" />
            <input v-model="priceMax" type="number" min="0" class="field-dark" placeholder="Цена до" />
            <input v-model="engineMin" type="number" min="0" class="field-dark" placeholder="Объём от, см³" />
            <input v-model="engineMax" type="number" min="0" class="field-dark" placeholder="Объём до, см³" />
            <input v-model="powerMin" type="number" min="0" class="field-dark" placeholder="Мощность от" />
            <input v-model="powerMax" type="number" min="0" class="field-dark" placeholder="Мощность до" />
          </div>

          <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-gray-600 uppercase tracking-wider">
              Диапазон каталога: {{ filterMeta.price.min.toLocaleString('ru-RU') }}–{{ filterMeta.price.max.toLocaleString('ru-RU') }} ₽ · {{ filterMeta.engine.min }}–{{ filterMeta.engine.max }} см³ · {{ filterMeta.power.min }}–{{ filterMeta.power.max }} л.с.
            </p>
            <button v-if="hasActiveFilters()" type="button" class="text-xs font-bold uppercase tracking-wider text-gray-400 hover:text-primary transition-colors" @click="resetFilters">
              Сбросить фильтры
            </button>
          </div>
        </form>

        <div class="flex flex-wrap justify-center gap-4 mb-12">
          <button
            class="px-8 py-3 font-display font-bold uppercase tracking-wider text-sm transition-all duration-300 border border-white/10 transform -skew-x-12"
            :class="!type ? 'bg-primary border-primary text-white hover:bg-primary/90' : 'bg-dark-lighter text-gray-400 hover:border-primary hover:text-primary hover:bg-white/5'"
            @click="type = ''; applyFilters(1)"
          >
            <span class="inline-block skew-x-12">Все</span>
          </button>
          <button
            class="px-8 py-3 font-display font-bold uppercase tracking-wider text-sm transition-all duration-300 border border-white/10 transform -skew-x-12"
            :class="type === 'Enduro' ? 'bg-primary border-primary text-white hover:bg-primary/90' : 'bg-dark-lighter text-gray-400 hover:border-primary hover:text-primary hover:bg-white/5'"
            @click="type = 'Enduro'; applyFilters(1)"
          >
            <span class="inline-block skew-x-12">Эндуро</span>
          </button>
          <button
            class="px-8 py-3 font-display font-bold uppercase tracking-wider text-sm transition-all duration-300 border border-white/10 transform -skew-x-12"
            :class="type === 'ATV' ? 'bg-primary border-primary text-white hover:bg-primary/90' : 'bg-dark-lighter text-gray-400 hover:border-primary hover:text-primary hover:bg-white/5'"
            @click="type = 'ATV'; applyFilters(1)"
          >
            <span class="inline-block skew-x-12">Квадроциклы</span>
          </button>
          <button
            class="px-8 py-3 font-display font-bold uppercase tracking-wider text-sm transition-all duration-300 border border-white/10 transform -skew-x-12"
            :class="type === 'Parts' ? 'bg-primary border-primary text-white hover:bg-primary/90' : 'bg-dark-lighter text-gray-400 hover:border-primary hover:text-primary hover:bg-white/5'"
            @click="type = 'Parts'; applyFilters(1)"
          >
            <span class="inline-block skew-x-12">Запчасти</span>
          </button>
        </div>

        <AlertMessage v-if="successText" tone="success">{{ successText }}</AlertMessage>
        <AlertMessage v-if="errorText">{{ errorText }}</AlertMessage>
        <div v-if="loading" class="text-center text-gray-500 py-16">Загрузка каталога...</div>

        <div v-if="noResultsForSearch" class="text-center py-16">
          <p class="text-2xl text-gray-500 font-display uppercase">По выбранным параметрам ничего не найдено</p>
          <button class="mt-4 inline-block text-primary hover:text-white transition-colors font-bold uppercase tracking-wider text-sm" @click="resetFilters">
            Сбросить фильтры
          </button>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <MotorcycleCard
            v-for="motorcycle in motorcycles"
            :key="motorcycle.id"
            :motorcycle="motorcycle"
            :compare-active="compareIds.includes(motorcycle.id)"
            :favorite-active="favoriteIds.includes(motorcycle.id)"
            :show-favorite="true"
            @toggle-compare="toggleCompare"
            @toggle-favorite="toggleFavorite"
            @add-cart="addToCart"
            @create-request="openRequestModal"
          />
        </div>

        <div class="mt-20 flex flex-wrap justify-center items-center gap-4" v-if="lastPage > 1">
          <button class="px-5 py-2 text-xs font-bold uppercase tracking-wider transition-all duration-300 border border-white/10 text-gray-400 hover:border-primary hover:text-primary disabled:opacity-40 disabled:cursor-not-allowed" :disabled="currentPage <= 1" @click="applyFilters(currentPage - 1)">
            Назад
          </button>

          <span class="text-sm text-gray-500">Страница {{ currentPage }} из {{ lastPage }} · {{ total }} товаров</span>

          <button class="px-5 py-2 text-xs font-bold uppercase tracking-wider transition-all duration-300 border border-white/10 text-gray-400 hover:border-primary hover:text-primary disabled:opacity-40 disabled:cursor-not-allowed" :disabled="currentPage >= lastPage" @click="applyFilters(currentPage + 1)">
            Вперёд
          </button>
        </div>
      </div>
    </div>

    <div v-if="requestModalOpen && selectedMotorcycle" class="fixed inset-0 z-[80] flex items-center justify-center px-4 py-8 bg-black/70 backdrop-blur-sm" @click.self="requestModalOpen = false">
      <form class="w-full max-w-2xl bg-dark-lighter border border-white/10 shadow-2xl p-6 md:p-8 relative" @submit.prevent="submitSalesRequest">
        <button type="button" class="absolute right-4 top-4 w-9 h-9 border border-white/10 text-gray-500 hover:text-white hover:border-primary transition-colors" @click="requestModalOpen = false">
          ×
        </button>

        <p class="text-primary text-xs font-bold uppercase tracking-[0.25em] mb-3">Заявка на покупку</p>
        <h2 class="text-3xl md:text-4xl font-display font-bold text-white uppercase italic mb-2">{{ selectedMotorcycle.brand }} {{ selectedMotorcycle.model }}</h2>
        <p class="text-gray-500 mb-6">Менеджер уточнит наличие, комплектацию, условия покупки или предзаказа.</p>

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
