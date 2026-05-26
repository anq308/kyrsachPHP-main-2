<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  tone?: 'error' | 'success';
  title?: string;
  variant?: 'inline' | 'banner';
}

const props = withDefaults(defineProps<Props>(), {
  tone: 'error',
  title: '',
  variant: 'inline',
});

const wrapperClass = computed(() => {
  if (props.variant === 'banner') {
    return props.tone === 'success'
      ? 'bg-green-500/10 border-l-4 border-green-500 text-green-400 p-4 rounded-r shadow-lg backdrop-blur-sm'
      : 'bg-red-500/10 border-l-4 border-red-500 text-red-400 p-4 rounded-r shadow-lg backdrop-blur-sm';
  }

  return props.tone === 'success'
    ? 'mb-6 p-4 bg-green-500/10 border border-green-500/30 text-green-400 text-sm'
    : 'mb-6 p-4 bg-red-500/10 border border-red-500/30 text-red-400 text-sm';
});
</script>

<template>
  <div :class="wrapperClass" role="alert">
    <p v-if="title" class="font-bold font-display text-lg">{{ title }}</p>
    <slot />
  </div>
</template>
