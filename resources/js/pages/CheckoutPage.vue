<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import api from '../api';
import AlertMessage from '../components/ui/AlertMessage.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import PageHero from '../components/ui/PageHero.vue';
import { loadSession, sessionState } from '../session';
import type { CartPayload, PickupPoint } from '../types';

const loading = ref(true);
const submitting = ref(false);
const errorText = ref('');
const successText = ref('');
const completedOrderId = ref<number | null>(null);
const cart = ref<CartPayload>({ items: [], total: 0, count: 0 });
const pickupPoints = ref<PickupPoint[]>([]);

const form = reactive({
  name: '',
  phone: '',
  email: '',
  address: '',
  comment: '',
  payment_method: 'cash_pickup',
  pickup_point_id: '',
});

const paymentMethods = [
  { value: 'cash_pickup', label: 'Наличными при получении', note: 'Оплата в кассе мотосалона или пункта выдачи.' },
  { value: 'card_pickup', label: 'Картой при получении', note: 'Банковская карта при выдаче техники.' },
  { value: 'online_mock', label: 'Онлайн-оплата', note: 'Демо-оплата для дипломного проекта.' },
  { value: 'credit_request', label: 'Заявка на рассрочку', note: 'Менеджер свяжется для согласования условий.' },
];

async function loadCart() {
  loading.value = true;
  errorText.value = '';

  try {
    const { data } = await api.get<CartPayload>('/cart');
    cart.value = data;
  } catch {
    errorText.value = 'Не удалось получить данные корзины.';
  } finally {
    loading.value = false;
  }
}

async function loadPickupPoints() {
  const { data } = await api.get('/pickup-points');
  pickupPoints.value = data.pickup_points ?? [];

  if (!form.pickup_point_id && pickupPoints.value.length) {
    form.pickup_point_id = String(pickupPoints.value[0].id);
  }
}

async function submitOrder() {
  errorText.value = '';
  successText.value = '';
  submitting.value = true;

  try {
    const { data } = await api.post('/checkout', {
      ...form,
      pickup_point_id: Number(form.pickup_point_id),
    });
    successText.value = data.message ?? 'Заказ оформлен.';
    completedOrderId.value = data.order_id ?? null;
    await loadSession();
    cart.value = { items: [], total: 0, count: 0 };
  } catch (error: any) {
    errorText.value = error?.response?.data?.message ?? 'Не удалось оформить заказ.';
  } finally {
    submitting.value = false;
  }
}

onMounted(async () => {
  if (!sessionState.initialized) {
    await loadSession();
  }

  if (sessionState.user) {
    form.name = sessionState.user.name ?? '';
    form.email = sessionState.user.email ?? '';
  }

  await Promise.all([loadCart(), loadPickupPoints()]);
});
</script>

<template>
  <div>
    <PageHero center>
      <h1 class="text-5xl md:text-7xl font-bold font-display italic text-white leading-none mb-4 tracking-normal">
        <span class="text-gradient">ОФОРМЛЕНИЕ</span> <span class="text-transparent text-stroke">ЗАКАЗА</span>
      </h1>
    </PageHero>

    <div class="bg-dark min-h-screen pb-24 relative">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <AlertMessage v-if="errorText">{{ errorText }}</AlertMessage>
        <AlertMessage v-if="successText" tone="success">{{ successText }}</AlertMessage>

        <p v-if="loading" class="text-gray-500 py-8">Загрузка...</p>

        <section v-else-if="completedOrderId" class="max-w-4xl mx-auto bg-dark-lighter border border-primary/30 p-8 md:p-10 text-center relative overflow-hidden">
          <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-primary via-orange-400 to-transparent" />
          <p class="text-primary text-xs font-bold uppercase tracking-[0.25em] mb-4">Заказ принят системой</p>
          <h2 class="text-4xl md:text-6xl font-display font-bold italic text-white uppercase tracking-normal mb-5">
            Заказ #{{ completedOrderId }} оформлен
          </h2>
          <p class="text-gray-400 text-lg leading-relaxed max-w-2xl mx-auto">
            Менеджер AVANTIS проверит наличие, закрепит бронь и свяжется с вами по указанному телефону. Если вы вошли в аккаунт, статус заказа появится в личном кабинете.
          </p>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-8 text-left">
            <div class="bg-dark border border-white/5 p-4">
              <p class="text-primary font-display font-bold text-2xl mb-2">01</p>
              <p class="text-white font-bold uppercase">Проверка заказа</p>
              <p class="text-gray-600 text-sm mt-1">Менеджер уточнит детали и оплату.</p>
            </div>
            <div class="bg-dark border border-white/5 p-4">
              <p class="text-primary font-display font-bold text-2xl mb-2">02</p>
              <p class="text-white font-bold uppercase">Бронь техники</p>
              <p class="text-gray-600 text-sm mt-1">Товар закрепляется до выдачи.</p>
            </div>
            <div class="bg-dark border border-white/5 p-4">
              <p class="text-primary font-display font-bold text-2xl mb-2">03</p>
              <p class="text-white font-bold uppercase">Выдача</p>
              <p class="text-gray-600 text-sm mt-1">Вам сообщат дату и пункт получения.</p>
            </div>
          </div>
          <div class="flex flex-col sm:flex-row justify-center gap-4 mt-8">
            <RouterLink v-if="sessionState.user" to="/profile" class="btn btn-primary"><span>Открыть личный кабинет</span></RouterLink>
            <RouterLink v-else to="/login" class="btn btn-primary"><span>Войти для отслеживания</span></RouterLink>
            <RouterLink to="/catalog" class="btn btn-outline"><span>Вернуться в каталог</span></RouterLink>
          </div>
        </section>

        <EmptyState v-else-if="!cart.items.length" title="Корзина пуста" action-to="/catalog" action-label="Перейти в каталог" />

        <form v-else class="lg:grid lg:grid-cols-12 lg:gap-12" @submit.prevent="submitOrder">
          <div class="lg:col-span-7">
            <div class="bg-dark-lighter border border-white/5 p-8 relative overflow-hidden mb-8">
              <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary to-transparent" />

              <h2 class="text-2xl font-bold font-display text-white mb-8 uppercase italic">Данные покупателя</h2>

              <div class="space-y-6">
                <div>
                  <label for="name" class="block text-sm font-medium text-gray-500 mb-2 uppercase tracking-wide">ФИО *</label>
                  <input id="name" v-model="form.name" type="text" required class="w-full bg-dark border border-white/10 text-white px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors placeholder-gray-600" placeholder="Иванов Иван Иванович" />
                </div>

                <div>
                  <label for="phone" class="block text-sm font-medium text-gray-500 mb-2 uppercase tracking-wide">Телефон *</label>
                  <input id="phone" v-model="form.phone" type="text" required class="w-full bg-dark border border-white/10 text-white px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors placeholder-gray-600" placeholder="+7 (999) 000-00-00" />
                </div>

                <div>
                  <label for="email" class="block text-sm font-medium text-gray-500 mb-2 uppercase tracking-wide">Email</label>
                  <input id="email" v-model="form.email" type="email" class="w-full bg-dark border border-white/10 text-white px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors placeholder-gray-600" placeholder="your@email.com" />
                </div>

                <div>
                  <label for="address" class="block text-sm font-medium text-gray-500 mb-2 uppercase tracking-wide">Адрес доставки</label>
                  <input id="address" v-model="form.address" type="text" class="w-full bg-dark border border-white/10 text-white px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors placeholder-gray-600" placeholder="г. Москва, ул. Примерная, д. 1" />
                </div>

                <div>
                  <label for="comment" class="block text-sm font-medium text-gray-500 mb-2 uppercase tracking-wide">Комментарий</label>
                  <textarea id="comment" v-model="form.comment" rows="3" class="w-full bg-dark border border-white/10 text-white px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors placeholder-gray-600" placeholder="Пожелания к заказу" />
                </div>
              </div>
            </div>

            <div class="bg-dark-lighter border border-white/5 p-8 relative overflow-hidden mb-8">
              <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-orange-500 to-transparent" />

              <h2 class="text-2xl font-bold font-display text-white mb-8 uppercase italic">Оплата и выдача</h2>

              <div class="space-y-6">
                <div>
                  <label for="payment_method" class="block text-sm font-medium text-gray-500 mb-2 uppercase tracking-wide">Способ оплаты *</label>
                  <select id="payment_method" v-model="form.payment_method" required class="w-full bg-dark border border-white/10 text-white px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                    <option v-for="method in paymentMethods" :key="method.value" :value="method.value">{{ method.label }}</option>
                  </select>
                  <p class="text-xs text-gray-600 mt-2">{{ paymentMethods.find((method) => method.value === form.payment_method)?.note }}</p>
                </div>

                <div>
                  <label for="pickup_point_id" class="block text-sm font-medium text-gray-500 mb-2 uppercase tracking-wide">Где забрать технику *</label>
                  <select id="pickup_point_id" v-model="form.pickup_point_id" required class="w-full bg-dark border border-white/10 text-white px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                    <option value="" disabled>Выберите пункт выдачи</option>
                    <option v-for="point in pickupPoints" :key="point.id" :value="String(point.id)">{{ point.name }}</option>
                  </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <button
                    v-for="point in pickupPoints"
                    :key="point.id"
                    type="button"
                    class="text-left bg-dark border p-4 transition-all hover:border-primary/40"
                    :class="form.pickup_point_id === String(point.id) ? 'border-primary/60 shadow-lg shadow-primary/10' : 'border-white/10'"
                    @click="form.pickup_point_id = String(point.id)"
                  >
                    <span class="block text-white font-display font-bold uppercase text-sm mb-2">{{ point.name }}</span>
                    <span class="block text-gray-500 text-sm leading-relaxed">{{ point.address }}</span>
                    <span class="block text-gray-600 text-xs mt-2">{{ point.work_hours || 'График уточнит менеджер' }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="lg:col-span-5">
            <div class="bg-dark-lighter border border-white/5 p-6 sticky top-24">
              <h2 class="text-2xl font-bold font-display text-white mb-6 uppercase italic">Ваш заказ</h2>

              <div class="space-y-4 mb-6">
                <div v-for="item in cart.items" :key="item.id" class="flex items-center gap-4 pb-4 border-b border-white/5">
                  <div class="flex-shrink-0 w-16 h-16 bg-dark rounded overflow-hidden border border-white/10">
                    <img :src="item.image" :alt="item.name" class="w-full h-full object-cover" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-bold text-white truncate">{{ item.name }}</h4>
                    <p class="text-xs text-gray-500 mt-1">Кол-во: {{ item.quantity }}</p>
                  </div>
                  <div class="text-right">
                    <span class="text-primary font-bold font-display text-lg">{{ item.line_total.toLocaleString('ru-RU') }} ₽</span>
                  </div>
                </div>
              </div>

              <div class="flex justify-between items-center pt-4 border-t border-white/10 mb-8">
                <span class="text-lg font-bold text-gray-400 uppercase tracking-wider">Итого:</span>
                <span class="text-3xl font-bold text-primary font-display">{{ cart.total.toLocaleString('ru-RU') }} ₽</span>
              </div>

              <button type="submit" class="btn btn-primary w-full text-xl shadow-lg shadow-primary/20" :disabled="submitting">
                <span>{{ submitting ? 'Оформление...' : 'Подтвердить заказ' }}</span>
              </button>

              <p class="text-xs text-center text-gray-600 mt-4">После подтверждения менеджер проверит наличие, закрепит бронь и отправит уведомление в личный кабинет.</p>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
