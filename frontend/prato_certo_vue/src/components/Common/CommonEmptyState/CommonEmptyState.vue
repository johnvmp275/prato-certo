<script setup>
defineOptions({
  name: 'CommonEmptyState',
});

defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
  icon: {
    type: [Object, Function],
    default: null,
  },
});
</script>

<template>
  <div class="common-empty-state">
    <span
      v-if="icon"
      class="common-empty-state__icon"
      aria-hidden="true"
    >
      <component :is="icon" />
    </span>
    <p class="common-empty-state__title">{{ title }}</p>
    <p
      v-if="description"
      class="common-empty-state__description"
    >
      {{ description }}
    </p>
    <div
      v-if="$slots.default"
      class="common-empty-state__actions"
    >
      <slot />
    </div>
  </div>
</template>

<style scoped>
.common-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 40px 20px;
  text-align: center;
}

.common-empty-state__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 52px;
  height: 52px;
  margin-bottom: 6px;
  border-radius: 16px;
  background: var(--color-primary-50);
  color: var(--color-primary-500);
}

.common-empty-state__icon svg {
  width: 24px;
}

.common-empty-state__title {
  color: var(--color-text-title);
  font-weight: 600;
}

.common-empty-state__description {
  max-width: 340px;
  color: var(--color-text-secondary);
  font-size: 0.85rem;
}

.common-empty-state__actions {
  margin-top: 10px;
}
</style>
