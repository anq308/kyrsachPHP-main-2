<script setup lang="ts">
import { reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import api from '../api';
import { loadSession } from '../session';

const router = useRouter();
const submitting = ref(false);
const errorText = ref('');
const showPassword = ref(false);

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

async function register() {
  errorText.value = '';
  submitting.value = true;

  try {
    await api.post('/register', form);
    await loadSession();
    await router.push('/');
  } catch (error: any) {
    errorText.value =
      error?.response?.data?.errors?.email?.[0] ??
      error?.response?.data?.errors?.password?.[0] ??
      'Ошибка регистрации.';
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center relative overflow-hidden py-12">
    <div class="absolute inset-0 bg-dark" />
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-primary/10 rounded-full blur-[120px] pointer-events-none" />
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-primary/5 rounded-full blur-[120px] pointer-events-none" />

    <div class="relative z-10 w-full max-w-lg mx-auto px-4">
      <div class="text-center mb-10">
        <RouterLink to="/" class="inline-block mb-6"><span class="text-4xl font-display font-bold text-white tracking-wider">AVANTIS</span></RouterLink>
        <h1 class="text-3xl md:text-4xl font-display font-bold text-white uppercase tracking-wide mb-2">Регистрация</h1>
        <p class="text-gray-500">Создайте аккаунт для доступа к личному кабинету</p>
      </div>

      <div class="bg-dark-lighter border border-white/5 p-8 md:p-10 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary via-primary/80 to-transparent" />

        <div v-if="errorText" class="mb-6 p-4 bg-red-500/10 border border-red-500/30 text-red-400 text-sm">{{ errorText }}</div>

        <form class="space-y-5" @submit.prevent="register">
          <div>
            <label for="name" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Имя</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7" /></svg>
              </div>
              <input id="name" v-model="form.name" type="text" required class="w-full bg-dark border border-white/10 text-white pl-12 pr-4 py-3.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all placeholder-gray-600 text-sm" placeholder="Ваше имя" />
            </div>
          </div>

          <div>
            <label for="email" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Email</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2" /></svg>
              </div>
              <input id="email" v-model="form.email" type="email" required class="w-full bg-dark border border-white/10 text-white pl-12 pr-4 py-3.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all placeholder-gray-600 text-sm" placeholder="your@email.com" />
            </div>
          </div>

          <div>
            <label for="password" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Пароль</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
              </div>
              <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'" required class="w-full bg-dark border border-white/10 text-white pl-12 pr-12 py-3.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all placeholder-gray-600 text-sm" placeholder="Минимум 8 символов" />
              <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-600 hover:text-gray-400 transition-colors" @click="showPassword = !showPassword">
                <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7" /></svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
              </button>
            </div>
          </div>

          <div>
            <label for="password_confirmation" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Подтверждение пароля</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016" /></svg>
              </div>
              <input id="password_confirmation" v-model="form.password_confirmation" type="password" required class="w-full bg-dark border border-white/10 text-white pl-12 pr-4 py-3.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all placeholder-gray-600 text-sm" placeholder="Повторите пароль" />
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-full text-lg shadow-lg shadow-primary/20 py-4 mt-2" :disabled="submitting">
            <span>{{ submitting ? 'Создание...' : 'Создать аккаунт' }}</span>
          </button>
        </form>

        <div class="flex items-center my-8">
          <div class="flex-1 h-px bg-white/10" />
          <span class="px-4 text-xs text-gray-600 uppercase tracking-wider">или</span>
          <div class="flex-1 h-px bg-white/10" />
        </div>

        <div class="text-center">
          <p class="text-gray-500 text-sm mb-3">Уже есть аккаунт?</p>
          <RouterLink to="/login" class="btn btn-outline w-full"><span>Войти</span></RouterLink>
        </div>
      </div>

      <div class="text-center mt-8">
        <RouterLink to="/" class="text-gray-600 hover:text-primary text-sm transition-colors inline-flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
          Вернуться на главную
        </RouterLink>
      </div>
    </div>
  </div>
</template>
