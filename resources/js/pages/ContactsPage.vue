<script setup lang="ts">
import { reactive, ref } from 'vue';
import api from '../api';

const submitting = ref(false);
const errorText = ref('');
const successText = ref('');

const form = reactive({
  name: '',
  phone: '',
  email: '',
  message: '',
});

async function submitForm() {
  errorText.value = '';
  successText.value = '';
  submitting.value = true;

  try {
    const { data } = await api.post('/contact', form);
    successText.value = data.message ?? 'Сообщение отправлено!';
    form.name = '';
    form.phone = '';
    form.email = '';
    form.message = '';
  } catch {
    errorText.value = 'Не удалось отправить сообщение.';
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <div>
    <div class="relative py-24 bg-dark overflow-hidden">
      <div class="absolute inset-0">
        <div class="absolute inset-0 bg-gradient-to-b from-dark via-dark/90 to-dark" />
      </div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <h1 class="text-5xl md:text-7xl font-bold font-display italic text-white leading-none mb-6 tracking-normal shadow-orange-glow">
          <span class="text-gradient">КОНТАКТЫ</span>
        </h1>
        <p class="mt-4 text-xl text-gray-300 max-w-2xl mx-auto font-light">
          Мы всегда рады видеть вас в нашем салоне. Приезжайте за техникой, экипировкой или просто пообщаться.
        </p>
      </div>
    </div>

    <div class="bg-dark py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
          <div>
            <h2 class="text-3xl font-bold font-display text-white mb-8 uppercase italic">Наши координаты</h2>

            <div class="space-y-8">
              <div class="flex items-start">
                <div class="flex-shrink-0 w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0" /></svg>
                </div>
                <div class="ml-6">
                  <h3 class="text-lg font-bold text-white uppercase mb-2">Адрес</h3>
                  <p class="text-gray-400">г. Москва, ул. Мотоциклетная, д. 15, стр. 2</p>
                  <p class="text-gray-500 text-sm mt-1">Въезд со стороны парковки</p>
                </div>
              </div>

              <div class="flex items-start">
                <div class="flex-shrink-0 w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                </div>
                <div class="ml-6">
                  <h3 class="text-lg font-bold text-white uppercase mb-2">Телефон</h3>
                  <p class="text-gray-400 font-display text-xl">8 (800) 200-25-49</p>
                  <p class="text-gray-500 text-sm mt-1">Звонок бесплатный по РФ</p>
                </div>
              </div>

              <div class="flex items-start">
                <div class="flex-shrink-0 w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2" /></svg>
                </div>
                <div class="ml-6">
                  <h3 class="text-lg font-bold text-white uppercase mb-2">Email</h3>
                  <p class="text-gray-400">info@avantsb.ru</p>
                  <p class="text-gray-400">sales@avantsb.ru</p>
                </div>
              </div>

              <div class="flex items-start">
                <div class="flex-shrink-0 w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0" /></svg>
                </div>
                <div class="ml-6">
                  <h3 class="text-lg font-bold text-white uppercase mb-2">Режим работы</h3>
                  <p class="text-gray-400">Пн-Пт: 09:00 - 20:00</p>
                  <p class="text-gray-400">Сб-Вс: 10:00 - 18:00</p>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-dark-lighter border border-white/5 p-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-primary/20 blur-3xl" />

            <form class="space-y-6" @submit.prevent="submitForm">
              <h3 class="text-2xl font-bold font-display text-white mb-6 uppercase">Напишите нам</h3>

              <div>
                <label for="name" class="block text-sm font-medium text-gray-500 mb-2 uppercase tracking-wide">Ваше имя *</label>
                <input id="name" v-model="form.name" type="text" required class="w-full bg-dark border border-white/10 text-white px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors placeholder-gray-600" placeholder="Иван Иванов" />
              </div>

              <div>
                <label for="phone" class="block text-sm font-medium text-gray-500 mb-2 uppercase tracking-wide">Телефон</label>
                <input id="phone" v-model="form.phone" type="text" class="w-full bg-dark border border-white/10 text-white px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors placeholder-gray-600" placeholder="+7 (999) 000-00-00" />
              </div>

              <div>
                <label for="email" class="block text-sm font-medium text-gray-500 mb-2 uppercase tracking-wide">Email</label>
                <input id="email" v-model="form.email" type="email" class="w-full bg-dark border border-white/10 text-white px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors placeholder-gray-600" placeholder="your@email.com" />
              </div>

              <div>
                <label for="message" class="block text-sm font-medium text-gray-500 mb-2 uppercase tracking-wide">Сообщение *</label>
                <textarea id="message" v-model="form.message" rows="4" required class="w-full bg-dark border border-white/10 text-white px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors placeholder-gray-600" placeholder="Ваш вопрос или пожелание" />
              </div>

              <p v-if="errorText" class="text-red-400 text-sm">{{ errorText }}</p>
              <p v-if="successText" class="text-green-400 text-sm">{{ successText }}</p>

              <button type="submit" class="btn btn-primary w-full text-xl shadow-lg" :disabled="submitting">
                <span>{{ submitting ? 'Отправка...' : 'Отправить' }}</span>
              </button>

              <p class="text-xs text-center text-gray-600 mt-4">Нажимая кнопку, вы соглашаетесь с политикой конфиденциальности</p>
            </form>
          </div>
        </div>

        <div class="mt-16 border border-white/5 h-96 w-full grayscale invert opacity-80 hover:grayscale-0 hover:opacity-100 transition-all duration-500">
          <iframe
            src="https://yandex.ru/map-widget/v1/?um=constructor%3A086c87994260029853921703080076a028020826978187884878438407481182&amp;source=constructor"
            width="100%"
            height="100%"
            frameborder="0"
          />
        </div>
      </div>
    </div>
  </div>
</template>
