<script setup>
defineOptions({
  name: 'CommonLoader',
});

defineProps({
  label: {
    type: String,
    default: 'Carregando...',
  },
});
</script>

<template>
  <div
    class="common-loader"
    role="status"
  >
    <span
      class="common-loader__spinner"
      aria-hidden="true"
    />
    <span class="common-loader__label">{{ label }}</span>
  </div>
</template>

<style scoped>
.common-loader {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 48px 16px;
  color: var(--color-text-tertiary);
  font-size: 0.85rem;
}

.common-loader__spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--color-primary-100);
  border-top-color: var(--color-primary-500);
  border-radius: 50%;
  animation: common-loader-spin 700ms linear infinite;
}

@keyframes common-loader-spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
