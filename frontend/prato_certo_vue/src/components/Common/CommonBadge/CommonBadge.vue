<script setup>
defineOptions({
  name: 'CommonBadge',
});

defineProps({
  label: {
    type: String,
    required: true,
  },
  variant: {
    type: String,
    default: 'neutral',
    validator: value => ['success', 'neutral', 'primary', 'warning', 'danger', 'info'].includes(value),
  },
  dot: {
    type: Boolean,
    default: false,
  },
});
</script>

<template>
  <span
    class="common-badge"
    :class="`common-badge--${variant}`"
  >
    <span
      v-if="dot"
      class="common-badge__dot"
      aria-hidden="true"
    />
    {{ label }}
  </span>
</template>

<style scoped>
.common-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 0.74rem;
  font-weight: 600;
  line-height: 1.4;
  white-space: nowrap;
}

.common-badge__dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.common-badge--success {
  background: var(--color-success-50);
  color: var(--color-success-700);
}

.common-badge--neutral {
  background: var(--color-neutral-100);
  color: var(--color-text-secondary);
}

.common-badge--primary {
  background: var(--color-primary-50);
  color: var(--color-primary-700);
}

.common-badge--warning {
  background: var(--color-warning-50);
  color: var(--color-warning-700);
}

.common-badge--danger {
  background: var(--color-error-50);
  color: var(--color-error-700);
}

.common-badge--info {
  background: var(--color-info-50);
  color: var(--color-info-600);
}
</style>
