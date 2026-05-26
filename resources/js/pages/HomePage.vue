<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import api from '../api';
import type { Motorcycle, NewsItem } from '../types';

const loading = ref(true);
const errorText = ref('');
const popular = ref<Motorcycle[]>([]);
const news = ref<NewsItem[]>([]);
const submitError = ref('');
const submitSuccess = ref('');
const submitting = ref(false);

const leadForm = reactive({
  name: '',
  phone: '',
  email: '',
  comment: '',
});

const workflowSteps = [
  ['01', 'Выбор модели', 'Клиент выбирает технику в каталоге, сравнивает характеристики и сохраняет варианты.'],
  ['02', 'Заказ или заявка', 'Оформляется заказ, консультация, уточнение наличия или предзаказ.'],
  ['03', 'Обработка менеджером', 'Сотрудник видит обращение в админ-панели и меняет статус работы.'],
  ['04', 'Покупка или сервис', 'Клиент получает технику либо записывается на обслуживание AVANTIS.'],
  ['05', 'Контроль статуса', 'История заказов, заявок и сервисных записей доступна в личном кабинете.'],
];

const capabilityCards = [
  ['Каталог мототехники', 'Поиск, фильтры, характеристики, сравнение и избранное.'],
  ['Оформление заказов', 'Корзина, контактные данные клиента и история покупок.'],
  ['Заявки на покупку', 'Консультации, предзаказы и уточнение наличия по конкретной модели.'],
  ['Сервисное обслуживание', 'Онлайн-запись на диагностику, ТО и ремонт техники.'],
  ['Личный кабинет', 'Статусы заказов, заявок на покупку и сервисных обращений.'],
  ['Панель менеджера', 'Управление товарами, заказами, клиентами и обращениями.'],
];

onMounted(async () => {
  try {
    const { data } = await api.get('/home');
    popular.value = data.popularMotorcycles ?? [];
    news.value = data.news ?? [];
  } catch {
    errorText.value = 'Не удалось загрузить данные главной страницы.';
  } finally {
    loading.value = false;
  }
});

async function submitLeadForm() {
  submitError.value = '';
  submitSuccess.value = '';
  submitting.value = true;

  try {
    const { data } = await api.post('/applications', {
      ...leadForm,
      type: 'consultation',
    });
    submitSuccess.value = data.message ?? 'Заявка отправлена.';
    leadForm.name = '';
    leadForm.phone = '';
    leadForm.email = '';
    leadForm.comment = '';
  } catch (error: any) {
    submitError.value = error?.response?.data?.message ?? 'Не удалось отправить заявку.';
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <div>
    <div class="relative bg-dark overflow-hidden min-h-screen flex items-center">
      <div class="absolute inset-0 z-0">
        <img :src="'/images/hero_bg.png'" class="w-full h-full object-cover opacity-40 scale-110" alt="Motocross Action" />
        <div class="absolute inset-0 bg-gradient-to-r from-dark via-dark/80 to-transparent" />
        <div class="absolute inset-0 bg-gradient-to-t from-dark via-transparent to-transparent" />
      </div>

      <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full pt-20">
        <div class="max-w-4xl">
          <h1 class="text-5xl md:text-7xl lg:text-8xl font-bold font-display italic text-white leading-none mb-8 tracking-normal shadow-orange-glow">
            <span class="text-gradient">Продажа и сервисное</span>
            <span class="block text-stroke text-transparent">обслуживание мототехники AVANTIS</span>
          </h1>
          <p class="text-gray-300 text-lg md:text-2xl max-w-2xl font-light mb-12 border-l-4 border-primary pl-6">
            Информационная система для выбора техники, оформления заказов, обработки заявок и записи клиентов на сервисное обслуживание.
          </p>
          <div class="flex flex-col sm:flex-row gap-6">
            <RouterLink to="/catalog" class="btn btn-primary text-xl">
              <span>Перейти в каталог</span>
            </RouterLink>
            <RouterLink to="/service" class="btn btn-outline text-xl">
              <span>Записаться на сервис</span>
            </RouterLink>
            <a href="#lead-form" class="btn btn-outline text-xl">
              <span>Оставить заявку</span>
            </a>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-12 max-w-3xl">
            <RouterLink to="/catalog" class="hero-flow-card">
              <span class="text-primary font-display font-bold text-2xl">01</span>
              <span class="text-white font-display font-bold uppercase">Продажа техники</span>
              <span class="text-gray-500 text-sm">Каталог, сравнение, избранное и заказ.</span>
            </RouterLink>
            <RouterLink to="/service" class="hero-flow-card">
              <span class="text-primary font-display font-bold text-2xl">02</span>
              <span class="text-white font-display font-bold uppercase">Сервис</span>
              <span class="text-gray-500 text-sm">Запись на диагностику и обслуживание.</span>
            </RouterLink>
            <RouterLink to="/profile" class="hero-flow-card">
              <span class="text-primary font-display font-bold text-2xl">03</span>
              <span class="text-white font-display font-bold uppercase">Личный кабинет</span>
              <span class="text-gray-500 text-sm">Заказы и статусы заявок клиента.</span>
            </RouterLink>
          </div>
        </div>
      </div>

      <div class="absolute bottom-0 right-0 w-2/3 h-32 bg-dark-lighter clip-diagonal-reverse z-20 hidden md:block" />
    </div>

    <section class="py-20 bg-dark-lighter border-y border-white/5">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-12 animate-reveal">
          <div>
            <p class="text-primary text-xs font-bold uppercase tracking-[0.25em] mb-3">Информационная система</p>
            <h2 class="text-4xl md:text-6xl font-display font-bold italic text-white tracking-normal">ВОЗМОЖНОСТИ AVANTIS</h2>
            <div class="h-1 w-20 bg-primary mt-4" />
          </div>
          <p class="text-gray-400 max-w-xl text-lg">
            Сайт объединяет продажу мототехники, сервисное обслуживание и рабочее место менеджера в одном понятном процессе.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <article v-for="(card, index) in capabilityCards" :key="card[0]" class="reveal-card bg-dark border border-white/5 p-6 hover:border-primary/40 hover:-translate-y-1 transition-all duration-300" :style="`animation-delay:${index * 60}ms`">
            <div class="flex items-center justify-between mb-8">
              <span class="text-primary font-display font-bold text-3xl">{{ String(index + 1).padStart(2, '0') }}</span>
              <span class="w-10 h-px bg-white/10" />
            </div>
            <h3 class="text-xl font-display font-bold text-white uppercase mb-3">{{ card[0] }}</h3>
            <p class="text-gray-500 leading-relaxed">{{ card[1] }}</p>
          </article>
        </div>
      </div>
    </section>

    <section class="py-24 bg-dark relative overflow-hidden">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="flex flex-col md:flex-row justify-between items-end mb-16">
          <div>
            <h2 class="text-5xl md:text-7xl font-bold font-display italic text-white tracking-normal mb-2">
              ВЫБЕРИ <span class="text-primary">СВОЙ</span> ПУТЬ
            </h2>
            <div class="h-2 w-24 bg-primary" />
          </div>
          <RouterLink to="/catalog" class="hidden md:inline-flex items-center text-gray-400 hover:text-white transition-colors group">
            <span class="mr-2 uppercase tracking-widest text-sm font-bold">Смотреть все</span>
            <svg class="w-6 h-6 transform group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
          </RouterLink>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <RouterLink to="/catalog?type=Enduro" class="lg:col-span-8 relative group cursor-pointer block overflow-hidden rounded-sm border border-white/5 hover:border-primary/50 transition-all duration-500 bg-dark-lighter">
            <div class="aspect-[16/9] lg:aspect-[16/8] relative overflow-hidden">
              <img :src="'/images/category_enduro.png'" alt="Enduro" class="object-cover w-full h-full transform group-hover:scale-110 transition-transform duration-700 filter group-hover:brightness-110" />
              <div class="absolute inset-0 bg-gradient-to-t from-dark via-dark/40 to-transparent opacity-90 group-hover:opacity-80 transition-opacity" />
              <div class="absolute top-0 right-0 w-32 h-32 bg-primary/20 blur-3xl rounded-full transform translate-x-1/2 -translate-y-1/2 group-hover:bg-primary/40 transition-colors duration-500" />
              
              <div class="absolute bottom-0 left-0 p-8 md:p-12 w-full">
                 <div class="flex justify-between items-end">
                   <div>
                      <span class="text-primary font-display font-bold text-sm tracking-[0.2em] uppercase mb-4 flex items-center gap-2">
                        <span class="w-8 h-px bg-primary"></span>
                        Лидер Продаж
                      </span>
                      <h3 class="text-5xl md:text-7xl font-display font-bold text-white italic group-hover:text-primary transition-colors tracking-normal drop-shadow-xl">ENDURO</h3>
                      <p class="text-gray-300 mt-4 max-w-md hidden md:block text-lg font-light leading-relaxed border-l-2 border-primary/50 pl-4">
                        Мотоциклы для пересеченной местности, соревнований и активного отдыха. Максимальная производительность.
                      </p>
                   </div>
                   <div class="hidden md:flex w-14 h-14 rounded-full border border-white/20 items-center justify-center text-white group-hover:border-primary group-hover:bg-primary/10 transition-all duration-300 transform group-hover:translate-x-2">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                   </div>
                 </div>
              </div>
            </div>
          </RouterLink>

          <RouterLink to="/catalog?type=ATV" class="lg:col-span-4 relative group cursor-pointer block overflow-hidden rounded-sm border border-white/5 hover:border-primary/50 transition-all duration-500 bg-dark-lighter">
            <div class="aspect-square lg:aspect-auto lg:h-full relative overflow-hidden">
              <img :src="'/images/category_atv.png'" alt="ATV" class="object-cover w-full h-full transform group-hover:scale-110 transition-transform duration-700 filter group-hover:brightness-110" />
              <div class="absolute inset-0 bg-gradient-to-t from-dark via-dark/40 to-transparent opacity-90 group-hover:opacity-80 transition-opacity" />
              
              <div class="absolute top-6 right-6">
                <div class="bg-dark/50 backdrop-blur-md px-4 py-2 border border-white/10 rounded-sm transform skew-x-[-10deg]">
                  <span class="text-white font-bold text-sm uppercase tracking-wider skew-x-[10deg] block">4x4 Power</span>
                </div>
              </div>

              <div class="absolute bottom-0 left-0 p-8 w-full">
                <span class="text-gray-400 font-display font-bold text-xs tracking-[0.2em] uppercase mb-4 flex items-center gap-2 group-hover:text-primary transition-colors">
                  <span class="w-6 h-px bg-current"></span>
                  Утилитарные
                </span>
                <div class="flex justify-between items-end">
                  <h3 class="text-5xl font-display font-bold text-white italic group-hover:text-primary transition-colors tracking-normal drop-shadow-xl">ATV</h3>
                  <div class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center text-white group-hover:border-primary group-hover:bg-primary/10 transition-all duration-300 transform group-hover:translate-x-2">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                  </div>
                </div>
              </div>
            </div>
          </RouterLink>

          <RouterLink to="/catalog?type=Parts" class="lg:col-span-12 relative group cursor-pointer overflow-hidden rounded-sm border border-white/5 hover:border-primary/50 transition-all duration-500 bg-dark-lighter mt-2">
            <div class="p-8 md:p-12 lg:p-16 flex flex-col md:flex-row items-center relative overflow-hidden">
              <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-primary/5 to-transparent skew-x-[-20deg] transform translate-x-20 group-hover:from-primary/10 transition-colors duration-500" />
              
              <div class="absolute -left-32 -bottom-32 w-64 h-64 bg-primary/10 blur-3xl rounded-full" />

              <div class="md:w-7/12 relative z-10 pr-8">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/5 border border-white/10 rounded-sm mb-6 transform -skew-x-12">
                   <span class="text-primary text-xs font-bold uppercase tracking-widest skew-x-12 block">Обслуживание</span>
                </div>
                <h3 class="text-4xl md:text-5xl lg:text-6xl font-display font-bold text-white italic mb-6 tracking-normal group-hover:text-primary transition-colors duration-300">
                  ЗАПЧАСТИ И СЕРВИС
                </h3>
                <p class="text-gray-400 text-lg lg:text-xl mb-10 max-w-xl font-light leading-relaxed">
                  Оригинальные детали и профессиональное обслуживание для вашей техники. Поддерживайте свой байк в идеальном состоянии для новых побед.
                </p>
                <div class="inline-flex items-center text-white font-bold uppercase tracking-widest text-sm group-hover:text-primary transition-colors duration-300">
                  <span>Перейти в раздел</span>
                  <div class="ml-4 w-12 h-12 rounded-full border border-white/20 flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all duration-300 transform group-hover:translate-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                  </div>
                </div>
              </div>

              <div class="md:w-5/12 mt-12 md:mt-0 flex justify-center lg:justify-end relative z-10">
                <div class="relative w-full max-w-md aspect-[4/3]">
                  <img :src="'/images/category_parts.png'" alt="Parts" class="absolute inset-0 w-full h-full object-contain filter drop-shadow-2xl opacity-80 group-hover:opacity-100 group-hover:scale-110 transition-all duration-700 z-10" />
                  <div class="absolute inset-0 bg-primary/20 blur-[80px] rounded-full scale-75 group-hover:scale-100 transition-transform duration-700" />
                </div>
              </div>
            </div>
          </RouterLink>
        </div>
      </div>
    </section>

    <section class="py-24 bg-dark relative border-t border-white/5">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="flex justify-between items-end mb-12">
          <div>
            <h2 class="text-4xl md:text-5xl font-bold font-display italic text-white mb-2">ПОПУЛЯРНЫЕ МОДЕЛИ</h2>
            <div class="h-1 w-20 bg-primary" />
          </div>
        </div>

        <p v-if="loading" class="text-gray-500">Загрузка...</p>
        <p v-else-if="errorText" class="text-red-400">{{ errorText }}</p>

        <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div v-for="moto in popular" :key="moto.id" class="group relative bg-dark-lighter border border-white/5 hover:border-primary/50 transition-all duration-300">
            <div class="aspect-[4/3] overflow-hidden relative">
              <img :src="moto.image_url" :alt="moto.model" class="object-cover w-full h-full transform group-hover:scale-110 transition-transform duration-700" />
              <div class="absolute top-4 left-4 bg-primary text-white text-xs font-bold uppercase px-3 py-1 transform -skew-x-12">
                <span class="inline-block skew-x-12">Хит</span>
              </div>
              <div class="absolute top-4 right-4 px-3 py-1 text-[11px] font-bold uppercase tracking-wider border backdrop-blur-sm" :class="moto.is_available ? 'bg-green-500/15 text-green-300 border-green-500/30' : 'bg-red-500/15 text-red-300 border-red-500/30'">
                {{ moto.is_available ? 'В наличии' : 'Нет в наличии' }}
              </div>
            </div>
            <div class="p-6">
              <h3 class="text-2xl font-display font-bold text-white italic mb-2">{{ moto.brand }} {{ moto.model }}</h3>
              <div class="flex flex-wrap gap-3 text-xs text-gray-500 font-mono border-y border-white/5 py-3 mb-4">
                <span>{{ moto.year }} г.</span>
                <span>{{ moto.engine_capacity }} см³</span>
                <span>{{ moto.power }} л.с.</span>
              </div>
              <p class="text-primary font-bold text-xl mb-4">{{ moto.price.toLocaleString('ru-RU') }} ₽</p>
              <RouterLink :to="`/motorcycle/${moto.id}`" class="btn btn-outline w-full mt-4">
                <span>Подробнее</span>
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="py-24 bg-dark relative overflow-hidden">
      <div class="absolute inset-0 bg-dark-lighter/50" />
      <div class="absolute -left-24 top-1/2 w-96 h-96 bg-primary/10 rounded-full blur-[100px] pointer-events-none" />

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="bg-dark-lighter border border-white/5 rounded-sm overflow-hidden relative shadow-2xl">
          <div class="grid grid-cols-1 lg:grid-cols-2">
            <div class="relative h-96 lg:h-auto min-h-[500px] group">
              <img :src="'/images/service_tuning.png'" alt="Service & Tuning" class="absolute inset-0 w-full h-full object-cover grayscale opacity-80 group-hover:grayscale-0 transition-all duration-700" />
              <div class="absolute inset-0 bg-gradient-to-r from-transparent to-dark-lighter" />
              <div class="absolute inset-0 bg-gradient-to-t from-dark-lighter via-transparent to-transparent" />

              <div class="absolute bottom-8 left-8 bg-primary/90 backdrop-blur-md p-4 max-w-xs transform -skew-x-6 border-l-4 border-white shadow-lg">
                <p class="text-white font-bold text-lg leading-none skew-x-6">ОФИЦИАЛЬНЫЙ ДИЛЕР</p>
                <p class="text-white/80 text-xs mt-1 skew-x-6 uppercase tracking-wider">Сертифицированные механики</p>
              </div>
            </div>

            <div class="p-8 md:p-12 lg:p-16 flex flex-col justify-center relative overflow-hidden">
              <span class="absolute top-0 right-0 text-9xl font-display font-bold text-white/5 pointer-events-none select-none leading-none -mr-16 -mt-8">SERVICE</span>

              <h2 class="text-5xl font-display font-bold text-white italic mb-6 relative z-10">
                СЕРВИС <span class="text-primary">&</span> ТЮНИНГ
              </h2>
              <p class="text-gray-400 text-lg mb-8 relative z-10 font-light leading-relaxed">
                Мы знаем, как заставить ваш байк летать. От планового ТО до подготовки к соревнованиям мирового уровня. Используем только оригинальные запчасти и профессиональный инструмент.
              </p>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-10 relative z-10">
                <div class="flex items-center space-x-4 group">
                  <div class="w-12 h-12 flex-shrink-0 border border-white/10 flex items-center justify-center group-hover:bg-primary group-hover:border-primary transition-all duration-300 transform group-hover:-skew-x-12">
                    <svg class="w-5 h-5 text-white transform group-hover:skew-x-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                  </div>
                  <span class="text-white font-bold uppercase tracking-wider text-sm">Диагностика</span>
                </div>
                <div class="flex items-center space-x-4 group">
                  <div class="w-12 h-12 flex-shrink-0 border border-white/10 flex items-center justify-center group-hover:bg-primary group-hover:border-primary transition-all duration-300 transform group-hover:-skew-x-12">
                    <svg class="w-5 h-5 text-white transform group-hover:skew-x-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                  </div>
                  <span class="text-white font-bold uppercase tracking-wider text-sm">Чип-тюнинг</span>
                </div>
                <div class="flex items-center space-x-4 group">
                  <div class="w-12 h-12 flex-shrink-0 border border-white/10 flex items-center justify-center group-hover:bg-primary group-hover:border-primary transition-all duration-300 transform group-hover:-skew-x-12">
                    <svg class="w-5 h-5 text-white transform group-hover:skew-x-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                  </div>
                  <span class="text-white font-bold uppercase tracking-wider text-sm">Ремонт Подвески</span>
                </div>
                <div class="flex items-center space-x-4 group">
                  <div class="w-12 h-12 flex-shrink-0 border border-white/10 flex items-center justify-center group-hover:bg-primary group-hover:border-primary transition-all duration-300 transform group-hover:-skew-x-12">
                    <svg class="w-5 h-5 text-white transform group-hover:skew-x-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                  </div>
                  <span class="text-white font-bold uppercase tracking-wider text-sm">Сезонное ТО</span>
                </div>
              </div>

            <RouterLink to="/service" class="btn btn-primary w-full md:w-auto">
                <span>Записаться Онлайн</span>
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="py-24 bg-dark border-t border-white/5">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-5xl font-display font-bold text-white italic mb-4">БЛОГ И НОВОСТИ</h2>
          <div class="h-1 w-24 bg-white/20 mx-auto" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div v-for="item in news" :key="item.title" class="group cursor-pointer">
            <div class="aspect-[16/9] bg-dark-lighter overflow-hidden mb-6 relative">
              <img :src="item.image" alt="News" class="object-cover w-full h-full transform group-hover:scale-105 transition-transform duration-700" />
              <div class="absolute top-0 left-0 bg-primary/90 text-white text-xs font-bold px-3 py-2">{{ item.date }}</div>
            </div>
            <h3 class="text-xl font-bold text-white mb-2 group-hover:text-primary transition-colors leading-tight">{{ item.title }}</h3>
            <p class="text-gray-500 text-sm line-clamp-2 mb-4">{{ item.excerpt }}</p>
            <span class="text-white text-xs font-bold uppercase tracking-widest border-b border-primary/50 pb-1">Читать далее</span>
          </div>
        </div>
      </div>
    </section>

    <div class="py-12 bg-dark-lighter border-y border-white/5">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap justify-center md:justify-between items-center gap-8 opacity-50 grayscale hover:grayscale-0 transition-all duration-500">
          <span class="text-2xl font-display font-bold text-white tracking-widest">AVANTIS</span>
          <span class="text-2xl font-display font-bold text-white tracking-widest">ZONGSHEN</span>
          <span class="text-2xl font-display font-bold text-white tracking-widest">KKE</span>
          <span class="text-2xl font-display font-bold text-white tracking-widest">MOTUL</span>
          <span class="text-2xl font-display font-bold text-white tracking-widest">NGK</span>
          <span class="text-2xl font-display font-bold text-white tracking-widest">BOSCH</span>
        </div>
      </div>
    </div>

    <section id="about" class="py-24 bg-dark-lighter relative overflow-hidden border-t border-white/5">
      <div class="absolute inset-0 opacity-5">
        <svg class="h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none">
          <path d="M0 100 C 20 0 50 0 100 100 Z" fill="none" stroke="white" stroke-width="0.5" />
        </svg>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-20">
          <h2 class="text-5xl md:text-7xl font-bold font-display italic text-white tracking-normal mb-4 text-stroke">ПОЧЕМУ МЫ</h2>
          <p class="text-gray-400 max-w-2xl mx-auto text-lg">Мы не просто продаем мотоциклы. Мы дарим эмоции и гарантируем качество на каждом этапе.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
          <div class="group">
            <div class="relative mb-8">
              <div class="absolute -inset-4 bg-primary/20 rounded-full blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
              <div class="w-16 h-16 bg-primary flex items-center justify-center rounded-sm transform -skew-x-12 group-hover:scale-110 transition-transform duration-300 relative z-10">
                <svg class="w-8 h-8 text-white transform skew-x-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0" /></svg>
              </div>
            </div>
            <h3 class="text-2xl font-display font-bold text-white uppercase italic mb-4 group-hover:text-primary transition-colors">Гарантия Качества</h3>
            <p class="text-gray-500 leading-relaxed">Вся техника проходит предпродажную подготовку. Мы уверены в каждом, проданном нами, мотоцикле.</p>
          </div>

          <div class="group">
            <div class="relative mb-8">
              <div class="absolute -inset-4 bg-primary/20 rounded-full blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
              <div class="w-16 h-16 bg-white/10 flex items-center justify-center rounded-sm transform -skew-x-12 group-hover:bg-primary group-hover:scale-110 transition-all duration-300 relative z-10">
                <svg class="w-8 h-8 text-white transform skew-x-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7" /></svg>
              </div>
            </div>
            <h3 class="text-2xl font-display font-bold text-white uppercase italic mb-4 group-hover:text-primary transition-colors">Мощность и Драйв</h3>
            <p class="text-gray-500 leading-relaxed">Топовые двигатели Zongshen, подвески KKE/ARS – мы подбираем конфигурации для максимальной отдачи.</p>
          </div>

          <div class="group">
            <div class="relative mb-8">
              <div class="absolute -inset-4 bg-primary/20 rounded-full blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
              <div class="w-16 h-16 bg-white/10 flex items-center justify-center rounded-sm transform -skew-x-12 group-hover:bg-primary group-hover:scale-110 transition-all duration-300 relative z-10">
                <svg class="w-8 h-8 text-white transform skew-x-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0" /></svg>
              </div>
            </div>
            <h3 class="text-2xl font-display font-bold text-white uppercase italic mb-4 group-hover:text-primary transition-colors">Лучшие Цены</h3>
            <p class="text-gray-500 leading-relaxed">Прямые поставки от производителя позволяют нам держать цены на доступном уровне без лишних наценок.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="py-20 bg-dark relative overflow-hidden border-t border-white/5">
      <div class="absolute inset-0">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary/5 rounded-full blur-[120px] pointer-events-none" />
      </div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 md:gap-12">
          <div class="text-center">
            <div class="text-5xl md:text-6xl font-display font-bold text-white mb-2">10+</div>
            <div class="h-1 w-12 bg-primary mx-auto mb-3" />
            <p class="text-gray-500 text-sm uppercase tracking-wider font-bold">Лет опыта</p>
          </div>
          <div class="text-center">
            <div class="text-5xl md:text-6xl font-display font-bold text-white mb-2">2000+</div>
            <div class="h-1 w-12 bg-primary mx-auto mb-3" />
            <p class="text-gray-500 text-sm uppercase tracking-wider font-bold">Довольных клиентов</p>
          </div>
          <div class="text-center">
            <div class="text-5xl md:text-6xl font-display font-bold text-white mb-2">500+</div>
            <div class="h-1 w-12 bg-primary mx-auto mb-3" />
            <p class="text-gray-500 text-sm uppercase tracking-wider font-bold">Моделей техники</p>
          </div>
          <div class="text-center">
            <div class="text-5xl md:text-6xl font-display font-bold text-primary mb-2">2</div>
            <div class="h-1 w-12 bg-primary mx-auto mb-3" />
            <p class="text-gray-500 text-sm uppercase tracking-wider font-bold">Года гарантии</p>
          </div>
        </div>
      </div>
    </section>

    <section class="py-24 bg-dark-lighter border-y border-white/5">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-12">
          <div>
            <h2 class="text-4xl md:text-6xl font-display font-bold text-white italic tracking-normal">ПРОЦЕСС РАБОТЫ</h2>
            <div class="h-1 w-20 bg-primary mt-4" />
          </div>
          <p class="text-gray-400 max-w-xl text-lg">
            Система связывает каталог, заявки, заказы, сервис и личный кабинет клиента в один понятный сценарий.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-px bg-white/10 border border-white/10">
          <article v-for="step in workflowSteps" :key="step[0]" class="bg-dark-lighter p-6 md:p-8 hover:bg-dark transition-colors min-h-64">
            <p class="text-primary font-display font-bold text-5xl mb-8">{{ step[0] }}</p>
            <h3 class="text-xl font-display font-bold text-white uppercase mb-4">{{ step[1] }}</h3>
            <p class="text-gray-500 leading-relaxed">{{ step[2] }}</p>
          </article>
        </div>
      </div>
    </section>

    <section class="py-24 bg-dark-lighter relative overflow-hidden">
      <div class="absolute -right-40 top-0 w-96 h-96 bg-primary/5 rounded-full blur-[120px] pointer-events-none" />
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-5xl font-display font-bold text-white italic mb-4">ОТЗЫВЫ КЛИЕНТОВ</h2>
          <div class="h-1 w-24 bg-primary mx-auto" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="bg-dark border border-white/5 p-8 relative group hover:border-primary/30 transition-colors">
            <div class="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-primary to-transparent opacity-0 group-hover:opacity-100 transition-opacity" />
            <p class="text-gray-400 mb-6 leading-relaxed italic">"Купил Avantis Enduro 250 для тренировок. Качество сборки на высоте, двигатель тянет отлично. Ребята из сервиса помогли с настройкой подвески."</p>
            <div class="flex items-center gap-3 pt-4 border-t border-white/5">
              <div class="w-10 h-10 bg-primary/20 flex items-center justify-center text-primary font-display font-bold">А</div>
              <div>
                <p class="text-white font-bold text-sm">Алексей К.</p>
                <p class="text-gray-600 text-xs">Avantis Enduro 250</p>
              </div>
            </div>
          </div>

          <div class="bg-dark border border-white/5 p-8 relative group hover:border-primary/30 transition-colors">
            <div class="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-primary to-transparent opacity-0 group-hover:opacity-100 transition-opacity" />
            <p class="text-gray-400 mb-6 leading-relaxed italic">"Взяли квадрик для дачи — семья в восторге! ATV мощный и управляемый. Доставили за 3 дня. Отдельное спасибо за подробную консультацию при выборе."</p>
            <div class="flex items-center gap-3 pt-4 border-t border-white/5">
              <div class="w-10 h-10 bg-blue-500/20 flex items-center justify-center text-blue-400 font-display font-bold">Д</div>
              <div>
                <p class="text-white font-bold text-sm">Дмитрий В.</p>
                <p class="text-gray-600 text-xs">Avantis Hunter 200</p>
              </div>
            </div>
          </div>

          <div class="bg-dark border border-white/5 p-8 relative group hover:border-primary/30 transition-colors">
            <div class="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-primary to-transparent opacity-0 group-hover:opacity-100 transition-opacity" />
            <p class="text-gray-400 mb-6 leading-relaxed italic">"Обслуживаюсь тут уже второй сезон. Механики грамотные, запчасти всегда в наличии. Сделали полную переборку подвески за день — респект!"</p>
            <div class="flex items-center gap-3 pt-4 border-t border-white/5">
              <div class="w-10 h-10 bg-green-500/20 flex items-center justify-center text-green-400 font-display font-bold">М</div>
              <div>
                <p class="text-white font-bold text-sm">Максим Р.</p>
                <p class="text-gray-600 text-xs">Постоянный клиент сервиса</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="lead-form" class="py-20 relative overflow-hidden bg-primary">
      <div class="absolute inset-0 bg-dark/20 mix-blend-multiply" />
      <div class="absolute -right-20 -top-20 w-96 h-96 bg-white/10 rounded-full blur-3xl" />
      <div class="absolute -left-20 -bottom-20 w-64 h-64 bg-white/5 rounded-full blur-3xl" />

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
          <div>
            <h2 class="text-4xl md:text-6xl font-display font-bold text-white italic tracking-normal mb-6">ГОТОВ К ПРИКЛЮЧЕНИЯМ?</h2>
            <p class="text-white/80 text-lg mb-8 max-w-md">
              Оставь заявку и наш менеджер поможет подобрать идеальную технику для твоих задач. Бесплатная консультация и тест-драйв.
            </p>
            <div class="flex flex-wrap gap-6 text-white/90">
              <div class="flex items-center gap-2"><span class="text-sm font-bold">Бесплатная доставка</span></div>
              <div class="flex items-center gap-2"><span class="text-sm font-bold">Гарантия 2 года</span></div>
              <div class="flex items-center gap-2"><span class="text-sm font-bold">Тест-драйв</span></div>
            </div>
          </div>

          <div class="bg-dark/30 backdrop-blur-md border border-white/10 p-8">
            <h3 class="text-xl font-display font-bold text-white uppercase mb-6">Обратная связь</h3>
            <form class="space-y-4" @submit.prevent="submitLeadForm">
              <input v-model="leadForm.name" type="text" required placeholder="Ваше имя" class="w-full bg-white/10 border border-white/20 text-white px-4 py-3 placeholder-white/50 focus:outline-none focus:border-white transition-colors text-sm" />
              <input v-model="leadForm.phone" type="text" required placeholder="+7 (___) ___-__-__" class="w-full bg-white/10 border border-white/20 text-white px-4 py-3 placeholder-white/50 focus:outline-none focus:border-white transition-colors text-sm" />
              <input v-model="leadForm.email" type="email" placeholder="your@email.com" class="w-full bg-white/10 border border-white/20 text-white px-4 py-3 placeholder-white/50 focus:outline-none focus:border-white transition-colors text-sm" />
              <textarea v-model="leadForm.comment" rows="3" placeholder="Какая техника вас интересует?" class="w-full bg-white/10 border border-white/20 text-white px-4 py-3 placeholder-white/50 focus:outline-none focus:border-white transition-colors text-sm resize-none" />

              <p v-if="submitError" class="text-red-200 text-sm">{{ submitError }}</p>
              <p v-if="submitSuccess" class="text-green-200 text-sm">{{ submitSuccess }}</p>

              <button type="submit" class="btn w-full text-lg bg-white text-dark hover:bg-dark hover:text-white border-white shadow-lg" :disabled="submitting">
                <span>{{ submitting ? 'Отправка...' : 'Отправить заявку' }}</span>
              </button>
            </form>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
