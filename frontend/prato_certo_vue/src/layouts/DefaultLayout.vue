<script setup>
import DefaultHeader from '@/components/Default/DefaultHeader/DefaultHeader.vue';
</script>

<template>
  <div class="default-layout">
    <DefaultHeader />
    <div class="default-layout__content">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.default-layout {
  min-height: 100vh;
  min-height: 100dvh;
  background: var(--color-background);
}

.default-layout__content {
  min-width: 0;
}

@media (min-width: 1024px) {
  .default-layout {
    display: grid;
    grid-template-columns: max-content minmax(0, 1fr);
  }
}
</style>
