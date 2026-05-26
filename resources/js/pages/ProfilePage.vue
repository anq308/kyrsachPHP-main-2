<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import api from '../api';
import StatusBadge from '../components/ui/StatusBadge.vue';
import type { ClientNotification, Order, SalesRequest, ServiceRequest, User } from '../types';

const router = useRouter();

const loading = ref(true);
const errorText = ref('');
const user = ref<User | null>(null);
const orders = ref<Order[]>([]);
const salesRequests = ref<SalesRequest[]>([]);
const serviceRequests = ref<ServiceRequest[]>([]);
const notifications = ref<ClientNotification[]>([]);
const unreadNotificationsCount = ref(0);
const favoritesCount = ref(0);
const expandedOrders = ref<number[]>([]);

function toggleOrder(id: number) {
  if (expandedOrders.value.includes(id)) {
    expandedOrders.value = expandedOrders.value.filter((orderId) => orderId !== id);
    return;
  }
  expandedOrders.value = [...expandedOrders.value, id];
}

function isExpanded(id: number): boolean {
  return expandedOrders.value.includes(id);
}

function paymentMethodLabel(method?: Order['payment_method']): string {
  switch (method) {
    case 'card_pickup':
      return 'Картой при получении';
    case 'online_mock':
      return 'Онлайн-оплата';
    case 'credit_request':
      return 'Рассрочка / кредит';
    case 'cash_pickup':
      return 'Наличными при получении';
    default:
      return 'Не указан';
  }
}

function paymentStatusLabel(status?: Order['payment_status']): string {
  switch (status) {
    case 'paid':
      return 'Оплачено';
    case 'failed':
      return 'Ошибка оплаты';
    case 'refunded':
      return 'Возврат';
    case 'pending':
      return 'Ожидает оплаты';
    default:
      return 'Не указан';
  }
}

function salesTypeLabel(type: SalesRequest['type']): string {
  switch (type) {
    case 'purchase':
      return 'Покупка';
    case 'availability':
      return 'Уточнить наличие';
    case 'preorder':
      return 'Предзаказ';
    case 'test_drive':
      return 'Тест-драйв';
    case 'consultation':
    default:
      return 'Консультация';
  }
}

async function loadProfile() {
  loading.value = true;
  errorText.value = '';

  try {
    const { data } = await api.get('/profile');
    user.value = data.user;
    orders.value = data.orders ?? [];
    salesRequests.value = data.sales_requests ?? [];
    serviceRequests.value = data.service_requests ?? [];
    notifications.value = data.notifications ?? [];
    unreadNotificationsCount.value = Number(data.unread_notifications_count ?? 0);
    favoritesCount.value = Number(data.favorites_count ?? 0);
  } catch {
    await router.push('/login');
  } finally {
    loading.value = false;
  }
}

async function markNotificationsRead() {
  if (!unreadNotificationsCount.value) {
    return;
  }

  await api.patch('/profile/notifications/read');
  notifications.value = notifications.value.map((notification) => ({ ...notification, is_read: true }));
  unreadNotificationsCount.value = 0;
}

onMounted(loadProfile);
</script>

<template>
  <div>
    <div class="relative bg-dark overflow-hidden py-16 md:py-20">
      <div class="absolute inset-0">
        <div class="absolute inset-0 bg-gradient-to-b from-primary/5 via-dark to-dark" />
        <div class="absolute -top-40 right-0 w-96 h-96 bg-primary/10 rounded-full blur-[120px] pointer-events-none" />
      </div>

      <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-6" v-if="user">
          <div class="w-20 h-20 bg-primary/20 border-2 border-primary/50 flex items-center justify-center text-primary text-3xl font-bold font-display">
            {{ user.name.trim().charAt(0).toUpperCase() }}
          </div>
          <div>
            <h1 class="text-4xl md:text-5xl font-bold font-display text-white uppercase tracking-wide mb-1">{{ user.name }}</h1>
            <p class="text-gray-500">{{ user.email }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-dark min-h-screen pb-24 relative">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <p v-if="loading" class="text-gray-500 py-8">Загрузка...</p>
        <p v-if="errorText" class="mb-6 p-4 bg-red-500/10 border border-red-500/30 text-red-400 text-sm">{{ errorText }}</p>

        <div v-if="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 -mt-8 mb-12">
          <div class="bg-dark-lighter border border-white/5 p-6 relative overflow-hidden group hover:border-primary/30 transition-colors">
            <div class="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-primary to-transparent" />
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs text-gray-600 uppercase tracking-wider font-bold mb-1">Заказов</p>
                <p class="text-3xl font-display font-bold text-white">{{ orders.length }}</p>
                <p v-if="unreadNotificationsCount" class="text-xs text-primary mt-1">{{ unreadNotificationsCount }} новых уведомлений</p>
              </div>
              <div class="w-12 h-12 bg-primary/10 flex items-center justify-center text-primary">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2" /></svg>
              </div>
            </div>
          </div>

          <div class="bg-dark-lighter border border-white/5 p-6 relative overflow-hidden group hover:border-primary/30 transition-colors">
            <div class="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-primary to-transparent" />
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs text-gray-600 uppercase tracking-wider font-bold mb-1">В избранном</p>
                <p class="text-3xl font-display font-bold text-white">{{ favoritesCount }}</p>
              </div>
              <div class="w-12 h-12 bg-red-500/10 flex items-center justify-center text-red-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
              </div>
            </div>
          </div>

          <div class="bg-dark-lighter border border-white/5 p-6 relative overflow-hidden group hover:border-primary/30 transition-colors">
            <div class="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-orange-500 to-transparent" />
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs text-gray-600 uppercase tracking-wider font-bold mb-1">Заявок</p>
                <p class="text-3xl font-display font-bold text-white">{{ salesRequests.length }}</p>
              </div>
              <div class="w-12 h-12 bg-orange-500/10 flex items-center justify-center text-orange-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-8 4h5m-7 8h12a2 2 0 002-2V5a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
              </div>
            </div>
          </div>

          <div class="bg-dark-lighter border border-white/5 p-6 relative overflow-hidden group hover:border-primary/30 transition-colors">
            <div class="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-green-500 to-transparent" />
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs text-gray-600 uppercase tracking-wider font-bold mb-1">Сервис</p>
                <p class="text-3xl font-display font-bold text-white">{{ serviceRequests.length }}</p>
              </div>
              <div class="w-12 h-12 bg-green-500/10 flex items-center justify-center text-green-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /></svg>
              </div>
            </div>
          </div>

          <div class="bg-dark-lighter border border-white/5 p-6 relative overflow-hidden group hover:border-primary/30 transition-colors">
            <div class="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-primary to-transparent" />
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs text-gray-600 uppercase tracking-wider font-bold mb-1">Дата регистрации</p>
                <p class="text-xl font-display font-bold text-white">{{ user?.created_at ? new Date(user.created_at).toLocaleDateString('ru-RU') : '—' }}</p>
              </div>
              <div class="w-12 h-12 bg-blue-500/10 flex items-center justify-center text-blue-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2" /></svg>
              </div>
            </div>
          </div>
        </div>

        <div v-if="!loading && user" class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
          <section class="bg-dark-lighter border border-white/5 p-6 lg:col-span-1">
            <p class="text-primary text-xs font-bold uppercase tracking-[0.25em] mb-3">Личные данные</p>
            <h2 class="text-2xl font-display font-bold text-white uppercase mb-5">{{ user.name }}</h2>
            <div class="space-y-3 text-sm">
              <div class="flex items-center justify-between gap-4 border-b border-white/5 pb-3">
                <span class="text-gray-500">Email</span>
                <span class="text-gray-200 text-right">{{ user.email }}</span>
              </div>
              <div class="flex items-center justify-between gap-4 border-b border-white/5 pb-3">
                <span class="text-gray-500">Роль</span>
                <StatusBadge :status="user.is_admin ? 'admin' : 'user'" kind="role" />
              </div>
              <div class="flex items-center justify-between gap-4">
                <span class="text-gray-500">Регистрация</span>
                <span class="text-gray-200">{{ user.created_at ? new Date(user.created_at).toLocaleDateString('ru-RU') : '—' }}</span>
              </div>
            </div>
          </section>

          <section class="bg-dark-lighter border border-white/5 p-6 lg:col-span-2">
            <p class="text-primary text-xs font-bold uppercase tracking-[0.25em] mb-3">Разделы кабинета</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
              <a href="#profile-orders" class="filter-chip block text-center">Мои заказы</a>
              <a href="#profile-sales" class="filter-chip block text-center">Заявки на покупку</a>
              <a href="#profile-service" class="filter-chip block text-center">Записи на сервис</a>
              <RouterLink to="/favorites" class="filter-chip block text-center">Избранное</RouterLink>
            </div>
          </section>
        </div>

        <div class="flex flex-wrap gap-3 mb-12" v-if="!loading">
          <RouterLink to="/favorites" class="inline-flex items-center gap-2 px-5 py-2.5 bg-dark-lighter border border-white/5 text-gray-400 hover:text-white hover:border-primary/50 transition-all text-sm font-bold uppercase tracking-wider">
            <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
            Избранное
          </RouterLink>
          <RouterLink to="/compare" class="inline-flex items-center gap-2 px-5 py-2.5 bg-dark-lighter border border-white/5 text-gray-400 hover:text-white hover:border-primary/50 transition-all text-sm font-bold uppercase tracking-wider">
            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
            Сравнение
          </RouterLink>
          <RouterLink to="/catalog" class="inline-flex items-center gap-2 px-5 py-2.5 bg-dark-lighter border border-white/5 text-gray-400 hover:text-white hover:border-primary/50 transition-all text-sm font-bold uppercase tracking-wider">
            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
            Каталог
          </RouterLink>
          <RouterLink to="/cart" class="inline-flex items-center gap-2 px-5 py-2.5 bg-dark-lighter border border-white/5 text-gray-400 hover:text-white hover:border-primary/50 transition-all text-sm font-bold uppercase tracking-wider">
            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" /></svg>
            Корзина
          </RouterLink>
        </div>

        <section v-if="!loading" class="bg-dark-lighter border border-white/5 p-6 mb-12">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-5">
            <div>
              <p class="text-primary text-xs font-bold uppercase tracking-[0.25em] mb-2">Уведомления</p>
              <h2 class="text-2xl font-display font-bold text-white uppercase italic">Статусы от менеджера</h2>
            </div>
            <button
              type="button"
              class="filter-chip"
              :disabled="!unreadNotificationsCount"
              @click="markNotificationsRead"
            >
              Отметить прочитанными
            </button>
          </div>

          <div v-if="!notifications.length" class="empty-panel">Пока нет уведомлений по заказам.</div>
          <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-3">
            <article
              v-for="notification in notifications"
              :key="notification.id"
              class="bg-dark border p-4 transition-colors"
              :class="notification.is_read ? 'border-white/5' : 'border-primary/40'"
            >
              <div class="flex items-start justify-between gap-3 mb-2">
                <p class="text-white font-display font-bold uppercase">{{ notification.title }}</p>
                <span v-if="!notification.is_read" class="text-[10px] text-primary font-bold uppercase tracking-wider">Новое</span>
              </div>
              <p class="text-gray-400 text-sm leading-relaxed">{{ notification.message }}</p>
              <p class="text-gray-600 text-xs mt-3">{{ new Date(notification.created_at).toLocaleString('ru-RU') }}</p>
            </article>
          </div>
        </section>

        <div v-if="!loading" class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-14">
          <section id="profile-sales">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-2xl md:text-3xl font-bold font-display text-white uppercase">Заявки на покупку</h2>
              <RouterLink to="/catalog" class="text-xs font-bold uppercase tracking-wider text-primary hover:text-white transition-colors">Новая заявка</RouterLink>
            </div>

            <div v-if="!salesRequests.length" class="bg-dark-lighter border border-white/5 p-8">
              <p class="text-white font-display font-bold uppercase mb-2">У вас пока нет заявок</p>
              <p class="text-gray-500 text-sm">Откройте карточку мотоцикла и нажмите “Оставить заявку”.</p>
            </div>

            <div v-else class="space-y-4">
              <article v-for="request in salesRequests" :key="request.id" class="bg-dark-lighter border border-white/5 p-5 hover:border-primary/30 transition-colors">
                <div class="flex items-start justify-between gap-4 mb-4">
                  <div>
                    <p class="text-white font-display font-bold uppercase">#{{ request.id }} · {{ salesTypeLabel(request.type) }}</p>
                    <p class="text-sm text-gray-600">{{ new Date(request.created_at).toLocaleString('ru-RU') }}</p>
                  </div>
                  <StatusBadge :status="request.status" kind="sales" />
                </div>
                <p class="text-gray-300 font-medium">{{ request.motorcycle ? `${request.motorcycle.brand} ${request.motorcycle.model}` : 'Консультация по подбору техники' }}</p>
                <p v-if="request.comment" class="text-sm text-gray-500 mt-2 line-clamp-2">{{ request.comment }}</p>
              </article>
            </div>
          </section>

          <section id="profile-service">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-2xl md:text-3xl font-bold font-display text-white uppercase">Сервисные заявки</h2>
              <RouterLink to="/service" class="text-xs font-bold uppercase tracking-wider text-primary hover:text-white transition-colors">Записаться</RouterLink>
            </div>

            <div v-if="!serviceRequests.length" class="bg-dark-lighter border border-white/5 p-8">
              <p class="text-white font-display font-bold uppercase mb-2">У вас пока нет сервисных записей</p>
              <p class="text-gray-500 text-sm">Запишитесь на сервис, чтобы отслеживать статус обслуживания.</p>
            </div>

            <div v-else class="space-y-4">
              <article v-for="request in serviceRequests" :key="request.id" class="bg-dark-lighter border border-white/5 p-5 hover:border-primary/30 transition-colors">
                <div class="flex items-start justify-between gap-4 mb-4">
                  <div>
                    <p class="text-white font-display font-bold uppercase">#{{ request.id }} · {{ request.service_type }}</p>
                    <p class="text-sm text-gray-600">{{ new Date(request.created_at).toLocaleString('ru-RU') }}</p>
                  </div>
                  <StatusBadge :status="request.status" kind="service" />
                </div>
                <p class="text-gray-300 font-medium">{{ request.motorcycle_model }}</p>
                <p class="text-sm text-gray-500 mt-2">Желаемая дата: {{ request.preferred_date ? new Date(request.preferred_date).toLocaleDateString('ru-RU') : 'не указана' }}</p>
              </article>
            </div>
          </section>
        </div>

        <div id="profile-orders" class="flex items-center justify-between mb-8" v-if="!loading">
          <h2 class="text-3xl font-bold font-display text-white uppercase">История заказов</h2>
          <div class="h-px flex-1 bg-white/5 ml-6" />
        </div>

        <div v-if="!loading && !orders.length" class="bg-dark-lighter border border-white/5 p-16 text-center">
          <div class="w-20 h-20 mx-auto mb-6 bg-white/5 flex items-center justify-center">
            <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
          </div>
          <h3 class="text-xl font-display font-bold text-white uppercase mb-2">У вас пока нет заказов</h3>
          <p class="text-gray-500 mb-8 max-w-md mx-auto">Перейдите в каталог и выберите технику, которая подойдёт именно вам</p>
          <RouterLink to="/catalog" class="btn btn-primary"><span>Перейти в каталог</span></RouterLink>
        </div>

        <div v-else-if="!loading" class="space-y-4">
          <div v-for="order in orders" :key="order.id" class="bg-dark-lighter border border-white/5 overflow-hidden hover:border-white/10 transition-colors">
            <div class="p-5 md:p-6 flex flex-col md:flex-row md:items-center md:justify-between cursor-pointer hover:bg-white/[0.02] transition-colors" @click="toggleOrder(order.id)">
              <div class="flex items-center gap-4 mb-3 md:mb-0">
                <div
                  class="w-10 h-10 flex items-center justify-center text-sm font-display font-bold"
                  :class="{
                    'bg-blue-500/10 text-blue-400 border border-blue-500/20': order.status === 'new',
                    'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20': order.status === 'processing',
                    'bg-orange-500/10 text-orange-400 border border-orange-500/20': order.status === 'approved',
                    'bg-primary/10 text-primary border border-primary/30': order.status === 'ready_for_pickup',
                    'bg-green-500/10 text-green-400 border border-green-500/20': order.status === 'completed',
                    'bg-red-500/10 text-red-400 border border-red-500/20': order.status === 'cancelled',
                  }"
                >
                  #{{ order.id }}
                </div>
                <div>
                  <h3 class="text-base font-bold font-display text-white uppercase">{{ order.items.length }} товаров</h3>
                  <p class="text-sm text-gray-600">{{ new Date(order.created_at).toLocaleString('ru-RU') }}</p>
                </div>
              </div>

              <div class="flex items-center gap-4 md:gap-6">
                <StatusBadge :status="order.status" kind="order" />
                <span class="text-xl font-bold text-primary font-display">{{ order.total.toLocaleString('ru-RU') }} ₽</span>
                <svg class="w-5 h-5 text-gray-600 transform transition-transform" :class="isExpanded(order.id) ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
              </div>
            </div>

            <div v-if="isExpanded(order.id)" class="border-t border-white/5">
              <div class="p-5 md:p-6 space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5">
                  <div class="bg-dark border border-white/5 p-4">
                    <p class="text-xs text-gray-600 font-bold uppercase tracking-wider mb-1">Оплата</p>
                    <p class="text-white text-sm font-bold">{{ paymentMethodLabel(order.payment_method) }}</p>
                    <p class="text-gray-500 text-xs mt-1">{{ paymentStatusLabel(order.payment_status) }}</p>
                  </div>
                  <div class="bg-dark border border-white/5 p-4">
                    <p class="text-xs text-gray-600 font-bold uppercase tracking-wider mb-1">Пункт выдачи</p>
                    <p class="text-white text-sm font-bold">{{ order.pickup_point?.name || 'Уточнит менеджер' }}</p>
                    <p class="text-gray-500 text-xs mt-1">{{ order.pickup_point?.address || 'Адрес будет согласован' }}</p>
                  </div>
                  <div class="bg-dark border border-white/5 p-4">
                    <p class="text-xs text-gray-600 font-bold uppercase tracking-wider mb-1">Готовность</p>
                    <p class="text-white text-sm font-bold">{{ order.pickup_ready_at ? new Date(order.pickup_ready_at).toLocaleString('ru-RU') : 'После подтверждения' }}</p>
                    <p class="text-gray-500 text-xs mt-1">Бронь: {{ order.reservations?.[0]?.status === 'active' ? 'активна' : 'по статусу заказа' }}</p>
                  </div>
                </div>
                <div v-for="item in order.items" :key="item.id" class="flex justify-between items-center py-2 border-b border-white/5 last:border-0">
                  <div class="flex items-center gap-3">
                    <span class="text-white font-medium">{{ item.name }}</span>
                    <span class="text-xs px-2 py-0.5 bg-white/5 text-gray-500">x{{ item.quantity }}</span>
                  </div>
                  <span class="text-primary font-bold font-display">{{ (item.price * item.quantity).toLocaleString('ru-RU') }} ₽</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
