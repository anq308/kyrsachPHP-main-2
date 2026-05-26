<script setup lang="ts">
interface Props {
  backgroundImage?: string;
  imageAlt?: string;
  imageClass?: string;
  overlayClass?: string;
  sectionClass?: string;
  containerClass?: string;
  center?: boolean;
  compact?: boolean;
}

withDefaults(defineProps<Props>(), {
  backgroundImage: '',
  imageAlt: '',
  imageClass: 'opacity-30',
  overlayClass: 'bg-gradient-to-b from-dark via-dark/90 to-dark',
  sectionClass: '',
  containerClass: '',
  center: false,
  compact: true,
});
</script>

<template>
  <div :class="['relative bg-dark overflow-hidden', compact ? 'py-16 md:py-20' : 'py-24 md:py-32', sectionClass]">
    <div class="absolute inset-0 z-0">
      <img v-if="backgroundImage" :src="backgroundImage" :alt="imageAlt" :class="['w-full h-full object-cover', imageClass]" />
      <div :class="['absolute inset-0', overlayClass]" />
      <slot name="background" />
    </div>

    <div :class="['relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8', center ? 'text-center' : '', containerClass]">
      <slot />
    </div>
  </div>
</template>
