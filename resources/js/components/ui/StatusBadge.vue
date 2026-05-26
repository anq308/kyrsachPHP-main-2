<script setup lang="ts">
import { computed } from 'vue';

type BadgeKind = 'order' | 'sales' | 'service' | 'product' | 'role';

interface Props {
  status: string;
  kind?: BadgeKind;
}

const props = withDefaults(defineProps<Props>(), {
  kind: 'order',
});

const labels: Record<string, string> = {
  new: 'Новая',
  processing: 'В обработке',
  completed: 'Завершён',
  cancelled: 'Отменена',
  in_progress: 'В работе',
  approved: 'Согласована',
  confirmed: 'Подтверждена',
  in_service: 'В сервисе',
  done: 'Готово',
  available: 'В наличии',
  unavailable: 'Нет в наличии',
  admin: 'Админ',
  user: 'Клиент',
};

const classes = computed(() => {
  const palette: Record<string, string> = {
    new: 'border-blue-500/30 bg-blue-500/10 text-blue-300',
    processing: 'border-yellow-500/30 bg-yellow-500/10 text-yellow-300',
    in_progress: 'border-yellow-500/30 bg-yellow-500/10 text-yellow-300',
    approved: 'border-orange-500/30 bg-orange-500/10 text-orange-300',
    confirmed: 'border-orange-500/30 bg-orange-500/10 text-orange-300',
    in_service: 'border-primary/40 bg-primary/10 text-primary',
    completed: 'border-green-500/30 bg-green-500/10 text-green-300',
    done: 'border-green-500/30 bg-green-500/10 text-green-300',
    cancelled: 'border-red-500/30 bg-red-500/10 text-red-300',
    available: 'border-green-500/30 bg-green-500/10 text-green-300',
    unavailable: 'border-red-500/30 bg-red-500/10 text-red-300',
    admin: 'border-primary/40 bg-primary/10 text-primary',
    user: 'border-white/10 bg-white/5 text-gray-400',
  };

  return palette[props.status] ?? 'border-white/10 bg-white/5 text-gray-300';
});

const text = computed(() => labels[props.status] ?? props.status);
</script>

<template>
  <span :class="['inline-flex items-center gap-1.5 border px-3 py-1 text-[11px] font-bold uppercase tracking-wider', classes]">
    <span class="h-1.5 w-1.5 rounded-full bg-current" />
    {{ text }}
  </span>
</template>
