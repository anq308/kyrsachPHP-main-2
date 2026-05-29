<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import api, { refreshCsrfToken } from '../../api';
import { clearSession, sessionState } from '../../session';

const emit = defineEmits<{
  error: [message: string];
}>();

const profileMenuOpen = ref(false);
const mobileMenuOpen = ref(false);

const isAuth = computed(() => Boolean(sessionState.user));
const isStaff = computed(() => Boolean(sessionState.user?.can_manage));
const firstLetter = computed(() => sessionState.user?.name?.trim().charAt(0).toUpperCase() ?? 'U');

function closeMenuOnOutsideClick(event: MouseEvent) {
  const target = event.target as HTMLElement;
  if (!target.closest('[data-profile-menu]')) {
    profileMenuOpen.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', closeMenuOnOutsideClick);
});

onUnmounted(() => {
  document.removeEventListener('click', closeMenuOnOutsideClick);
});

async function logout() {
  try {
    await api.post('/logout');
    clearSession();
    mobileMenuOpen.value = false;
    window.location.href = '/';
  } catch (error: any) {
    if (error?.response?.status === 419) {
      try {
        await refreshCsrfToken();
        await api.post('/logout');
        clearSession();
        mobileMenuOpen.value = false;
        window.location.href = '/';
        return;
      } catch {
        // Fall through to the visible error below.
      }
    }

    if (error?.response?.status === 401) {
      clearSession();
      mobileMenuOpen.value = false;
      window.location.href = '/';
      return;
    }

    emit('error', 'Не удалось выйти из системы.');
  }
}
</script>

<template>
  <nav class="bg-dark/90 backdrop-blur-md text-white sticky top-0 z-50 border-b border-white/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-20 items-center gap-4">
        <div class="flex items-center">
          <RouterLink to="/" class="flex items-center group relative">
            <span class="text-5xl font-bold tracking-normal font-display italic text-white group-hover:text-primary transition-colors duration-300 relative z-10">
              AVANTIS
            </span>
            <div class="absolute -inset-2 bg-primary/20 blur-lg opacity-0 group-hover:opacity-100 transition-opacity duration-500 rounded-full" />
          </RouterLink>

          <div class="hidden md:ml-12 md:flex md:space-x-8">
            <RouterLink to="/" class="relative text-xl font-medium font-display text-white hover:text-primary transition-colors tracking-wide py-2 group">
              Главная
              <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-primary transition-all duration-300 group-hover:w-full" />
            </RouterLink>
            <RouterLink to="/catalog" class="relative text-xl font-medium font-display text-gray-300 hover:text-primary transition-colors tracking-wide py-2 group">
              Каталог
              <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-primary transition-all duration-300 group-hover:w-full" />
            </RouterLink>
            <RouterLink to="/service" class="relative text-xl font-medium font-display text-gray-300 hover:text-primary transition-colors tracking-wide py-2 group">
              Сервис
              <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-primary transition-all duration-300 group-hover:w-full" />
            </RouterLink>
            <RouterLink to="/contacts" class="relative text-xl font-medium font-display text-gray-300 hover:text-primary transition-colors tracking-wide py-2 group">
              Контакты
              <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-primary transition-all duration-300 group-hover:w-full" />
            </RouterLink>
          </div>
        </div>

        <div class="flex items-center space-x-3 sm:space-x-5">
          <a href="tel:88002002549" class="hidden lg:block text-white font-display font-bold text-2xl tracking-wide hover:text-primary transition-colors">
            8 (800) 200-25-49
          </a>

          <RouterLink to="/compare" class="text-gray-400 hover:text-primary relative transition-all duration-300 hover:scale-110" title="Сравнение">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
            <span v-if="sessionState.compareCount > 0" class="absolute -top-2 -right-2 inline-flex items-center justify-center w-5 h-5 text-xs font-bold leading-none text-white bg-blue-500 rounded-full">
              {{ sessionState.compareCount }}
            </span>
          </RouterLink>

          <RouterLink v-if="isAuth" to="/favorites" class="text-gray-400 hover:text-red-400 relative transition-all duration-300 hover:scale-110" title="Избранное">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
          </RouterLink>

          <RouterLink to="/cart" class="text-gray-400 hover:text-primary relative transition-all duration-300 hover:scale-110" title="Корзина">
            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span v-if="sessionState.cartCount > 0" class="absolute -top-2 -right-2 inline-flex items-center justify-center w-5 h-5 text-xs font-bold leading-none text-white bg-primary rounded-full animate-pulse">
              {{ sessionState.cartCount }}
            </span>
          </RouterLink>

          <div v-if="isAuth" class="relative" data-profile-menu>
            <button class="flex items-center text-sm font-medium text-gray-300 hover:text-white focus:outline-none" @click="profileMenuOpen = !profileMenuOpen">
              <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center text-primary text-sm font-bold font-display mr-2">
                {{ firstLetter }}
              </div>
              <span class="hidden md:inline font-display text-xl tracking-wide mr-1">{{ sessionState.user?.name }}</span>
              <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>

            <div v-show="profileMenuOpen" class="origin-top-right absolute right-0 mt-2 w-48 rounded-sm shadow-xl bg-dark-lighter border border-white/10 py-1 z-50">
              <RouterLink to="/profile" class="block px-4 py-2 text-sm text-gray-300 hover:bg-primary hover:text-white transition-colors" @click="profileMenuOpen = false">
                Личный кабинет
              </RouterLink>
              <RouterLink to="/favorites" class="block px-4 py-2 text-sm text-gray-300 hover:bg-primary hover:text-white transition-colors" @click="profileMenuOpen = false">
                Избранное
              </RouterLink>
              <RouterLink v-if="isStaff" to="/admin" class="block px-4 py-2 text-sm text-primary hover:bg-primary hover:text-white transition-colors font-bold" @click="profileMenuOpen = false">
                Панель менеджера
              </RouterLink>
              <div class="border-t border-white/10 my-1" />
              <button class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-red-500/80 hover:text-white transition-colors" @click="logout">
                Выйти
              </button>
            </div>
          </div>

          <div v-else class="hidden md:flex items-center space-x-4">
            <RouterLink to="/login" class="text-lg font-display font-medium text-gray-300 hover:text-white transition-colors">Вход</RouterLink>
            <RouterLink to="/register" class="px-5 py-2 text-lg font-display font-bold text-dark bg-primary hover:bg-white transition-colors skew-x-[-10deg] inline-block">
              <span class="skew-x-[10deg] inline-block">Регистрация</span>
            </RouterLink>
          </div>

          <button type="button" class="md:hidden w-10 h-10 border border-white/10 flex items-center justify-center text-gray-300 hover:text-white hover:border-primary/50 transition-colors" @click="mobileMenuOpen = !mobileMenuOpen" aria-label="Открыть меню">
            <svg v-if="!mobileMenuOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" /></svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
      </div>

      <div v-show="mobileMenuOpen" class="md:hidden border-t border-white/10 py-4 space-y-2">
        <RouterLink to="/" class="mobile-nav-link" @click="mobileMenuOpen = false">Главная</RouterLink>
        <RouterLink to="/catalog" class="mobile-nav-link" @click="mobileMenuOpen = false">Каталог</RouterLink>
        <RouterLink to="/service" class="mobile-nav-link" @click="mobileMenuOpen = false">Сервис</RouterLink>
        <RouterLink to="/contacts" class="mobile-nav-link" @click="mobileMenuOpen = false">Контакты</RouterLink>
        <RouterLink v-if="isAuth" to="/profile" class="mobile-nav-link" @click="mobileMenuOpen = false">Личный кабинет</RouterLink>
        <RouterLink v-if="isStaff" to="/admin" class="mobile-nav-link text-primary" @click="mobileMenuOpen = false">Панель менеджера</RouterLink>
        <div v-if="!isAuth" class="grid grid-cols-2 gap-3 pt-2">
          <RouterLink to="/login" class="mobile-action-link" @click="mobileMenuOpen = false">Вход</RouterLink>
          <RouterLink to="/register" class="mobile-action-link bg-primary text-dark border-primary" @click="mobileMenuOpen = false">Регистрация</RouterLink>
        </div>
      </div>
    </div>
  </nav>
</template>
