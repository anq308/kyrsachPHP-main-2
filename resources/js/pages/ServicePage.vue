<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import api from '../api';
import AlertMessage from '../components/ui/AlertMessage.vue';
import PageHero from '../components/ui/PageHero.vue';
import { assetUrl } from '../assets';
import { sessionState } from '../session';
import type { ServiceSlot } from '../types';

const submitError = ref('');
const submitSuccess = ref('');
const submitting = ref(false);
const serviceSlots = ref<ServiceSlot[]>([]);

const serviceForm = reactive({
  name: sessionState.user?.name ?? '',
  phone: '',
  email: sessionState.user?.email ?? '',
  motorcycle_model: '',
  service_type: 'Техническое обслуживание',
  service_slot_id: '',
  preferred_date: '',
  comment: '',
});

const services = [
  {
    title: 'Техническое обслуживание',
    price: 'от 3 500 ₽',
    text: 'Масло, фильтры, цепь, тормоза, крепёж и контроль основных узлов перед сезоном.',
  },
  {
    title: 'Диагностика',
    price: 'от 1 500 ₽',
    text: 'Проверка двигателя, электрики, подвески и трансмиссии с понятной рекомендацией по работам.',
  },
  {
    title: 'Замена масла',
    price: 'от 1 200 ₽',
    text: 'Подбор масла, замена фильтра, контроль уровня и проверка состояния расходников.',
  },
  {
    title: 'Ремонт двигателя',
    price: 'от 8 000 ₽',
    text: 'Регулировка клапанов, поршневая группа, КПП, сцепление и устранение нестабильной работы.',
  },
  {
    title: 'Настройка подвески',
    price: 'от 4 500 ₽',
    text: 'Переборка вилки и амортизатора, замена расходников, настройка под вес и стиль езды.',
  },
  {
    title: 'Шиномонтаж',
    price: 'от 1 800 ₽',
    text: 'Замена шин, камер и балансировка колёс для эндуро, ATV и дорожной техники.',
  },
  {
    title: 'Подготовка к сезону',
    price: 'от 5 000 ₽',
    text: 'Комплексный осмотр, настройка, смазка, проверка тормозов и готовности техники к выезду.',
  },
  {
    title: 'Установка оборудования',
    price: 'индивидуально',
    text: 'Защита, свет, руль, кофры, выпуск и дополнительное оборудование под задачи клиента.',
  },
];

const trustItems = [
  'Гарантия на выполненные работы',
  'Оригинальные и проверенные запчасти',
  'Опытные механики по AVANTIS и эндуро-технике',
  'Прозрачное согласование стоимости до ремонта',
];

const processSteps = [
  ['01', 'Заявка', 'Вы описываете технику, симптом и удобную дату визита.'],
  ['02', 'Диагностика', 'Механик проверяет узлы и фиксирует список обязательных работ.'],
  ['03', 'Согласование', 'Менеджер подтверждает сроки, стоимость и наличие запчастей.'],
  ['04', 'Выдача', 'После работ техника проходит контроль и возвращается готовой к сезону.'],
];

const today = computed(() => new Date().toISOString().slice(0, 10));

function slotLabel(slot: ServiceSlot): string {
  const date = new Date(slot.service_date).toLocaleDateString('ru-RU');
  const service = slot.service_type ? ` · ${slot.service_type}` : '';

  return `${date}, ${slot.starts_at.slice(0, 5)}-${slot.ends_at.slice(0, 5)}${service} · свободно ${slot.capacity - slot.booked_count}`;
}

async function loadServiceSlots() {
  try {
    const { data } = await api.get('/service-slots');
    serviceSlots.value = data.service_slots ?? [];
  } catch {
    serviceSlots.value = [];
  }
}

async function submitServiceRequest() {
  submitError.value = '';
  submitSuccess.value = '';
  submitting.value = true;

  try {
    const { data } = await api.post('/service-requests', {
      ...serviceForm,
      service_slot_id: serviceForm.service_slot_id ? Number(serviceForm.service_slot_id) : null,
      preferred_date: serviceForm.preferred_date || null,
    });

    submitSuccess.value = data.message ?? 'Заявка на сервисное обслуживание отправлена. Менеджер свяжется с вами для подтверждения записи.';
    serviceForm.phone = '';
    serviceForm.motorcycle_model = '';
    serviceForm.service_type = 'Техническое обслуживание';
    serviceForm.service_slot_id = '';
    serviceForm.preferred_date = '';
    serviceForm.comment = '';
    await loadServiceSlots();
  } catch (error: any) {
    submitError.value = error?.response?.data?.message ?? 'Не удалось отправить запись на сервис.';
  } finally {
    submitting.value = false;
  }
}

onMounted(loadServiceSlots);
</script>

<template>
  <div>
    <PageHero
      :background-image="assetUrl('/images/service_tuning.png')"
      image-alt="Сервис AVANTIS"
      image-class="opacity-45"
      overlay-class="bg-gradient-to-r from-dark via-dark/85 to-dark/20"
      section-class="min-h-[680px] flex items-center"
      container-class="pt-16"
      :compact="false"
    >
      <div class="max-w-3xl animate-reveal">
        <h1 class="text-5xl md:text-7xl lg:text-8xl font-bold font-display italic text-white leading-none mb-8 tracking-normal shadow-orange-glow">
          СЕРВИС <span class="block text-transparent text-stroke">AVANTIS</span>
        </h1>
        <p class="text-lg md:text-2xl text-gray-300 max-w-2xl font-light leading-relaxed border-l-4 border-primary pl-6 mb-10">
          Профессиональное обслуживание, диагностика и подготовка техники к сезону. Работаем с эндуро, ATV и мототехникой, которой нужна надежность без компромиссов.
        </p>
        <div class="flex flex-col sm:flex-row gap-4">
          <a href="#service-form" class="btn btn-primary text-lg"><span>Записаться на сервис</span></a>
          <RouterLink to="/catalog" class="btn btn-outline text-lg"><span>Выбрать технику</span></RouterLink>
        </div>
      </div>
    </PageHero>

    <section class="bg-dark py-20 md:py-24 border-t border-white/5">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-12 animate-reveal">
          <div>
            <h2 class="text-4xl md:text-6xl font-display font-bold italic text-white tracking-normal">УСЛУГИ СЕРВИСА</h2>
            <div class="h-1 w-20 bg-primary mt-4" />
          </div>
          <p class="text-gray-400 max-w-xl text-lg">
            От быстрой предпродажной подготовки до полной переборки узлов. Все работы фиксируются как сервисные заявки в системе.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <article v-for="(service, index) in services" :key="service.title" class="reveal-card bg-dark-lighter border border-white/5 p-7 hover:border-primary/40 hover:-translate-y-1 transition-all duration-300" :style="`animation-delay:${index * 70}ms`">
            <div class="flex items-start justify-between gap-4 mb-8">
              <div class="w-12 h-12 border border-primary/30 bg-primary/10 text-primary flex items-center justify-center font-display font-bold">
                {{ String(index + 1).padStart(2, '0') }}
              </div>
              <span class="text-primary font-display font-bold text-xl">{{ service.price }}</span>
            </div>
            <h3 class="text-2xl font-display font-bold text-white uppercase mb-4">{{ service.title }}</h3>
            <p class="text-gray-500 leading-relaxed">{{ service.text }}</p>
          </article>
        </div>
      </div>
    </section>

    <section id="service-form" class="bg-dark-lighter py-20 md:py-24 border-y border-white/5 relative overflow-hidden">
      <div class="absolute inset-0 bg-grid-pattern opacity-[0.04]" />
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
          <div class="lg:col-span-5">
            <h2 class="text-4xl md:text-6xl font-display font-bold italic text-white tracking-normal mb-6">ЗАПИСЬ НА ОБСЛУЖИВАНИЕ</h2>
            <p class="text-gray-400 text-lg leading-relaxed mb-8">
              Оставьте контакты, модель техники и тип работ. Менеджер проверит загрузку сервиса и подтвердит удобное время.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div v-for="item in trustItems" :key="item" class="border border-white/5 bg-dark/60 p-4 text-sm font-bold uppercase tracking-wider text-gray-300">
                <span class="text-primary mr-2">/</span>{{ item }}
              </div>
            </div>
          </div>

          <form class="lg:col-span-7 bg-dark border border-white/10 p-6 md:p-8 shadow-2xl" @submit.prevent="submitServiceRequest">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <input v-model="serviceForm.name" type="text" required placeholder="Ваше имя" class="field-dark" />
              <input v-model="serviceForm.phone" type="text" required placeholder="+7 (___) ___-__-__" class="field-dark" />
              <input v-model="serviceForm.email" type="email" placeholder="email для уведомлений" class="field-dark" />
              <input v-model="serviceForm.motorcycle_model" type="text" required placeholder="Модель техники" class="field-dark" />
              <select v-model="serviceForm.service_type" required class="field-dark">
                <option v-for="service in services" :key="service.title" :value="service.title">{{ service.title }}</option>
              </select>
              <select v-model="serviceForm.service_slot_id" class="field-dark">
                <option value="">Время подберёт менеджер</option>
                <option v-for="slot in serviceSlots" :key="slot.id" :value="String(slot.id)">{{ slotLabel(slot) }}</option>
              </select>
              <input v-model="serviceForm.preferred_date" type="date" :min="today" class="field-dark" />
              <textarea v-model="serviceForm.comment" rows="5" placeholder="Комментарий: пробег, симптомы, желаемые работы" class="field-dark md:col-span-2 resize-none" />
            </div>

            <AlertMessage v-if="submitError" class="mt-5">{{ submitError }}</AlertMessage>
            <AlertMessage v-if="submitSuccess" tone="success" class="mt-5">{{ submitSuccess }}</AlertMessage>

            <button type="submit" class="btn btn-primary w-full mt-6 text-lg" :disabled="submitting">
              <span>{{ submitting ? 'Отправка...' : 'Отправить сервисную заявку' }}</span>
            </button>
          </form>
        </div>
      </div>
    </section>

    <section class="bg-dark py-20 md:py-24">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-12">
          <h2 class="text-4xl md:text-6xl font-display font-bold italic text-white tracking-normal">КАК ПРОХОДИТ ОБСЛУЖИВАНИЕ</h2>
          <div class="h-1 w-20 bg-primary mt-4" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-px bg-white/10 border border-white/10">
          <article v-for="step in processSteps" :key="step[0]" class="bg-dark p-6 md:p-8 min-h-64 hover:bg-dark-lighter transition-colors">
            <p class="text-primary font-display font-bold text-5xl mb-8">{{ step[0] }}</p>
            <h3 class="text-xl font-display font-bold text-white uppercase mb-4">{{ step[1] }}</h3>
            <p class="text-gray-500 leading-relaxed">{{ step[2] }}</p>
          </article>
        </div>
      </div>
    </section>
  </div>
</template>
