<script setup lang="ts">
interface Props {
  title: string;
  description?: string;
  actionTo?: string;
  actionLabel?: string;
  containerClass?: string;
  titleClass?: string;
  descriptionClass?: string;
  actionClass?: string;
}

withDefaults(defineProps<Props>(), {
  description: '',
  actionTo: '',
  actionLabel: '',
  containerClass: '',
  titleClass: 'text-xl text-gray-500 mb-4',
  descriptionClass: 'text-gray-500 mb-8 max-w-md mx-auto',
  actionClass: 'btn btn-primary',
});
</script>

<template>
  <div :class="['bg-dark-lighter border border-white/5 p-12 text-center', containerClass]">
    <div v-if="$slots.icon" class="mb-4 flex justify-center">
      <slot name="icon" />
    </div>

    <p :class="titleClass">{{ title }}</p>
    <p v-if="description" :class="descriptionClass">{{ description }}</p>

    <RouterLink v-if="actionTo && actionLabel" :to="actionTo" :class="actionClass">
      <span>{{ actionLabel }}</span>
    </RouterLink>
  </div>
</template>
