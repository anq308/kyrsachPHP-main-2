<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import api from '../api';
import StatusBadge from '../components/ui/StatusBadge.vue';
import type { ContactMessage, Motorcycle, Order, SalesRequest, ServiceRequest, User } from '../types';

interface DashboardStats {
  usersCount: number;
  ordersCount: number;
  salesRequestsCount: number;
  serviceRequestsCount: number;
  contactMessagesCount: number;
  newSalesRequestsCount: number;
  newServiceRequestsCount: number;
  totalRevenue: number;
  unavailableCount: number;
}

interface AdminUser extends User {
  orders?: Order[];
  sales_requests?: SalesRequest[];
  service_requests?: ServiceRequest[];
}

type AdminTab = 'dashboard' | 'products' | 'orders' | 'sales' | 'service' | 'users' | 'messages';
type ProductGroupMode = 'type' | 'brand' | 'availability';

const loading = ref(true);
const saving = ref(false);
const errorText = ref('');
const successText = ref('');
const activeTab = ref<AdminTab>('dashboard');

const searchQuery = ref('');
const filterStatus = ref<'all' | 'available' | 'unavailable'>('all');
const productGroupMode = ref<ProductGroupMode>('type');
const selectedProductGroup = ref('all');
const salesStatusFilter = ref<'all' | SalesRequest['status']>('all');
const serviceStatusFilter = ref<'all' | ServiceRequest['status']>('all');
const showProductForm = ref(false);

const motorcycles = ref<Motorcycle[]>([]);
const orders = ref<Order[]>([]);
const salesRequests = ref<SalesRequest[]>([]);
const serviceRequests = ref<ServiceRequest[]>([]);
const users = ref<AdminUser[]>([]);
const messages = ref<ContactMessage[]>([]);
const stats = ref<DashboardStats>({
  usersCount: 0,
  ordersCount: 0,
  salesRequestsCount: 0,
  serviceRequestsCount: 0,
  contactMessagesCount: 0,
  newSalesRequestsCount: 0,
  newServiceRequestsCount: 0,
  totalRevenue: 0,
  unavailableCount: 0,
});

const editingId = ref<number | null>(null);

const form = reactive({
  brand: '',
  model: '',
  type: 'Enduro',
  year: new Date().getFullYear(),
  engine_capacity: 250,
  power: 20,
  price: 100000,
  description: '',
  image_url: '/images/product_enduro_1.png',
  is_available: true,
  transmission: '',
  cooling: '',
  fuel_system: '',
  weight: null as number | null,
  tank_capacity: null as number | null,
});

const tabs: Array<{ id: AdminTab; label: string }> = [
  { id: 'dashboard', label: 'Дашборд' },
  { id: 'products', label: 'Товары' },
  { id: 'orders', label: 'Заказы' },
  { id: 'sales', label: 'Заявки на покупку' },
  { id: 'service', label: 'Сервисные заявки' },
  { id: 'users', label: 'Пользователи' },
  { id: 'messages', label: 'Сообщения' },
];

const orderStatusOptions: Array<{ value: Order['status']; label: string }> = [
  { value: 'new', label: 'Новый' },
  { value: 'processing', label: 'В обработке' },
  { value: 'approved', label: 'Подтверждён' },
  { value: 'ready_for_pickup', label: 'Готов к выдаче' },
  { value: 'completed', label: 'Завершён' },
  { value: 'cancelled', label: 'Отменён' },
];

const salesStatusOptions: Array<{ value: 'all' | SalesRequest['status']; label: string }> = [
  { value: 'all', label: 'Все' },
  { value: 'new', label: 'Новые' },
  { value: 'in_progress', label: 'В работе' },
  { value: 'approved', label: 'Согласованные' },
  { value: 'completed', label: 'Завершённые' },
  { value: 'cancelled', label: 'Отменённые' },
];

const serviceStatusOptions: Array<{ value: 'all' | ServiceRequest['status']; label: string }> = [
  { value: 'all', label: 'Все' },
  { value: 'new', label: 'Новые' },
  { value: 'confirmed', label: 'Подтверждённые' },
  { value: 'in_service', label: 'В сервисе' },
  { value: 'done', label: 'Готовые' },
  { value: 'cancelled', label: 'Отменённые' },
];

const salesTypeLabels: Record<SalesRequest['type'], string> = {
  purchase: 'Покупка',
  consultation: 'Консультация',
  availability: 'Уточнение наличия',
  preorder: 'Предзаказ',
  test_drive: 'Тест-драйв',
};

const productGroupModes: Array<{ value: ProductGroupMode; label: string }> = [
  { value: 'type', label: 'По типу' },
  { value: 'brand', label: 'По бренду' },
  { value: 'availability', label: 'По наличию' },
];

const filteredMotorcycles = computed(() => {
  const query = searchQuery.value.toLowerCase().trim();

  return motorcycles.value.filter((moto) => {
    const searchable = `${moto.brand} ${moto.model} ${moto.type}`.toLowerCase();
    const matchesSearch = !query || searchable.includes(query);
    const matchesStatus =
      filterStatus.value === 'all' ||
      (filterStatus.value === 'available' && moto.is_available) ||
      (filterStatus.value === 'unavailable' && !moto.is_available);

    return matchesSearch && matchesStatus;
  });
});

const inventorySummary = computed(() => {
  const available = motorcycles.value.filter((moto) => moto.is_available);

  return {
    totalValue: motorcycles.value.reduce((sum, moto) => sum + Number(moto.price || 0), 0),
    availableCount: available.length,
    averagePrice: motorcycles.value.length
      ? Math.round(motorcycles.value.reduce((sum, moto) => sum + Number(moto.price || 0), 0) / motorcycles.value.length)
      : 0,
  };
});

const productGroupCards = computed(() => groupMotorcycles(filteredMotorcycles.value));

const visibleMotorcycles = computed(() => {
  if (selectedProductGroup.value === 'all') {
    return filteredMotorcycles.value;
  }

  return filteredMotorcycles.value.filter((moto) => productGroupKey(moto) === selectedProductGroup.value);
});

const groupedMotorcycles = computed(() => groupMotorcycles(visibleMotorcycles.value));

const filteredSalesRequests = computed(() =>
  salesRequests.value.filter((request) => salesStatusFilter.value === 'all' || request.status === salesStatusFilter.value),
);

const filteredServiceRequests = computed(() =>
  serviceRequests.value.filter((request) => serviceStatusFilter.value === 'all' || request.status === serviceStatusFilter.value),
);

const dashboardSalesRequests = computed(() => salesRequests.value.filter((request) => request.status === 'new').slice(0, 5));
const dashboardServiceRequests = computed(() => serviceRequests.value.filter((request) => request.status === 'new').slice(0, 5));
const latestOrders = computed(() => orders.value.slice(0, 5));
const unavailableMotorcycles = computed(() => motorcycles.value.filter((moto) => !moto.is_available).slice(0, 5));

function formatCurrency(value: number): string {
  return `${Number(value || 0).toLocaleString('ru-RU')} ₽`;
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

function reservationLabel(order: Order): string {
  const active = order.reservations?.find((reservation) => reservation.status === 'active');

  if (active?.expires_at) {
    return `Активна до ${new Date(active.expires_at).toLocaleDateString('ru-RU')}`;
  }

  if (active) {
    return 'Активна';
  }

  return 'Нет активной брони';
}

function productGroupKey(moto: Motorcycle): string {
  if (productGroupMode.value === 'brand') {
    return moto.brand || 'Без бренда';
  }

  if (productGroupMode.value === 'availability') {
    return moto.is_available ? 'available' : 'unavailable';
  }

  return moto.type || 'Без типа';
}

function productGroupLabel(key: string): string {
  if (productGroupMode.value === 'availability') {
    return key === 'available' ? 'В наличии' : 'Нет в наличии';
  }

  return key;
}

function groupMotorcycles(items: Motorcycle[]) {
  const groups = new Map<string, { key: string; label: string; count: number; value: number; items: Motorcycle[] }>();

  items.forEach((moto) => {
    const key = productGroupKey(moto);
    const group = groups.get(key) ?? {
      key,
      label: productGroupLabel(key),
      count: 0,
      value: 0,
      items: [],
    };

    group.count += 1;
    group.value += Number(moto.price || 0);
    group.items.push(moto);
    groups.set(key, group);
  });

  return [...groups.values()].sort((a, b) => b.count - a.count || a.label.localeCompare(b.label, 'ru'));
}

function setProductGroupMode(mode: ProductGroupMode) {
  productGroupMode.value = mode;
  selectedProductGroup.value = 'all';
}

function tabCount(tab: AdminTab): number {
  const counts: Record<AdminTab, number> = {
    dashboard: stats.value.newSalesRequestsCount + stats.value.newServiceRequestsCount,
    products: motorcycles.value.length,
    orders: orders.value.length,
    sales: salesRequests.value.length,
    service: serviceRequests.value.length,
    users: users.value.length,
    messages: messages.value.length,
  };

  return counts[tab];
}

function resetForm() {
  editingId.value = null;
  showProductForm.value = false;
  form.brand = '';
  form.model = '';
  form.type = 'Enduro';
  form.year = new Date().getFullYear();
  form.engine_capacity = 250;
  form.power = 20;
  form.price = 100000;
  form.description = '';
  form.image_url = '/images/product_enduro_1.png';
  form.is_available = true;
  form.transmission = '';
  form.cooling = '';
  form.fuel_system = '';
  form.weight = null;
  form.tank_capacity = null;
}

function openCreateMotorcycleForm() {
  resetForm();
  showProductForm.value = true;
}

function editMotorcycle(moto: Motorcycle) {
  editingId.value = moto.id;
  showProductForm.value = true;
  form.brand = moto.brand;
  form.model = moto.model;
  form.type = moto.type;
  form.year = moto.year;
  form.engine_capacity = moto.engine_capacity;
  form.power = moto.power;
  form.price = moto.price;
  form.description = moto.description;
  form.image_url = moto.image_url;
  form.is_available = moto.is_available;
  form.transmission = moto.transmission ?? '';
  form.cooling = moto.cooling ?? '';
  form.fuel_system = moto.fuel_system ?? '';
  form.weight = moto.weight ?? null;
  form.tank_capacity = moto.tank_capacity ?? null;
  activeTab.value = 'products';
}

async function loadDashboard() {
  loading.value = true;
  errorText.value = '';

  try {
    const { data } = await api.get('/admin/dashboard');
    motorcycles.value = data.motorcycles ?? [];
    orders.value = data.orders ?? [];
    salesRequests.value = data.sales_requests ?? [];
    serviceRequests.value = data.service_requests ?? [];
    users.value = data.users ?? [];
    messages.value = data.messages ?? [];
    stats.value = data.stats;
  } catch {
    errorText.value = 'Не удалось загрузить данные админ-панели.';
  } finally {
    loading.value = false;
  }
}

async function saveMotorcycle() {
  saving.value = true;
  errorText.value = '';
  successText.value = '';

  try {
    const { data } = editingId.value
      ? await api.put(`/admin/motorcycles/${editingId.value}`, form)
      : await api.post('/admin/motorcycles', form);

    successText.value = data.message;
    resetForm();
    showProductForm.value = false;
    await loadDashboard();
  } catch (error: any) {
    errorText.value = error?.response?.data?.message ?? 'Не удалось сохранить товар.';
  } finally {
    saving.value = false;
  }
}

async function deleteMotorcycle(id: number) {
  if (!confirm('Удалить товар?')) {
    return;
  }

  try {
    const { data } = await api.delete(`/admin/motorcycles/${id}`);
    successText.value = data.message;
    await loadDashboard();
  } catch {
    errorText.value = 'Не удалось удалить товар.';
  }
}

async function updateOrderStatus(orderId: number, status: Order['status']) {
  try {
    const { data } = await api.patch(`/admin/orders/${orderId}/status`, { status });
    successText.value = data.message;
    await loadDashboard();
  } catch {
    errorText.value = 'Не удалось обновить статус заказа.';
  }
}

async function updateSalesStatus(requestId: number, status: SalesRequest['status']) {
  try {
    const { data } = await api.patch(`/admin/sales-requests/${requestId}/status`, { status });
    successText.value = data.message;
    await loadDashboard();
  } catch {
    errorText.value = 'Не удалось обновить статус заявки.';
  }
}

async function deleteSalesRequest(requestId: number) {
  if (!confirm('Удалить заявку на покупку?')) {
    return;
  }

  try {
    const { data } = await api.delete(`/admin/sales-requests/${requestId}`);
    successText.value = data.message;
    await loadDashboard();
  } catch {
    errorText.value = 'Не удалось удалить заявку.';
  }
}

async function updateServiceStatus(requestId: number, status: ServiceRequest['status']) {
  try {
    const { data } = await api.patch(`/admin/service-requests/${requestId}/status`, { status });
    successText.value = data.message;
    await loadDashboard();
  } catch {
    errorText.value = 'Не удалось обновить статус сервисной заявки.';
  }
}

async function deleteServiceRequest(requestId: number) {
  if (!confirm('Удалить сервисную заявку?')) {
    return;
  }

  try {
    const { data } = await api.delete(`/admin/service-requests/${requestId}`);
    successText.value = data.message;
    await loadDashboard();
  } catch {
    errorText.value = 'Не удалось удалить сервисную заявку.';
  }
}

onMounted(loadDashboard);
</script>

<template>
  <div>
    <div class="relative bg-dark overflow-hidden py-16 md:py-20">
      <div class="absolute inset-0">
        <div class="absolute inset-0 bg-gradient-to-b from-primary/5 via-dark to-dark" />
        <div class="absolute top-0 right-0 w-96 h-96 bg-primary/5 blur-3xl" />
      </div>
      <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6">
          <div>
            <p class="text-primary text-sm font-bold uppercase tracking-[0.25em] mb-3">Администратор AVANTIS</p>
            <h1 class="text-4xl md:text-6xl font-bold font-display text-white uppercase italic tracking-tight">Панель менеджера</h1>
          </div>
          <p class="max-w-xl text-gray-500 text-lg">
            Управление каталогом, заказами, заявками на покупку, сервисом, клиентами и входящими сообщениями.
          </p>
        </div>
      </div>
    </div>

    <div class="bg-dark min-h-screen pb-24">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p v-if="errorText" class="mb-6 p-4 bg-red-500/10 border border-red-500/30 text-red-400 text-sm">{{ errorText }}</p>
        <p v-if="successText" class="mb-6 p-4 bg-green-500/10 border border-green-500/30 text-green-400 text-sm">{{ successText }}</p>

        <p v-if="loading" class="text-gray-500 py-8">Загрузка...</p>

        <div v-else>
          <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 -mt-8 mb-10">
            <button class="admin-stat-card text-left" @click="activeTab = 'orders'">
              <span class="stat-line bg-blue-500" />
              <span class="stat-label">Заказы</span>
              <span class="stat-value">{{ stats.ordersCount }}</span>
              <span class="stat-note">{{ formatCurrency(stats.totalRevenue) }}</span>
            </button>
            <button class="admin-stat-card text-left" @click="activeTab = 'sales'">
              <span class="stat-line bg-primary" />
              <span class="stat-label">Покупка</span>
              <span class="stat-value">{{ stats.salesRequestsCount }}</span>
              <span class="stat-note">{{ stats.newSalesRequestsCount }} новых</span>
            </button>
            <button class="admin-stat-card text-left" @click="activeTab = 'service'">
              <span class="stat-line bg-green-500" />
              <span class="stat-label">Сервис</span>
              <span class="stat-value">{{ stats.serviceRequestsCount }}</span>
              <span class="stat-note">{{ stats.newServiceRequestsCount }} новых</span>
            </button>
            <button class="admin-stat-card text-left" @click="activeTab = 'users'">
              <span class="stat-line bg-purple-500" />
              <span class="stat-label">Клиенты</span>
              <span class="stat-value">{{ stats.usersCount }}</span>
              <span class="stat-note">пользователей</span>
            </button>
            <button class="admin-stat-card text-left" @click="activeTab = 'products'">
              <span class="stat-line bg-white/60" />
              <span class="stat-label">Товары</span>
              <span class="stat-value">{{ motorcycles.length }}</span>
              <span class="stat-note">{{ stats.unavailableCount }} нет в наличии</span>
            </button>
            <button class="admin-stat-card text-left" @click="activeTab = 'messages'">
              <span class="stat-line bg-yellow-500" />
              <span class="stat-label">Сообщения</span>
              <span class="stat-value">{{ stats.contactMessagesCount }}</span>
              <span class="stat-note">обратная связь</span>
            </button>
          </div>

          <div class="flex items-center gap-1 mb-8 border-b border-white/5 overflow-x-auto">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              class="px-5 py-3 text-sm font-bold font-display uppercase tracking-wider border-b-2 transition-all whitespace-nowrap"
              :class="activeTab === tab.id ? 'text-primary border-primary' : 'text-gray-500 border-transparent hover:text-gray-300'"
              @click="activeTab = tab.id"
            >
              {{ tab.label }} <span class="text-[10px] text-gray-600 ml-1">{{ tabCount(tab.id) }}</span>
            </button>
          </div>

          <section v-show="activeTab === 'dashboard'" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
              <div class="admin-record lg:col-span-2">
                <div class="flex items-center justify-between gap-4 mb-5">
                  <div>
                    <h2 class="text-2xl md:text-3xl font-display font-bold text-white uppercase italic">Новые обращения</h2>
                    <p class="text-gray-500 text-sm">Заявки, которые требуют реакции менеджера.</p>
                  </div>
                  <button type="button" class="filter-chip filter-chip-active" @click="activeTab = 'sales'">Покупка</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <article v-for="request in dashboardSalesRequests" :key="request.id" class="bg-dark border border-white/5 p-4">
                    <div class="flex items-start justify-between gap-3 mb-3">
                      <p class="text-white font-display font-bold uppercase">#{{ request.id }} · {{ salesTypeLabels[request.type] }}</p>
                      <StatusBadge :status="request.status" kind="sales" />
                    </div>
                    <p class="text-gray-300 text-sm">{{ request.motorcycle ? `${request.motorcycle.brand} ${request.motorcycle.model}` : 'Подбор техники' }}</p>
                    <p class="text-gray-500 text-xs mt-2">{{ request.name }} · {{ request.phone }}</p>
                  </article>
                  <div v-if="!dashboardSalesRequests.length" class="empty-panel md:col-span-2">Новых заявок на покупку нет.</div>
                </div>
              </div>

              <div class="admin-record">
                <div class="flex items-center justify-between gap-4 mb-5">
                  <div>
                    <h2 class="text-2xl font-display font-bold text-white uppercase italic">Сервис</h2>
                    <p class="text-gray-500 text-sm">Новые записи на обслуживание.</p>
                  </div>
                  <button type="button" class="filter-chip filter-chip-active" @click="activeTab = 'service'">Сервис</button>
                </div>

                <div class="space-y-3">
                  <article v-for="request in dashboardServiceRequests" :key="request.id" class="bg-dark border border-white/5 p-4">
                    <div class="flex items-start justify-between gap-3 mb-2">
                      <p class="text-white font-display font-bold uppercase">#{{ request.id }}</p>
                      <StatusBadge :status="request.status" kind="service" />
                    </div>
                    <p class="text-gray-300 text-sm">{{ request.motorcycle_model }}</p>
                    <p class="text-gray-500 text-xs mt-1">{{ request.service_type }} · {{ request.phone }}</p>
                  </article>
                  <div v-if="!dashboardServiceRequests.length" class="empty-panel">Новых сервисных заявок нет.</div>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div class="admin-record">
                <div class="flex items-center justify-between gap-4 mb-5">
                  <h2 class="text-2xl font-display font-bold text-white uppercase italic">Последние заказы</h2>
                  <button type="button" class="text-xs font-bold uppercase tracking-wider text-primary hover:text-white transition-colors" @click="activeTab = 'orders'">Все заказы</button>
                </div>
                <div class="space-y-3">
                  <article v-for="order in latestOrders" :key="order.id" class="flex items-center justify-between gap-4 bg-dark border border-white/5 p-4">
                    <div>
                      <p class="text-white font-display font-bold uppercase">Заказ #{{ order.id }}</p>
                      <p class="text-gray-500 text-xs">{{ order.name || 'Гость' }} · {{ new Date(order.created_at).toLocaleDateString('ru-RU') }}</p>
                    </div>
                    <div class="text-right">
                      <StatusBadge :status="order.status" kind="order" />
                      <p class="text-primary font-display font-bold mt-2">{{ formatCurrency(order.total) }}</p>
                    </div>
                  </article>
                  <div v-if="!latestOrders.length" class="empty-panel">Заказов пока нет.</div>
                </div>
              </div>

              <div class="admin-record">
                <div class="flex items-center justify-between gap-4 mb-5">
                  <h2 class="text-2xl font-display font-bold text-white uppercase italic">Склад</h2>
                  <button type="button" class="text-xs font-bold uppercase tracking-wider text-primary hover:text-white transition-colors" @click="activeTab = 'products'">Каталог</button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                  <div class="bg-dark border border-white/5 p-4">
                    <p class="text-xs text-gray-600 font-bold uppercase tracking-wider">Позиций</p>
                    <p class="text-2xl font-display font-bold text-white">{{ motorcycles.length }}</p>
                  </div>
                  <div class="bg-dark border border-white/5 p-4">
                    <p class="text-xs text-gray-600 font-bold uppercase tracking-wider">В наличии</p>
                    <p class="text-2xl font-display font-bold text-green-300">{{ inventorySummary.availableCount }}</p>
                  </div>
                  <div class="bg-dark border border-white/5 p-4">
                    <p class="text-xs text-gray-600 font-bold uppercase tracking-wider">Нет</p>
                    <p class="text-2xl font-display font-bold text-red-300">{{ stats.unavailableCount }}</p>
                  </div>
                </div>
                <div class="space-y-3">
                  <article v-for="moto in unavailableMotorcycles" :key="moto.id" class="flex items-center justify-between gap-4 bg-dark border border-white/5 p-4">
                    <p class="text-white text-sm font-bold">{{ moto.brand }} {{ moto.model }}</p>
                    <StatusBadge status="unavailable" kind="product" />
                  </article>
                  <div v-if="!unavailableMotorcycles.length" class="empty-panel">Все товары доступны для продажи.</div>
                </div>
              </div>
            </div>
          </section>

          <section v-show="activeTab === 'products'">
            <div class="bg-dark-lighter border border-white/5 p-5 md:p-6 mb-8">
              <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                <div>
                  <h2 class="text-2xl md:text-3xl font-display font-bold text-white uppercase italic mb-2">Склад и каталог</h2>
                  <p class="text-gray-500 max-w-2xl">
                    Товары сгруппированы, чтобы менеджер быстрее видел структуру склада: техника, ATV, запчасти, бренды и наличие.
                  </p>
                </div>

                <button type="button" class="btn btn-primary shrink-0" @click="openCreateMotorcycleForm">
                  <span>Добавить товар</span>
                </button>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-6">
                <div class="bg-dark border border-white/5 p-4">
                  <p class="text-xs text-gray-600 font-bold uppercase tracking-wider mb-1">Стоимость склада</p>
                  <p class="text-xl font-display font-bold text-white">{{ formatCurrency(inventorySummary.totalValue) }}</p>
                </div>
                <div class="bg-dark border border-white/5 p-4">
                  <p class="text-xs text-gray-600 font-bold uppercase tracking-wider mb-1">В наличии</p>
                  <p class="text-xl font-display font-bold text-green-300">{{ inventorySummary.availableCount }} / {{ motorcycles.length }}</p>
                </div>
                <div class="bg-dark border border-white/5 p-4">
                  <p class="text-xs text-gray-600 font-bold uppercase tracking-wider mb-1">Средняя цена</p>
                  <p class="text-xl font-display font-bold text-primary">{{ formatCurrency(inventorySummary.averagePrice) }}</p>
                </div>
              </div>
            </div>

            <form v-if="showProductForm" class="bg-dark-lighter border border-white/5 mb-8" @submit.prevent="saveMotorcycle">
              <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold font-display text-white uppercase">{{ editingId ? 'Редактирование товара' : 'Новый товар' }}</h2>
                <StatusBadge :status="form.is_available ? 'available' : 'unavailable'" kind="product" />
              </div>

              <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <input v-model="form.brand" class="field-dark" placeholder="Бренд" required />
                <input v-model="form.model" class="field-dark" placeholder="Модель" required />
                <input v-model="form.type" class="field-dark" placeholder="Тип" required />
                <input v-model.number="form.year" type="number" class="field-dark" placeholder="Год" required />
                <input v-model.number="form.engine_capacity" type="number" class="field-dark" placeholder="Объём" required />
                <input v-model.number="form.power" type="number" class="field-dark" placeholder="Мощность" required />
                <input v-model.number="form.price" type="number" class="field-dark" placeholder="Цена" required />
                <input v-model="form.image_url" class="field-dark" placeholder="URL картинки" required />
                <input v-model="form.transmission" class="field-dark" placeholder="КПП" />
                <input v-model="form.cooling" class="field-dark" placeholder="Охлаждение" />
                <input v-model="form.fuel_system" class="field-dark" placeholder="Подача топлива" />
                <input v-model.number="form.weight" type="number" class="field-dark" placeholder="Вес" />
                <input v-model.number="form.tank_capacity" type="number" step="0.1" class="field-dark" placeholder="Бак" />
                <label class="flex items-center gap-3 text-sm text-gray-300">
                  <input v-model="form.is_available" type="checkbox" class="w-5 h-5 border-2 border-white/20 bg-dark text-primary" />
                  В наличии
                </label>
                <textarea v-model="form.description" class="field-dark md:col-span-2 lg:col-span-2" rows="3" placeholder="Описание" required />
              </div>

              <div class="px-6 pb-6 flex flex-wrap items-center gap-3">
                <button type="submit" class="btn btn-primary" :disabled="saving"><span>{{ saving ? 'Сохранение...' : (editingId ? 'Сохранить изменения' : 'Добавить товар') }}</span></button>
                <button type="button" class="px-6 py-3 border border-white/10 text-gray-400 text-sm font-bold font-display uppercase tracking-wider hover:border-white/30 hover:text-white transition-all" @click="resetForm">Сброс</button>
              </div>
            </form>

            <div class="bg-dark-lighter border border-white/5 p-5 md:p-6 mb-6">
              <div class="flex flex-col lg:flex-row gap-4 lg:items-center lg:justify-between mb-5">
                <div class="relative flex-1">
                  <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" /></svg>
                  <input v-model="searchQuery" type="text" placeholder="Поиск по названию, бренду или типу..." class="field-dark pl-12" />
                </div>
                <div class="flex gap-2 overflow-x-auto">
                  <button class="filter-chip" :class="filterStatus === 'all' ? 'filter-chip-active' : ''" @click="filterStatus = 'all'">Все</button>
                  <button class="filter-chip" :class="filterStatus === 'available' ? 'filter-chip-active' : ''" @click="filterStatus = 'available'">В наличии</button>
                  <button class="filter-chip" :class="filterStatus === 'unavailable' ? 'filter-chip-active' : ''" @click="filterStatus = 'unavailable'">Нет</button>
                </div>
              </div>

              <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-5">
                <div>
                  <p class="text-xs text-gray-600 font-bold uppercase tracking-wider mb-2">Группировать товары</p>
                  <div class="flex gap-2 overflow-x-auto">
                    <button
                      v-for="mode in productGroupModes"
                      :key="mode.value"
                      type="button"
                      class="filter-chip"
                      :class="productGroupMode === mode.value ? 'filter-chip-active' : ''"
                      @click="setProductGroupMode(mode.value)"
                    >
                      {{ mode.label }}
                    </button>
                  </div>
                </div>
                <button type="button" class="text-xs font-bold uppercase tracking-wider text-gray-500 hover:text-primary transition-colors" @click="selectedProductGroup = 'all'">
                  Показать все группы
                </button>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <button
                  type="button"
                  class="product-group-card"
                  :class="selectedProductGroup === 'all' ? 'product-group-card-active' : ''"
                  @click="selectedProductGroup = 'all'"
                >
                  <span class="text-xs text-gray-600 font-bold uppercase tracking-wider">Все группы</span>
                  <span class="text-2xl font-display font-bold text-white">{{ filteredMotorcycles.length }}</span>
                  <span class="text-xs text-gray-500">{{ formatCurrency(filteredMotorcycles.reduce((sum, moto) => sum + moto.price, 0)) }}</span>
                </button>

                <button
                  v-for="group in productGroupCards"
                  :key="group.key"
                  type="button"
                  class="product-group-card"
                  :class="selectedProductGroup === group.key ? 'product-group-card-active' : ''"
                  @click="selectedProductGroup = group.key"
                >
                  <span class="text-xs text-gray-600 font-bold uppercase tracking-wider">{{ group.label }}</span>
                  <span class="text-2xl font-display font-bold text-white">{{ group.count }}</span>
                  <span class="text-xs text-gray-500">{{ formatCurrency(group.value) }}</span>
                </button>
              </div>
            </div>

            <div class="bg-dark-lighter border border-white/5 overflow-hidden">
              <div class="overflow-x-auto">
                <table class="admin-table">
                  <thead>
                    <tr>
                      <th>Товар</th>
                      <th class="hidden md:table-cell">Тип</th>
                      <th>Цена</th>
                      <th class="hidden md:table-cell">Статус</th>
                      <th class="text-right">Действия</th>
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="group in groupedMotorcycles" :key="group.key">
                      <tr class="bg-dark/70">
                        <td colspan="5">
                          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <span class="text-primary font-display font-bold uppercase">{{ group.label }}</span>
                            <span class="text-xs text-gray-500">{{ group.count }} поз. · {{ formatCurrency(group.value) }}</span>
                          </div>
                        </td>
                      </tr>

                      <tr v-for="motorcycle in group.items" :key="motorcycle.id">
                        <td>
                          <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-dark border border-white/5 overflow-hidden flex-shrink-0"><img :src="motorcycle.image_url" alt="" class="w-full h-full object-cover" /></div>
                            <div>
                              <p class="text-white font-bold text-sm font-display uppercase">{{ motorcycle.brand }} {{ motorcycle.model }}</p>
                              <p class="text-gray-600 text-xs">{{ motorcycle.year }} г. · {{ motorcycle.engine_capacity }} см³ · {{ motorcycle.power }} л.с.</p>
                            </div>
                          </div>
                        </td>
                        <td class="hidden md:table-cell"><span class="px-2 py-1 bg-white/5 text-gray-400 text-xs font-bold uppercase">{{ motorcycle.type }}</span></td>
                        <td><span class="text-primary font-bold font-display text-lg">{{ formatCurrency(motorcycle.price) }}</span></td>
                        <td class="hidden md:table-cell"><StatusBadge :status="motorcycle.is_available ? 'available' : 'unavailable'" kind="product" /></td>
                        <td class="text-right">
                          <div class="flex items-center justify-end gap-2">
                            <button type="button" class="icon-button" title="Редактировать" @click="editMotorcycle(motorcycle)">
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            </button>
                            <button type="button" class="icon-button hover:text-red-400 hover:border-red-500/50" title="Удалить" @click="deleteMotorcycle(motorcycle.id)">
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                          </div>
                        </td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>
            </div>
            <div v-if="!visibleMotorcycles.length" class="empty-panel mt-4">Товаров по выбранным условиям не найдено.</div>
          </section>

          <section v-show="activeTab === 'orders'" class="space-y-4">
            <article v-for="order in orders" :key="order.id" class="admin-record">
              <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                  <div class="flex items-center gap-3 mb-2">
                    <p class="text-white font-display font-bold uppercase">Заказ #{{ order.id }}</p>
                    <StatusBadge :status="order.status" kind="order" />
                  </div>
                  <p class="text-gray-500 text-sm">{{ order.name || 'Гость' }} · {{ order.phone || 'Без телефона' }} · {{ new Date(order.created_at).toLocaleString('ru-RU') }}</p>
                </div>
                <div class="flex items-center gap-4">
                  <span class="text-xl font-bold text-primary font-display">{{ formatCurrency(order.total) }}</span>
                  <select class="field-dark min-w-44" :value="order.status" @change="updateOrderStatus(order.id, ($event.target as HTMLSelectElement).value as Order['status'])">
                    <option v-for="option in orderStatusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                  </select>
                </div>
              </div>
              <div class="mt-5 border-t border-white/5 pt-4 space-y-2">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                  <div class="bg-dark border border-white/5 p-4">
                    <p class="text-xs text-gray-600 font-bold uppercase tracking-wider mb-1">Оплата</p>
                    <p class="text-white text-sm font-bold">{{ paymentMethodLabel(order.payment_method) }}</p>
                    <p class="text-gray-500 text-xs mt-1">{{ paymentStatusLabel(order.payment_status) }}</p>
                  </div>
                  <div class="bg-dark border border-white/5 p-4">
                    <p class="text-xs text-gray-600 font-bold uppercase tracking-wider mb-1">Выдача</p>
                    <p class="text-white text-sm font-bold">{{ order.pickup_point?.name || 'Не выбран' }}</p>
                    <p class="text-gray-500 text-xs mt-1">{{ order.pickup_point?.address || 'Адрес не указан' }}</p>
                  </div>
                  <div class="bg-dark border border-white/5 p-4">
                    <p class="text-xs text-gray-600 font-bold uppercase tracking-wider mb-1">Готовность</p>
                    <p class="text-white text-sm font-bold">{{ order.pickup_ready_at ? new Date(order.pickup_ready_at).toLocaleString('ru-RU') : 'Не назначена' }}</p>
                    <p class="text-gray-500 text-xs mt-1">Статус меняется менеджером</p>
                  </div>
                  <div class="bg-dark border border-white/5 p-4">
                    <p class="text-xs text-gray-600 font-bold uppercase tracking-wider mb-1">Бронь</p>
                    <p class="text-white text-sm font-bold">{{ reservationLabel(order) }}</p>
                    <p class="text-gray-500 text-xs mt-1">{{ order.reservations?.length ?? 0 }} позиций</p>
                  </div>
                </div>
                <div v-for="item in order.items" :key="item.id" class="flex items-center justify-between text-sm">
                  <span class="text-gray-300">{{ item.name }} <span class="text-gray-600">× {{ item.quantity }}</span></span>
                  <span class="text-white font-bold font-display">{{ formatCurrency(item.price * item.quantity) }}</span>
                </div>
              </div>
            </article>
            <div v-if="!orders.length" class="empty-panel">Заказов пока нет.</div>
          </section>

          <section v-show="activeTab === 'sales'">
            <div class="flex flex-wrap gap-2 mb-6">
              <button v-for="option in salesStatusOptions" :key="option.value" class="filter-chip" :class="salesStatusFilter === option.value ? 'filter-chip-active' : ''" @click="salesStatusFilter = option.value">
                {{ option.label }}
              </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
              <article v-for="request in filteredSalesRequests" :key="request.id" class="admin-record">
                <div class="flex items-start justify-between gap-4 mb-4">
                  <div>
                    <p class="text-white font-display font-bold uppercase">Заявка #{{ request.id }} · {{ salesTypeLabels[request.type] }}</p>
                    <p class="text-gray-600 text-sm">{{ new Date(request.created_at).toLocaleString('ru-RU') }}</p>
                  </div>
                  <StatusBadge :status="request.status" kind="sales" />
                </div>
                <p class="text-gray-300 font-medium">{{ request.motorcycle ? `${request.motorcycle.brand} ${request.motorcycle.model}` : 'Подбор техники' }}</p>
                <p class="text-gray-500 text-sm mt-1">{{ request.name }} · {{ request.phone }} · {{ request.email || 'email не указан' }}</p>
                <p v-if="request.comment" class="text-gray-500 text-sm mt-3">{{ request.comment }}</p>
                <div class="flex gap-2 mt-5">
                  <select class="field-dark flex-1" :value="request.status" @change="updateSalesStatus(request.id, ($event.target as HTMLSelectElement).value as SalesRequest['status'])">
                    <option value="new">Новая</option>
                    <option value="in_progress">В работе</option>
                    <option value="approved">Согласована</option>
                    <option value="completed">Завершена</option>
                    <option value="cancelled">Отменена</option>
                  </select>
                  <button type="button" class="icon-button hover:text-red-400 hover:border-red-500/50" title="Удалить заявку" @click="deleteSalesRequest(request.id)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                  </button>
                </div>
              </article>
            </div>
            <div v-if="!filteredSalesRequests.length" class="empty-panel">Нет заявок с выбранным статусом.</div>
          </section>

          <section v-show="activeTab === 'service'">
            <div class="flex flex-wrap gap-2 mb-6">
              <button v-for="option in serviceStatusOptions" :key="option.value" class="filter-chip" :class="serviceStatusFilter === option.value ? 'filter-chip-active' : ''" @click="serviceStatusFilter = option.value">
                {{ option.label }}
              </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
              <article v-for="request in filteredServiceRequests" :key="request.id" class="admin-record">
                <div class="flex items-start justify-between gap-4 mb-4">
                  <div>
                    <p class="text-white font-display font-bold uppercase">Сервис #{{ request.id }} · {{ request.service_type }}</p>
                    <p class="text-gray-600 text-sm">{{ new Date(request.created_at).toLocaleString('ru-RU') }}</p>
                  </div>
                  <StatusBadge :status="request.status" kind="service" />
                </div>
                <p class="text-gray-300 font-medium">{{ request.motorcycle_model }}</p>
                <p class="text-gray-500 text-sm mt-1">{{ request.name }} · {{ request.phone }} · {{ request.email || 'email не указан' }}</p>
                <p class="text-gray-500 text-sm mt-2">Желаемая дата: {{ request.preferred_date ? new Date(request.preferred_date).toLocaleDateString('ru-RU') : 'не указана' }}</p>
                <p v-if="request.comment" class="text-gray-500 text-sm mt-3">{{ request.comment }}</p>
                <div class="flex gap-2 mt-5">
                  <select class="field-dark flex-1" :value="request.status" @change="updateServiceStatus(request.id, ($event.target as HTMLSelectElement).value as ServiceRequest['status'])">
                    <option value="new">Новая</option>
                    <option value="confirmed">Подтверждена</option>
                    <option value="in_service">В сервисе</option>
                    <option value="done">Готово</option>
                    <option value="cancelled">Отменена</option>
                  </select>
                  <button type="button" class="icon-button hover:text-red-400 hover:border-red-500/50" title="Удалить сервисную заявку" @click="deleteServiceRequest(request.id)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                  </button>
                </div>
              </article>
            </div>
            <div v-if="!filteredServiceRequests.length" class="empty-panel">Нет сервисных заявок с выбранным статусом.</div>
          </section>

          <section v-show="activeTab === 'users'" class="bg-dark-lighter border border-white/5 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="admin-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Пользователь</th>
                    <th class="hidden md:table-cell">Email</th>
                    <th class="hidden md:table-cell">Активность</th>
                    <th class="hidden md:table-cell">Роль</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="client in users" :key="client.id">
                    <td><span class="text-gray-600 text-sm font-display font-bold">#{{ client.id }}</span></td>
                    <td>
                      <div class="flex items-center gap-3">
                        <div class="w-9 h-9 flex items-center justify-center text-sm font-display font-bold" :class="client.is_admin ? 'bg-primary/20 text-primary border border-primary/30' : 'bg-white/5 text-gray-400 border border-white/10'">
                          {{ client.name.trim().charAt(0).toUpperCase() }}
                        </div>
                        <span class="text-white font-medium text-sm">{{ client.name }}</span>
                      </div>
                    </td>
                    <td class="hidden md:table-cell"><span class="text-gray-400 text-sm">{{ client.email }}</span></td>
                    <td class="hidden md:table-cell">
                      <span class="text-gray-500 text-sm">{{ client.orders?.length ?? 0 }} заказов · {{ client.sales_requests?.length ?? 0 }} заявок · {{ client.service_requests?.length ?? 0 }} сервис</span>
                    </td>
                    <td class="hidden md:table-cell"><StatusBadge :status="client.is_admin ? 'admin' : 'user'" kind="role" /></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <section v-show="activeTab === 'messages'" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <article v-for="message in messages" :key="message.id" class="admin-record">
              <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                  <p class="text-white font-display font-bold uppercase">Сообщение #{{ message.id }}</p>
                  <p class="text-gray-600 text-sm">{{ new Date(message.created_at).toLocaleString('ru-RU') }}</p>
                </div>
              </div>
              <p class="text-gray-300 font-medium">{{ message.name }}</p>
              <p class="text-gray-500 text-sm mt-1">{{ message.phone || 'телефон не указан' }} · {{ message.email || 'email не указан' }}</p>
              <p class="text-gray-400 text-sm mt-4 leading-relaxed">{{ message.message }}</p>
            </article>
            <div v-if="!messages.length" class="empty-panel lg:col-span-2">Сообщений пока нет.</div>
          </section>
        </div>
      </div>
    </div>
  </div>
</template>
