<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import api from '../api';
import StatusBadge from '../components/ui/StatusBadge.vue';
import { sessionState } from '../session';
import type { ContactMessage, Motorcycle, Order, SalesRequest, ServiceRequest, StatusHistory, User } from '../types';

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

type AdminTab = 'dashboard' | 'orders' | 'sales' | 'service' | 'products' | 'users' | 'messages';
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
const expandedOrders = ref<number[]>([]);

const motorcycles = ref<Motorcycle[]>([]);
const orders = ref<Order[]>([]);
const salesRequests = ref<SalesRequest[]>([]);
const serviceRequests = ref<ServiceRequest[]>([]);
const users = ref<AdminUser[]>([]);
const messages = ref<ContactMessage[]>([]);
const statusHistories = ref<StatusHistory[]>([]);
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
  stock_quantity: 1,
  reserved_quantity: 0,
  transmission: '',
  cooling: '',
  fuel_system: '',
  weight: null as number | null,
  tank_capacity: null as number | null,
});

const tabs: Array<{ id: AdminTab; label: string; hint: string }> = [
  { id: 'dashboard', label: 'Сводка', hint: 'Что требует внимания' },
  { id: 'orders', label: 'Заказы', hint: 'Оплата, бронь, выдача' },
  { id: 'sales', label: 'Покупка', hint: 'Заявки клиентов' },
  { id: 'service', label: 'Сервис', hint: 'Записи на обслуживание' },
  { id: 'products', label: 'Товары', hint: 'Склад и каталог' },
  { id: 'users', label: 'Клиенты', hint: 'Пользователи системы' },
  { id: 'messages', label: 'Сообщения', hint: 'Обратная связь' },
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
  availability: 'Наличие',
  preorder: 'Предзаказ',
  test_drive: 'Тест-драйв',
};

const productGroupModes: Array<{ value: ProductGroupMode; label: string }> = [
  { value: 'type', label: 'Тип' },
  { value: 'brand', label: 'Бренд' },
  { value: 'availability', label: 'Наличие' },
];

const roleOptions: Array<{ value: User['role']; label: string }> = [
  { value: 'client', label: 'Клиент' },
  { value: 'manager', label: 'Менеджер' },
  { value: 'admin', label: 'Администратор' },
];

const activeTabMeta = computed(() => tabs.find((tab) => tab.id === activeTab.value) ?? tabs[0]);
const urgentOrders = computed(() => orders.value.filter((order) => ['new', 'processing', 'approved'].includes(order.status)));
const readyOrders = computed(() => orders.value.filter((order) => order.status === 'ready_for_pickup'));
const newSalesRequests = computed(() => salesRequests.value.filter((request) => request.status === 'new'));
const newServiceRequests = computed(() => serviceRequests.value.filter((request) => request.status === 'new'));
const unavailableMotorcycles = computed(() => motorcycles.value.filter((moto) => !moto.is_available));
const canEditRoles = computed(() => sessionState.user?.role === 'admin');

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

const visibleMotorcycles = computed(() => {
  if (selectedProductGroup.value === 'all') {
    return filteredMotorcycles.value;
  }

  return filteredMotorcycles.value.filter((moto) => productGroupKey(moto) === selectedProductGroup.value);
});

const productGroupCards = computed(() => groupMotorcycles(filteredMotorcycles.value));
const groupedMotorcycles = computed(() => groupMotorcycles(visibleMotorcycles.value));

const filteredSalesRequests = computed(() =>
  salesRequests.value.filter((request) => salesStatusFilter.value === 'all' || request.status === salesStatusFilter.value),
);

const filteredServiceRequests = computed(() =>
  serviceRequests.value.filter((request) => serviceStatusFilter.value === 'all' || request.status === serviceStatusFilter.value),
);

function tabCount(tab: AdminTab): number {
  const counts: Record<AdminTab, number> = {
    dashboard: urgentOrders.value.length + newSalesRequests.value.length + newServiceRequests.value.length,
    orders: orders.value.length,
    sales: salesRequests.value.length,
    service: serviceRequests.value.length,
    products: motorcycles.value.length,
    users: users.value.length,
    messages: messages.value.length,
  };

  return counts[tab];
}

function formatCurrency(value: number): string {
  return `${Number(value || 0).toLocaleString('ru-RU')} ₽`;
}

function formatDate(value?: string | null): string {
  return value ? new Date(value).toLocaleString('ru-RU') : 'Не назначено';
}

function entityLabel(history: StatusHistory): string {
  if (history.entity_type.includes('Order')) {
    return `Заказ #${history.entity_id}`;
  }

  if (history.entity_type.includes('SalesRequest')) {
    return `Заявка #${history.entity_id}`;
  }

  if (history.entity_type.includes('ServiceRequest')) {
    return `Сервис #${history.entity_id}`;
  }

  return `Запись #${history.entity_id}`;
}

function askStatusComment(): string | null {
  const comment = window.prompt('Комментарий менеджера к изменению статуса (можно оставить пустым):');

  return comment?.trim() || null;
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

  return active ? 'Активна' : 'Нет активной брони';
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

function toggleOrderDetails(id: number) {
  expandedOrders.value = expandedOrders.value.includes(id)
    ? expandedOrders.value.filter((orderId) => orderId !== id)
    : [...expandedOrders.value, id];
}

function isOrderExpanded(id: number): boolean {
  return expandedOrders.value.includes(id);
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
  form.stock_quantity = 1;
  form.reserved_quantity = 0;
  form.transmission = '';
  form.cooling = '';
  form.fuel_system = '';
  form.weight = null;
  form.tank_capacity = null;
}

function openCreateMotorcycleForm() {
  resetForm();
  activeTab.value = 'products';
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
  form.stock_quantity = moto.stock_quantity ?? 1;
  form.reserved_quantity = moto.reserved_quantity ?? 0;
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
    statusHistories.value = data.status_histories ?? [];
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
    const { data } = await api.patch(`/admin/orders/${orderId}/status`, {
      status,
      status_comment: askStatusComment(),
    });
    successText.value = data.message;
    await loadDashboard();
  } catch {
    errorText.value = 'Не удалось обновить статус заказа.';
  }
}

async function updateSalesStatus(requestId: number, status: SalesRequest['status']) {
  try {
    const { data } = await api.patch(`/admin/sales-requests/${requestId}/status`, {
      status,
      status_comment: askStatusComment(),
    });
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
    const { data } = await api.patch(`/admin/service-requests/${requestId}/status`, {
      status,
      status_comment: askStatusComment(),
    });
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

async function updateUserRole(userId: number, role: User['role']) {
  try {
    const { data } = await api.patch(`/admin/users/${userId}/role`, { role });
    successText.value = data.message;
    await loadDashboard();
  } catch (error: any) {
    errorText.value = error?.response?.data?.message ?? 'Не удалось обновить роль пользователя.';
  }
}

onMounted(loadDashboard);
</script>

<template>
  <div class="min-h-screen bg-dark">
    <div class="border-b border-white/5 bg-dark-lighter/50">
      <div class="max-w-[1500px] mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
          <div>
            <p class="text-primary text-xs font-bold uppercase tracking-[0.25em] mb-2">AVANTIS Manager</p>
            <h1 class="text-3xl md:text-4xl font-display font-bold text-white uppercase italic">Панель управления</h1>
          </div>

          <div class="grid grid-cols-3 gap-2 w-full lg:w-auto">
            <button type="button" class="bg-dark border border-white/5 px-4 py-3 text-left" @click="activeTab = 'orders'">
              <span class="block text-[10px] text-gray-600 font-bold uppercase tracking-wider">Заказы в работе</span>
              <span class="text-2xl text-white font-display font-bold">{{ urgentOrders.length }}</span>
            </button>
            <button type="button" class="bg-dark border border-white/5 px-4 py-3 text-left" @click="activeTab = 'sales'">
              <span class="block text-[10px] text-gray-600 font-bold uppercase tracking-wider">Новые заявки</span>
              <span class="text-2xl text-primary font-display font-bold">{{ newSalesRequests.length }}</span>
            </button>
            <button type="button" class="bg-dark border border-white/5 px-4 py-3 text-left" @click="activeTab = 'service'">
              <span class="block text-[10px] text-gray-600 font-bold uppercase tracking-wider">Новый сервис</span>
              <span class="text-2xl text-green-300 font-display font-bold">{{ newServiceRequests.length }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <main class="max-w-[1500px] mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <p v-if="errorText" class="mb-4 p-4 bg-red-500/10 border border-red-500/30 text-red-400 text-sm">{{ errorText }}</p>
      <p v-if="successText" class="mb-4 p-4 bg-green-500/10 border border-green-500/30 text-green-400 text-sm">{{ successText }}</p>
      <p v-if="loading" class="text-gray-500 py-10">Загрузка панели...</p>

      <div v-else class="grid grid-cols-1 xl:grid-cols-[280px_1fr] gap-6">
        <aside class="space-y-4">
          <section class="bg-dark-lighter border border-white/5 p-4 xl:sticky xl:top-6">
            <div class="flex items-center justify-between gap-3 mb-4">
              <p class="text-xs text-gray-600 font-bold uppercase tracking-wider">Разделы</p>
              <button type="button" class="text-xs text-primary font-bold uppercase tracking-wider" @click="loadDashboard">Обновить</button>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-1 gap-2">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                class="flex items-center justify-between gap-3 border px-4 py-3 text-left transition-colors"
                :class="activeTab === tab.id ? 'border-primary/60 bg-primary/10 text-primary' : 'border-white/5 bg-dark text-gray-400 hover:text-white hover:border-white/20'"
                @click="activeTab = tab.id"
              >
                <span>
                  <span class="block text-sm font-bold uppercase tracking-wider">{{ tab.label }}</span>
                  <span class="block text-xs text-gray-600 mt-0.5">{{ tab.hint }}</span>
                </span>
                <span class="text-xs text-gray-500">{{ tabCount(tab.id) }}</span>
              </button>
            </div>

            <button type="button" class="btn btn-primary w-full mt-4" @click="openCreateMotorcycleForm">
              <span>Добавить товар</span>
            </button>
          </section>

          <section class="bg-dark-lighter border border-white/5 p-4">
            <p class="text-xs text-gray-600 font-bold uppercase tracking-wider mb-3">Склад</p>
            <div class="space-y-3 text-sm">
              <div class="flex items-center justify-between gap-3">
                <span class="text-gray-500">Всего товаров</span>
                <span class="text-white font-bold">{{ motorcycles.length }}</span>
              </div>
              <div class="flex items-center justify-between gap-3">
                <span class="text-gray-500">В наличии</span>
                <span class="text-green-300 font-bold">{{ inventorySummary.availableCount }}</span>
              </div>
              <div class="flex items-center justify-between gap-3">
                <span class="text-gray-500">Нет в наличии</span>
                <span class="text-red-300 font-bold">{{ unavailableMotorcycles.length }}</span>
              </div>
              <div class="flex items-center justify-between gap-3">
                <span class="text-gray-500">Сумма продаж</span>
                <span class="text-primary font-bold">{{ formatCurrency(stats.totalRevenue) }}</span>
              </div>
            </div>
          </section>
        </aside>

        <div class="space-y-6">
          <section class="bg-dark-lighter border border-white/5 p-5">
            <p class="text-primary text-xs font-bold uppercase tracking-[0.25em] mb-2">Текущий раздел</p>
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
              <div>
                <h2 class="text-3xl font-display font-bold text-white uppercase italic">{{ activeTabMeta.label }}</h2>
                <p class="text-gray-500 mt-1">{{ activeTabMeta.hint }}</p>
              </div>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                <div class="bg-dark border border-white/5 p-3">
                  <p class="text-gray-600 text-xs uppercase font-bold">Заказы</p>
                  <p class="text-white font-display font-bold text-xl">{{ stats.ordersCount }}</p>
                </div>
                <div class="bg-dark border border-white/5 p-3">
                  <p class="text-gray-600 text-xs uppercase font-bold">Заявки</p>
                  <p class="text-primary font-display font-bold text-xl">{{ stats.salesRequestsCount }}</p>
                </div>
                <div class="bg-dark border border-white/5 p-3">
                  <p class="text-gray-600 text-xs uppercase font-bold">Сервис</p>
                  <p class="text-green-300 font-display font-bold text-xl">{{ stats.serviceRequestsCount }}</p>
                </div>
                <div class="bg-dark border border-white/5 p-3">
                  <p class="text-gray-600 text-xs uppercase font-bold">Клиенты</p>
                  <p class="text-white font-display font-bold text-xl">{{ stats.usersCount }}</p>
                </div>
              </div>
            </div>
          </section>

          <section v-show="activeTab === 'dashboard'" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
              <button type="button" class="admin-record text-left" @click="activeTab = 'orders'">
                <p class="text-gray-600 text-xs font-bold uppercase tracking-wider mb-2">Заказы требуют действий</p>
                <p class="text-4xl text-white font-display font-bold">{{ urgentOrders.length }}</p>
                <p class="text-gray-500 text-sm mt-2">{{ readyOrders.length }} готовы к выдаче</p>
              </button>
              <button type="button" class="admin-record text-left" @click="activeTab = 'sales'">
                <p class="text-gray-600 text-xs font-bold uppercase tracking-wider mb-2">Новые заявки на покупку</p>
                <p class="text-4xl text-primary font-display font-bold">{{ newSalesRequests.length }}</p>
                <p class="text-gray-500 text-sm mt-2">нужно связаться с клиентом</p>
              </button>
              <button type="button" class="admin-record text-left" @click="activeTab = 'service'">
                <p class="text-gray-600 text-xs font-bold uppercase tracking-wider mb-2">Новые записи на сервис</p>
                <p class="text-4xl text-green-300 font-display font-bold">{{ newServiceRequests.length }}</p>
                <p class="text-gray-500 text-sm mt-2">нужно подтвердить дату</p>
              </button>
            </div>

            <div class="grid grid-cols-1 2xl:grid-cols-2 gap-6">
              <section class="bg-dark-lighter border border-white/5 overflow-hidden">
                <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between">
                  <h3 class="text-xl font-display font-bold text-white uppercase">Очередь заказов</h3>
                  <button type="button" class="text-xs text-primary font-bold uppercase tracking-wider" @click="activeTab = 'orders'">Открыть</button>
                </div>
                <div class="divide-y divide-white/5">
                  <article v-for="order in urgentOrders.slice(0, 5)" :key="order.id" class="p-4 flex items-center justify-between gap-4">
                    <div>
                      <p class="text-white font-bold">#{{ order.id }} · {{ order.name || 'Гость' }}</p>
                      <p class="text-gray-500 text-sm">{{ order.phone || 'без телефона' }} · {{ formatCurrency(order.total) }}</p>
                    </div>
                    <StatusBadge :status="order.status" kind="order" />
                  </article>
                  <div v-if="!urgentOrders.length" class="empty-panel">Активных заказов нет.</div>
                </div>
              </section>

              <section class="bg-dark-lighter border border-white/5 overflow-hidden">
                <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between">
                  <h3 class="text-xl font-display font-bold text-white uppercase">Новые обращения</h3>
                  <button type="button" class="text-xs text-primary font-bold uppercase tracking-wider" @click="activeTab = 'sales'">К заявкам</button>
                </div>
                <div class="divide-y divide-white/5">
                  <article v-for="request in newSalesRequests.slice(0, 5)" :key="request.id" class="p-4 flex items-start justify-between gap-4">
                    <div>
                      <p class="text-white font-bold">#{{ request.id }} · {{ salesTypeLabels[request.type] }}</p>
                      <p class="text-gray-500 text-sm">{{ request.name }} · {{ request.phone }}</p>
                    </div>
                    <StatusBadge :status="request.status" kind="sales" />
                  </article>
                  <div v-if="!newSalesRequests.length" class="empty-panel">Новых заявок на покупку нет.</div>
                </div>
              </section>
            </div>

            <section class="bg-dark-lighter border border-white/5 overflow-hidden">
              <div class="px-5 py-4 border-b border-white/5">
                <h3 class="text-xl font-display font-bold text-white uppercase">Журнал статусов</h3>
                <p class="text-gray-500 text-sm mt-1">Кто и почему менял статусы заказов, заявок и сервиса.</p>
              </div>
              <div class="divide-y divide-white/5">
                <article v-for="history in statusHistories.slice(0, 8)" :key="history.id" class="p-4 grid grid-cols-1 lg:grid-cols-[1fr_auto] gap-3">
                  <div>
                    <p class="text-white font-bold">{{ entityLabel(history) }}</p>
                    <p class="text-gray-500 text-sm">
                      {{ history.old_status || '—' }} → {{ history.new_status }}
                      <span v-if="history.user"> · {{ history.user.name }}</span>
                    </p>
                    <p v-if="history.comment" class="text-gray-400 text-sm mt-2">{{ history.comment }}</p>
                  </div>
                  <p class="text-gray-600 text-xs lg:text-right">{{ formatDate(history.created_at) }}</p>
                </article>
                <div v-if="!statusHistories.length" class="empty-panel">Истории изменений пока нет.</div>
              </div>
            </section>
          </section>

          <section v-show="activeTab === 'orders'" class="space-y-3">
            <article v-for="order in orders" :key="order.id" class="bg-dark-lighter border border-white/5 overflow-hidden">
              <div class="p-4 md:p-5 grid grid-cols-1 lg:grid-cols-[1fr_auto] gap-4">
                <button type="button" class="text-left" @click="toggleOrderDetails(order.id)">
                  <div class="flex flex-wrap items-center gap-3 mb-2">
                    <p class="text-white font-display font-bold uppercase">Заказ #{{ order.id }}</p>
                    <StatusBadge :status="order.status" kind="order" />
                  </div>
                  <p class="text-gray-500 text-sm">{{ order.name || 'Гость' }} · {{ order.phone || 'без телефона' }} · {{ formatDate(order.created_at) }}</p>
                </button>

                <div class="flex flex-col sm:flex-row sm:items-center gap-3 lg:justify-end">
                  <span class="text-primary font-display font-bold text-xl">{{ formatCurrency(order.total) }}</span>
                  <select class="field-dark sm:w-56" :value="order.status" @change="updateOrderStatus(order.id, ($event.target as HTMLSelectElement).value as Order['status'])">
                    <option v-for="option in orderStatusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                  </select>
                  <button type="button" class="filter-chip" @click="toggleOrderDetails(order.id)">{{ isOrderExpanded(order.id) ? 'Скрыть' : 'Детали' }}</button>
                </div>
              </div>

              <div v-if="isOrderExpanded(order.id)" class="border-t border-white/5 p-4 md:p-5">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-5">
                  <div class="bg-dark border border-white/5 p-4">
                    <p class="text-xs text-gray-600 font-bold uppercase mb-1">Оплата</p>
                    <p class="text-white font-bold">{{ paymentMethodLabel(order.payment_method) }}</p>
                    <p class="text-gray-500 text-sm">{{ paymentStatusLabel(order.payments?.[0]?.status ?? order.payment_status) }}</p>
                    <p v-if="order.payments?.[0]?.transaction_id" class="text-gray-600 text-xs mt-1">{{ order.payments[0].transaction_id }}</p>
                  </div>
                  <div class="bg-dark border border-white/5 p-4">
                    <p class="text-xs text-gray-600 font-bold uppercase mb-1">Выдача</p>
                    <p class="text-white font-bold">{{ order.pickup_point?.name || 'Не выбран' }}</p>
                    <p class="text-gray-500 text-sm">{{ order.pickup_point?.address || 'Адрес не указан' }}</p>
                  </div>
                  <div class="bg-dark border border-white/5 p-4">
                    <p class="text-xs text-gray-600 font-bold uppercase mb-1">Готовность</p>
                    <p class="text-white font-bold">{{ formatDate(order.pickup_ready_at) }}</p>
                    <p class="text-gray-500 text-sm">Клиент увидит уведомление</p>
                  </div>
                  <div class="bg-dark border border-white/5 p-4">
                    <p class="text-xs text-gray-600 font-bold uppercase mb-1">Бронь</p>
                    <p class="text-white font-bold">{{ reservationLabel(order) }}</p>
                    <p class="text-gray-500 text-sm">{{ order.reservations?.length ?? 0 }} позиций</p>
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
            <div v-if="!orders.length" class="empty-panel">Заказов пока нет.</div>
          </section>

          <section v-show="activeTab === 'sales'" class="space-y-4">
            <div class="flex gap-2 overflow-x-auto">
              <button v-for="option in salesStatusOptions" :key="option.value" class="filter-chip" :class="salesStatusFilter === option.value ? 'filter-chip-active' : ''" @click="salesStatusFilter = option.value">
                {{ option.label }}
              </button>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
              <article v-for="request in filteredSalesRequests" :key="request.id" class="admin-record">
                <div class="flex items-start justify-between gap-4 mb-4">
                  <div>
                    <p class="text-white font-display font-bold uppercase">Заявка #{{ request.id }} · {{ salesTypeLabels[request.type] }}</p>
                    <p class="text-gray-600 text-sm">{{ formatDate(request.created_at) }}</p>
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

          <section v-show="activeTab === 'service'" class="space-y-4">
            <div class="flex gap-2 overflow-x-auto">
              <button v-for="option in serviceStatusOptions" :key="option.value" class="filter-chip" :class="serviceStatusFilter === option.value ? 'filter-chip-active' : ''" @click="serviceStatusFilter = option.value">
                {{ option.label }}
              </button>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
              <article v-for="request in filteredServiceRequests" :key="request.id" class="admin-record">
                <div class="flex items-start justify-between gap-4 mb-4">
                  <div>
                    <p class="text-white font-display font-bold uppercase">Сервис #{{ request.id }} · {{ request.service_type }}</p>
                    <p class="text-gray-600 text-sm">{{ formatDate(request.created_at) }}</p>
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

          <section v-show="activeTab === 'products'" class="space-y-5">
            <div class="bg-dark-lighter border border-white/5 p-5">
              <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-5">
                <div>
                  <h3 class="text-2xl font-display font-bold text-white uppercase italic">Склад и каталог</h3>
                  <p class="text-gray-500 text-sm">Поиск, группировка и редактирование товаров.</p>
                </div>
                <button type="button" class="btn btn-primary" @click="openCreateMotorcycleForm"><span>Добавить товар</span></button>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="bg-dark border border-white/5 p-4">
                  <p class="text-xs text-gray-600 font-bold uppercase tracking-wider">Стоимость склада</p>
                  <p class="text-xl font-display font-bold text-white">{{ formatCurrency(inventorySummary.totalValue) }}</p>
                </div>
                <div class="bg-dark border border-white/5 p-4">
                  <p class="text-xs text-gray-600 font-bold uppercase tracking-wider">В наличии</p>
                  <p class="text-xl font-display font-bold text-green-300">{{ inventorySummary.availableCount }} / {{ motorcycles.length }}</p>
                </div>
                <div class="bg-dark border border-white/5 p-4">
                  <p class="text-xs text-gray-600 font-bold uppercase tracking-wider">Средняя цена</p>
                  <p class="text-xl font-display font-bold text-primary">{{ formatCurrency(inventorySummary.averagePrice) }}</p>
                </div>
              </div>
            </div>

            <form v-if="showProductForm" class="bg-dark-lighter border border-white/5" @submit.prevent="saveMotorcycle">
              <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between gap-3">
                <h3 class="text-lg font-display font-bold text-white uppercase">{{ editingId ? 'Редактирование товара' : 'Новый товар' }}</h3>
                <button type="button" class="filter-chip" @click="resetForm">Закрыть</button>
              </div>

              <div class="p-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <input v-model="form.brand" class="field-dark" placeholder="Бренд" required />
                <input v-model="form.model" class="field-dark" placeholder="Модель" required />
                <input v-model="form.type" class="field-dark" placeholder="Тип" required />
                <input v-model.number="form.year" type="number" class="field-dark" placeholder="Год" required />
                <input v-model.number="form.engine_capacity" type="number" class="field-dark" placeholder="Объём" required />
                <input v-model.number="form.power" type="number" class="field-dark" placeholder="Мощность" required />
                <input v-model.number="form.price" type="number" class="field-dark" placeholder="Цена" required />
                <input v-model.number="form.stock_quantity" type="number" min="0" class="field-dark" placeholder="Кол-во на складе" />
                <input v-model.number="form.reserved_quantity" type="number" min="0" class="field-dark" placeholder="В резерве" />
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
                <textarea v-model="form.description" class="field-dark md:col-span-2 xl:col-span-2" rows="3" placeholder="Описание" required />
              </div>

              <div class="px-5 pb-5 flex flex-wrap items-center gap-3">
                <button type="submit" class="btn btn-primary" :disabled="saving"><span>{{ saving ? 'Сохранение...' : 'Сохранить товар' }}</span></button>
              </div>
            </form>

            <div class="bg-dark-lighter border border-white/5 p-5">
              <div class="grid grid-cols-1 xl:grid-cols-[1fr_auto] gap-4 mb-4">
                <input v-model="searchQuery" type="text" placeholder="Поиск по названию, бренду или типу..." class="field-dark" />
                <div class="flex gap-2 overflow-x-auto">
                  <button class="filter-chip" :class="filterStatus === 'all' ? 'filter-chip-active' : ''" @click="filterStatus = 'all'">Все</button>
                  <button class="filter-chip" :class="filterStatus === 'available' ? 'filter-chip-active' : ''" @click="filterStatus = 'available'">В наличии</button>
                  <button class="filter-chip" :class="filterStatus === 'unavailable' ? 'filter-chip-active' : ''" @click="filterStatus = 'unavailable'">Нет</button>
                </div>
              </div>

              <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
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
                <div class="flex gap-2 overflow-x-auto">
                  <button type="button" class="filter-chip" :class="selectedProductGroup === 'all' ? 'filter-chip-active' : ''" @click="selectedProductGroup = 'all'">Все группы</button>
                  <button
                    v-for="group in productGroupCards"
                    :key="group.key"
                    type="button"
                    class="filter-chip"
                    :class="selectedProductGroup === group.key ? 'filter-chip-active' : ''"
                    @click="selectedProductGroup = group.key"
                  >
                    {{ group.label }} · {{ group.count }}
                  </button>
                </div>
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
                        <td class="hidden md:table-cell">
                          <span class="px-2 py-1 bg-white/5 text-gray-400 text-xs font-bold uppercase">{{ motorcycle.type }}</span>
                          <p class="text-gray-600 text-xs mt-2">Склад: {{ motorcycle.stock_quantity ?? 0 }} · Резерв: {{ motorcycle.reserved_quantity ?? 0 }}</p>
                        </td>
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
            <div v-if="!visibleMotorcycles.length" class="empty-panel">Товаров по выбранным условиям не найдено.</div>
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
                        <div class="w-9 h-9 flex items-center justify-center text-sm font-display font-bold" :class="client.can_manage ? 'bg-primary/20 text-primary border border-primary/30' : 'bg-white/5 text-gray-400 border border-white/10'">
                          {{ client.name.trim().charAt(0).toUpperCase() }}
                        </div>
                        <span class="text-white font-medium text-sm">{{ client.name }}</span>
                      </div>
                    </td>
                    <td class="hidden md:table-cell"><span class="text-gray-400 text-sm">{{ client.email }}</span></td>
                    <td class="hidden md:table-cell">
                      <span class="text-gray-500 text-sm">{{ client.orders?.length ?? 0 }} заказов · {{ client.sales_requests?.length ?? 0 }} заявок · {{ client.service_requests?.length ?? 0 }} сервис</span>
                    </td>
                    <td class="hidden md:table-cell">
                      <select
                        v-if="canEditRoles"
                        class="field-dark min-w-44"
                        :value="client.role"
                        :disabled="client.id === sessionState.user?.id"
                        @change="updateUserRole(client.id, ($event.target as HTMLSelectElement).value as User['role'])"
                      >
                        <option v-for="option in roleOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                      </select>
                      <StatusBadge v-else :status="client.role" kind="role" />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <section v-show="activeTab === 'messages'" class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <article v-for="message in messages" :key="message.id" class="admin-record">
              <p class="text-white font-display font-bold uppercase">Сообщение #{{ message.id }}</p>
              <p class="text-gray-600 text-sm mt-1">{{ formatDate(message.created_at) }}</p>
              <p class="text-gray-300 font-medium mt-4">{{ message.name }}</p>
              <p class="text-gray-500 text-sm mt-1">{{ message.phone || 'телефон не указан' }} · {{ message.email || 'email не указан' }}</p>
              <p class="text-gray-400 text-sm mt-4 leading-relaxed">{{ message.message }}</p>
            </article>
            <div v-if="!messages.length" class="empty-panel xl:col-span-2">Сообщений пока нет.</div>
          </section>
        </div>
      </div>
    </main>
  </div>
</template>
