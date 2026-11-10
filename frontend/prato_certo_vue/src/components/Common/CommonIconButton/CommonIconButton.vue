<script setup>
defineOptions({
  name: 'CommonIconButton',
});

defineProps({
  label: {
    type: String,
    required: true,
  },
  variant: {
    type: String,
    default: 'secondary',
    validator: value => ['secondary', 'ghost', 'danger', 'primary'].includes(value),
  },
  size: {
    type: Number,
    default: 36,
  },
});
</script>

<template>
  <button
    type="button"
    class="common-icon-button"
    :class="`common-icon-button--${variant}`"
    :aria-label="label"
    :title="label"
    :style="{ width: `${size}px`, height: `${size}px` }"
  >
    <slot />
  </button>
</template>

<style scoped>
.common-icon-button {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border: 1px solid transparent;
  border-radius: 10px;
  cursor: pointer;
  transition:
    background-color 160ms ease,
    color 160ms ease,
    border-color 160ms ease;
}

.common-icon-button :slotted(svg) {
  width: 17px;
  height: 17px;
}

.common-icon-button:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.common-icon-button--secondary {
  border-color: var(--color-border-default);
  background: var(--color-surface);
  color: var(--color-text-secondary);
}

.common-icon-button--ghost {
  background: transparent;
  color: var(--color-text-secondary);
}

.common-icon-button--danger {
  background: var(--color-error-50);
  color: var(--color-error-600);
}

.common-icon-button--primary {
  background: var(--color-primary-500);
  color: var(--color-text-inverse);
}

@media (hover: hover) and (pointer: fine) {
  .common-icon-button--secondary:not(:disabled):hover,
  .common-icon-button--ghost:not(:disabled):hover {
    background: var(--color-neutral-100);
    color: var(--color-text-title);
  }
  .common-icon-button--danger:not(:disabled):hover {
    background: var(--color-error-100);
  }
  .common-icon-button--primary:not(:disabled):hover {
    background: var(--color-primary-600);
  }
}
</style>
