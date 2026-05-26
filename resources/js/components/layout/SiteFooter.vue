<script setup lang="ts">
const currentYear = new Date().getFullYear();
</script>

<template>
  <footer class="bg-dark-lighter border-t border-white/5 pt-16 pb-8 relative overflow-hidden">
    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-primary to-transparent opacity-50" />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
        <div class="col-span-1 md:col-span-1">
          <span class="text-4xl font-bold tracking-normal text-white italic block mb-6 font-display">AVANTIS</span>
          <p class="text-gray-500 text-sm leading-relaxed mb-6">
            Создаем технику для тех, кто не ищет легких путей. Надежность, мощность и характер в каждой детали.
          </p>
        </div>

        <div>
          <h4 class="text-white font-display text-xl uppercase tracking-wider mb-6 border-b border-white/10 pb-2 inline-block">Товары</h4>
          <ul class="space-y-3 text-base text-gray-500 font-medium">
            <li><RouterLink to="/catalog?type=ATV" class="hover:text-primary hover:pl-2 transition-all block">Квадроциклы</RouterLink></li>
            <li><RouterLink to="/catalog?type=Enduro" class="hover:text-primary hover:pl-2 transition-all block">Эндуро</RouterLink></li>
            <li><RouterLink to="/catalog" class="hover:text-primary hover:pl-2 transition-all block">Питбайки</RouterLink></li>
            <li><RouterLink to="/catalog?type=Parts" class="hover:text-primary hover:pl-2 transition-all block">Запчасти</RouterLink></li>
          </ul>
        </div>

        <div>
          <h4 class="text-white font-display text-xl uppercase tracking-wider mb-6 border-b border-white/10 pb-2 inline-block">Компания</h4>
          <ul class="space-y-3 text-base text-gray-500 font-medium">
            <li><a href="#" class="hover:text-primary hover:pl-2 transition-all block">О бренде</a></li>
            <li><a href="#" class="hover:text-primary hover:pl-2 transition-all block">Дилеры</a></li>
            <li><a href="#" class="hover:text-primary hover:pl-2 transition-all block">Новости</a></li>
            <li><a href="#" class="hover:text-primary hover:pl-2 transition-all block">Сотрудничество</a></li>
          </ul>
        </div>

        <div>
          <h4 class="text-white font-display text-xl uppercase tracking-wider mb-6 border-b border-white/10 pb-2 inline-block">Контакты</h4>
          <ul class="space-y-4 text-base text-gray-400">
            <li class="flex items-center group">
              <div class="w-8 h-8 rounded-sm bg-white/5 flex items-center justify-center mr-3 group-hover:bg-primary transition-colors">
                <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
              </div>
              <span class="group-hover:text-white transition-colors">8 (800) 200-25-49</span>
            </li>
            <li class="flex items-center group">
              <div class="w-8 h-8 rounded-sm bg-white/5 flex items-center justify-center mr-3 group-hover:bg-primary transition-colors">
                <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <span>Ежедневно 9:00 - 21:00</span>
            </li>
          </ul>
        </div>
      </div>

      <div class="border-t border-white/5 pt-8 flex flex-col md:flex-row justify-between items-center text-sm text-gray-600">
        <p>&copy; {{ currentYear }} Avantis. Все права защищены.</p>
        <p class="mt-2 md:mt-0 font-display uppercase tracking-widest text-xs opacity-50">Designed for Speed</p>
      </div>
    </div>
  </footer>
</template>
