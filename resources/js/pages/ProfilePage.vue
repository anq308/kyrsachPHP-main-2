<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import api from '../api';
import StatusBadge from '../components/ui/StatusBadge.vue';
import type { ClientNotification, Order, Payment, SalesRequest, ServiceRequest, User } from '../types';

type ProfileTab = 'overview' | 'orders' | 'payments' | 'sales' | 'service' | 'notifications';

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
const activeTab = ref<ProfileTab>('overview');
const expandedOrders = ref<number[]>([]);

const latestOrder = computed(() => orders.value[0] ?? null);
const payments = computed<Payment[]>(() => orders.value.flatMap((order) => order.payments ?? []));
const pendingPayments = computed(() => payments.value.filter((payment) => payment.status === 'pending'));
const activeOrders = computed(() => orders.value.filter((order) => !['completed', 'cancelled'].includes(order.status)));
const activeSalesRequests = computed(() => salesRequests.value.filter((request) => !['completed', 'cancelled'].includes(request.status)));
const activeServiceRequests = computed(() => serviceRequests.value.filter((request) => !['done', 'cancelled'].includes(request.status)));
const isStaffUser = computed(() => Boolean(user.value?.can_manage));

const tabs = computed<Array<{ id: ProfileTab; label: string; count: number }>>(() => [
  { id: 'overview', label: 'Обзор', count: unreadNotificationsCount.value },
  { id: 'orders', label: 'Заказы', count: orders.value.length },
  { id: 'payments', label: 'Оплаты', count: pendingPayments.value.length },
  { id: 'sales', label: 'Заявки', count: salesRequests.value.length },
  { id: 'service', label: 'Сервис', count: serviceRequests.value.length },
  { id: 'notifications', label: 'Уведомления', count: unreadNotificationsCount.value },
]);

function toggleOrder(id: number) {
  expandedOrders.value = expandedOrders.value.includes(id)
    ? expandedOrders.value.filter((orderId) => orderId !== id)
    : [...expandedOrders.value, id];
}

function isExpanded(id: number): boolean {
  return expandedOrders.value.includes(id);
}

function formatDate(value?: string | null): string {
  return value ? new Date(value).toLocaleString('ru-RU') : 'Не назначено';
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

function formatCurrency(value: number): string {
  return `${Number(value || 0).toLocaleString('ru-RU')} ₽`;
}

function salesTypeLabel(type: SalesRequest['type']): string {
  switch (type) {
    case 'purchase':
      return 'Покупка';
    case 'availability':
      return 'Наличие';
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
  <div class="bg-dark min-h-screen pb-20">
    <section class="relative overflow-hidden border-b border-white/5 bg-dark-lighter/40">
      <div class="absolute inset-0 bg-gradient-to-b from-primary/5 via-transparent to-dark pointer-events-none" />
      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-12">
        <div v-if="user" class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
          <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-primary/15 border border-primary/40 flex items-center justify-center text-primary text-2xl font-bold font-display">
              {{ user.name.trim().charAt(0).toUpperCase() }}
            </div>
            <div>
              <p class="text-primary text-xs font-bold uppercase tracking-[0.24em] mb-1">Личный кабинет</p>
              <h1 class="text-3xl md:text-4xl font-display font-bold text-white uppercase">{{ user.name }}</h1>
              <p class="text-gray-500 text-sm">{{ user.email }}</p>
            </div>
          </div>

          <div class="flex flex-wrap gap-2">
            <RouterLink to="/catalog" class="filter-chip">Каталог</RouterLink>
            <RouterLink to="/service" class="filter-chip">Сервис</RouterLink>
            <RouterLink to="/favorites" class="filter-chip">Избранное</RouterLink>
          </div>
        </div>
      </div>
    </section>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
      <p v-if="loading" class="text-gray-500 py-8">Загрузка...</p>
      <p v-if="errorText" class="mb-6 p-4 bg-red-500/10 border border-red-500/30 text-red-400 text-sm">{{ errorText }}</p>

      <div v-if="!loading" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <aside class="lg:col-span-3 space-y-4">
          <section class="bg-dark-lighter border border-white/5 p-5">
            <p class="text-xs text-gray-600 font-bold uppercase tracking-wider mb-4">Навигация</p>
            <div class="space-y-2">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                class="w-full flex items-center justify-between gap-3 px-4 py-3 border text-left text-sm font-bold uppercase tracking-wider transition-colors"
                :class="activeTab === tab.id ? 'border-primary/60 bg-primary/10 text-primary' : 'border-white/5 bg-dark text-gray-400 hover:text-white hover:border-white/15'"
                @click="activeTab = tab.id"
              >
                <span>{{ tab.label }}</span>
                <span v-if="tab.count" class="text-xs text-gray-500">{{ tab.count }}</span>
              </button>
            </div>
          </section>

          <section v-if="user" class="bg-dark-lighter border border-white/5 p-5">
            <p class="text-xs text-gray-600 font-bold uppercase tracking-wider mb-4">Профиль</p>
            <div class="space-y-3 text-sm">
              <div class="flex items-center justify-between gap-3">
                <span class="text-gray-500">Роль</span>
                <StatusBadge :status="user.role" kind="role" />
              </div>
              <RouterLink v-if="isStaffUser" to="/admin" class="filter-chip block text-center">
                Рабочая панель
              </RouterLink>
              <div class="flex items-center justify-between gap-3">
                <span class="text-gray-500">Избранное</span>
                <span class="text-white font-bold">{{ favoritesCount }}</span>
              </div>
              <div class="flex items-center justify-between gap-3">
                <span class="text-gray-500">Регистрация</span>
                <span class="text-white">{{ user.created_at ? new Date(user.created_at).toLocaleDateString('ru-RU') : '—' }}</span>
              </div>
            </div>
          </section>
        </aside>

        <div class="lg:col-span-9 space-y-6">
          <section v-show="activeTab === 'overview'" class="space-y-6">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
              <button type="button" class="admin-stat-card text-left" @click="activeTab = 'orders'">
                <span class="stat-line bg-primary" />
                <span class="stat-label">Активные заказы</span>
                <span class="stat-value">{{ activeOrders.length }}</span>
                <span class="stat-note">в работе</span>
              </button>
              <button type="button" class="admin-stat-card text-left" @click="activeTab = 'sales'">
                <span class="stat-line bg-orange-500" />
                <span class="stat-label">Заявки</span>
                <span class="stat-value">{{ activeSalesRequests.length }}</span>
                <span class="stat-note">активные</span>
              </button>
              <button type="button" class="admin-stat-card text-left" @click="activeTab = 'service'">
                <span class="stat-line bg-green-500" />
                <span class="stat-label">Сервис</span>
                <span class="stat-value">{{ activeServiceRequests.length }}</span>
                <span class="stat-note">записи</span>
              </button>
              <button type="button" class="admin-stat-card text-left" @click="activeTab = 'payments'">
                <span class="stat-line bg-yellow-500" />
                <span class="stat-label">Оплаты</span>
                <span class="stat-value">{{ pendingPayments.length }}</span>
                <span class="stat-note">ожидают</span>
              </button>
              <button type="button" class="admin-stat-card text-left" @click="activeTab = 'notifications'">
                <span class="stat-line bg-blue-500" />
                <span class="stat-label">Уведомления</span>
                <span class="stat-value">{{ unreadNotificationsCount }}</span>
                <span class="stat-note">новые</span>
              </button>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
              <article class="bg-dark-lighter border border-white/5 p-6">
                <div class="flex items-start justify-between gap-4 mb-5">
                  <div>
                    <p class="text-primary text-xs font-bold uppercase tracking-[0.24em] mb-2">Последний заказ</p>
                    <h2 class="text-2xl font-display font-bold text-white uppercase">{{ latestOrder ? `Заказ #${latestOrder.id}` : 'Заказов пока нет' }}</h2>
                  </div>
                  <StatusBadge v-if="latestOrder" :status="latestOrder.status" kind="order" />
                </div>
                <template v-if="latestOrder">
                  <p class="text-gray-500 text-sm mb-4">{{ formatDate(latestOrder.created_at) }}</p>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div class="bg-dark border border-white/5 p-4">
                      <p class="text-gray-600 uppercase text-xs font-bold mb-1">Сумма</p>
                      <p class="text-primary font-display font-bold text-xl">{{ formatCurrency(latestOrder.total) }}</p>
                    </div>
                    <div class="bg-dark border border-white/5 p-4">
                      <p class="text-gray-600 uppercase text-xs font-bold mb-1">Выдача</p>
                      <p class="text-white font-bold">{{ latestOrder.pickup_point?.name || 'Уточнит менеджер' }}</p>
                    </div>
                  </div>
                  <button type="button" class="filter-chip mt-4" @click="activeTab = 'orders'">Открыть заказы</button>
                </template>
                <RouterLink v-else to="/catalog" class="btn btn-primary"><span>Перейти в каталог</span></RouterLink>
              </article>

              <article class="bg-dark-lighter border border-white/5 p-6">
                <div class="flex items-center justify-between gap-4 mb-5">
                  <div>
                    <p class="text-primary text-xs font-bold uppercase tracking-[0.24em] mb-2">Новые сообщения</p>
                    <h2 class="text-2xl font-display font-bold text-white uppercase">От менеджера</h2>
                  </div>
                  <button type="button" class="filter-chip" :disabled="!unreadNotificationsCount" @click="markNotificationsRead">Прочитано</button>
                </div>
                <div v-if="!notifications.length" class="empty-panel">Уведомлений пока нет.</div>
                <div v-else class="space-y-3">
                  <div v-for="notification in notifications.slice(0, 3)" :key="notification.id" class="bg-dark border border-white/5 p-4">
                    <p class="text-white font-bold">{{ notification.title }}</p>
                    <p class="text-gray-500 text-sm mt-1 line-clamp-2">{{ notification.message }}</p>
                  </div>
                </div>
              </article>
            </div>
          </section>

          <section v-show="activeTab === 'orders'" class="space-y-4">
            <div class="flex items-center justify-between gap-4">
              <h2 class="text-3xl font-display font-bold text-white uppercase">Мои заказы</h2>
              <RouterLink to="/catalog" class="filter-chip">Новый заказ</RouterLink>
            </div>

            <div v-if="!orders.length" class="empty-panel">
              У вас пока нет заказов. Перейдите в каталог и выберите технику.
            </div>

            <article v-for="order in orders" v-else :key="order.id" class="bg-dark-lighter border border-white/5 overflow-hidden">
              <button type="button" class="w-full p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4 text-left hover:bg-white/[0.02] transition-colors" @click="toggleOrder(order.id)">
                <div>
                  <div class="flex items-center gap-3 mb-2">
                    <p class="text-white font-display font-bold uppercase">Заказ #{{ order.id }}</p>
                    <StatusBadge :status="order.status" kind="order" />
                  </div>
                  <p class="text-gray-500 text-sm">{{ order.items.length }} поз. · {{ formatDate(order.created_at) }}</p>
                </div>
                <div class="flex items-center gap-4">
                  <span class="text-primary font-display font-bold text-xl">{{ formatCurrency(order.total) }}</span>
                  <span class="text-gray-600">{{ isExpanded(order.id) ? 'Свернуть' : 'Подробнее' }}</span>
                </div>
              </button>

              <div v-if="isExpanded(order.id)" class="border-t border-white/5 p-5 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                  <div class="bg-dark border border-white/5 p-4">
                    <p class="text-xs text-gray-600 font-bold uppercase mb-1">Оплата</p>
                    <p class="text-white font-bold">{{ paymentMethodLabel(order.payment_method) }}</p>
                    <p class="text-gray-500 text-sm">{{ paymentStatusLabel(order.payments?.[0]?.status ?? order.payment_status) }}</p>
                    <p v-if="order.payments?.[0]?.transaction_id" class="text-gray-600 text-xs mt-1">{{ order.payments[0].transaction_id }}</p>
                  </div>
                  <div class="bg-dark border border-white/5 p-4">
                    <p class="text-xs text-gray-600 font-bold uppercase mb-1">Пункт выдачи</p>
                    <p class="text-white font-bold">{{ order.pickup_point?.name || 'Уточнит менеджер' }}</p>
                    <p class="text-gray-500 text-sm">{{ order.pickup_point?.address || 'Адрес будет согласован' }}</p>
                  </div>
                  <div class="bg-dark border border-white/5 p-4">
                    <p class="text-xs text-gray-600 font-bold uppercase mb-1">Готовность</p>
                    <p class="text-white font-bold">{{ formatDate(order.pickup_ready_at) }}</p>
                    <p class="text-gray-500 text-sm">Бронь: {{ order.reservations?.[0]?.status === 'active' ? 'активна' : 'по статусу заказа' }}</p>
                  </div>
                </div>

                <div class="space-y-2">
                  <div v-for="item in order.items" :key="item.id" class="flex items-center justify-between gap-4 py-2 border-b border-white/5 last:border-0">
                    <span class="text-gray-300">{{ item.name }} <span class="text-gray-600">× {{ item.quantity }}</span></span>
                    <span class="text-white font-display font-bold">{{ formatCurrency(item.price * item.quantity) }}</span>
                  </div>
                </div>
              </div>
            </article>
          </section>

          <section v-show="activeTab === 'payments'" class="space-y-4">
            <div class="flex items-center justify-between gap-4">
              <h2 class="text-3xl font-display font-bold text-white uppercase">Мои оплаты</h2>
              <RouterLink to="/catalog" class="filter-chip">К покупкам</RouterLink>
            </div>
            <div v-if="!payments.length" class="empty-panel">Оплат пока нет. После оформления заказа здесь появится способ оплаты и статус.</div>
            <article v-for="payment in payments" v-else :key="payment.id" class="bg-dark-lighter border border-white/5 p-5">
              <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                  <p class="text-white font-display font-bold uppercase">Оплата #{{ payment.id }} · Заказ #{{ payment.order_id }}</p>
                  <p class="text-gray-500 text-sm mt-1">{{ paymentMethodLabel(payment.method) }} · {{ formatDate(payment.created_at) }}</p>
                  <p v-if="payment.transaction_id" class="text-gray-600 text-xs mt-2">{{ payment.transaction_id }}</p>
                </div>
                <div class="md:text-right">
                  <p class="text-primary font-display font-bold text-2xl">{{ formatCurrency(payment.amount) }}</p>
                  <StatusBadge :status="payment.status" />
                </div>
              </div>
              <p class="text-gray-500 text-sm mt-4">
                {{ payment.status === 'paid' ? 'Оплата подтверждена менеджером.' : 'После подтверждения оплаты менеджером вы получите уведомление.' }}
              </p>
            </article>
          </section>

          <section v-show="activeTab === 'sales'" class="space-y-4">
            <div class="flex items-center justify-between gap-4">
              <h2 class="text-3xl font-display font-bold text-white uppercase">Заявки на покупку</h2>
              <RouterLink to="/catalog" class="filter-chip">Оставить заявку</RouterLink>
            </div>
            <div v-if="!salesRequests.length" class="empty-panel">У вас пока нет заявок на покупку.</div>
            <article v-for="request in salesRequests" v-else :key="request.id" class="bg-dark-lighter border border-white/5 p-5">
              <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                <div>
                  <p class="text-white font-display font-bold uppercase">#{{ request.id }} · {{ salesTypeLabel(request.type) }}</p>
                  <p class="text-gray-300 mt-2">{{ request.motorcycle ? `${request.motorcycle.brand} ${request.motorcycle.model}` : 'Подбор техники' }}</p>
                  <p class="text-gray-600 text-sm mt-1">{{ formatDate(request.created_at) }}</p>
                </div>
                <StatusBadge :status="request.status" kind="sales" />
              </div>
              <p v-if="request.comment" class="text-gray-500 text-sm mt-4">{{ request.comment }}</p>
            </article>
          </section>

          <section v-show="activeTab === 'service'" class="space-y-4">
            <div class="flex items-center justify-between gap-4">
              <h2 class="text-3xl font-display font-bold text-white uppercase">Записи на сервис</h2>
              <RouterLink to="/service" class="filter-chip">Записаться</RouterLink>
            </div>
            <div v-if="!serviceRequests.length" class="empty-panel">У вас пока нет записей на сервис.</div>
            <article v-for="request in serviceRequests" v-else :key="request.id" class="bg-dark-lighter border border-white/5 p-5">
              <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                <div>
                  <p class="text-white font-display font-bold uppercase">#{{ request.id }} · {{ request.service_type }}</p>
                  <p class="text-gray-300 mt-2">{{ request.motorcycle_model }}</p>
                  <p class="text-gray-600 text-sm mt-1">Дата: {{ request.preferred_date ? new Date(request.preferred_date).toLocaleDateString('ru-RU') : 'не указана' }}</p>
                  <p v-if="request.service_slot" class="text-gray-600 text-sm mt-1">Время: {{ request.service_slot.starts_at.slice(0, 5) }}-{{ request.service_slot.ends_at.slice(0, 5) }}</p>
                </div>
                <StatusBadge :status="request.status" kind="service" />
              </div>
              <p v-if="request.comment" class="text-gray-500 text-sm mt-4">{{ request.comment }}</p>
            </article>
          </section>

          <section v-show="activeTab === 'notifications'" class="space-y-4">
            <div class="flex items-center justify-between gap-4">
              <h2 class="text-3xl font-display font-bold text-white uppercase">Уведомления</h2>
              <button type="button" class="filter-chip" :disabled="!unreadNotificationsCount" @click="markNotificationsRead">Отметить прочитанными</button>
            </div>
            <div v-if="!notifications.length" class="empty-panel">Пока нет уведомлений по заказам.</div>
            <article v-for="notification in notifications" v-else :key="notification.id" class="bg-dark-lighter border p-5" :class="notification.is_read ? 'border-white/5' : 'border-primary/40'">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-white font-display font-bold uppercase">{{ notification.title }}</p>
                  <p class="text-gray-500 text-sm mt-2">{{ notification.message }}</p>
                  <p class="text-gray-600 text-xs mt-3">{{ formatDate(notification.created_at) }}</p>
                </div>
                <span v-if="!notification.is_read" class="text-[10px] text-primary font-bold uppercase tracking-wider">Новое</span>
              </div>
            </article>
          </section>
        </div>
      </div>
    </main>
  </div>
</template>
