<script setup>
defineOptions({
  name: 'CommonStatCard',
});

defineProps({
  icon: {
    type: [Object, Function],
    default: null,
  },
  label: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    required: true,
  },
  hint: {
    type: String,
    default: '',
  },
  tone: {
    type: String,
    default: 'primary',
    validator: value => ['primary', 'success', 'warning', 'info'].includes(value),
  },
});
</script>

<template>
  <article class="common-stat-card card">
    <span
      v-if="icon"
      class="common-stat-card__icon"
      :class="`common-stat-card__icon--${tone}`"
      aria-hidden="true"
    >
      <component :is="icon" />
    </span>
    <div class="common-stat-card__content">
      <p class="common-stat-card__label">{{ label }}</p>
      <p class="common-stat-card__value tabular">{{ value }}</p>
      <p
        v-if="hint"
        class="common-stat-card__hint"
      >
        {{ hint }}
      </p>
    </div>
  </article>
</template>

<style scoped>
.common-stat-card {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 18px;
}

.common-stat-card__icon {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: 14px;
}

.common-stat-card__icon svg {
  width: 21px;
}

.common-stat-card__icon--primary {
  background: var(--color-primary-50);
  color: var(--color-primary-600);
}

.common-stat-card__icon--success {
  background: var(--color-success-50);
  color: var(--color-success-600);
}

.common-stat-card__icon--warning {
  background: var(--color-warning-50);
  color: var(--color-warning-600);
}

.common-stat-card__icon--info {
  background: var(--color-info-50);
  color: var(--color-info-600);
}

.common-stat-card__content {
  min-width: 0;
}

.common-stat-card__label {
  color: var(--color-text-secondary);
  font-size: 0.8rem;
  font-weight: 500;
}

.common-stat-card__value {
  margin-top: 2px;
  color: var(--color-text-title);
  font-family: var(--font-title);
  font-size: 1.35rem;
  font-weight: 600;
}

.common-stat-card__hint {
  margin-top: 2px;
  color: var(--color-text-tertiary);
  font-size: 0.75rem;
}
</style>
