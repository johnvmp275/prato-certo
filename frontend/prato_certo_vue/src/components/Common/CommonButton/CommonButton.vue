<script setup>
defineOptions({
  name: 'CommonButton',
});

defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: value => ['primary', 'success', 'secondary', 'ghost', 'danger', 'soft'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: value => ['sm', 'md', 'lg'].includes(value),
  },
  type: {
    type: String,
    default: 'button',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  block: {
    type: Boolean,
    default: false,
  },
});
</script>

<template>
  <button
    :type="type"
    class="common-button"
    :class="[`common-button--${variant}`, `common-button--${size}`, { 'is-block': block, 'is-loading': loading }]"
    :disabled="disabled || loading"
    :aria-busy="loading"
  >
    <span
      v-if="loading"
      class="common-button__spinner"
      aria-hidden="true"
    />
    <span
      v-else-if="$slots.icon"
      class="common-button__icon"
      aria-hidden="true"
    >
      <slot name="icon" />
    </span>
    <span class="common-button__label">
      <slot />
    </span>
  </button>
</template>

<style scoped>
.common-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 44px;
  padding: 0 18px;
  border: 1px solid transparent;
  border-radius: 12px;
  font-size: 0.88rem;
  font-weight: 600;
  white-space: nowrap;
  cursor: pointer;
  transition:
    background-color 160ms ease,
    border-color 160ms ease,
    color 160ms ease,
    box-shadow 160ms ease,
    transform 120ms ease;
}

.common-button:active:not(:disabled) {
  transform: scale(0.98);
}

.common-button:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.common-button.is-block {
  width: 100%;
}

/* TAMANHOS */
.common-button--sm {
  min-height: 36px;
  padding: 0 12px;
  border-radius: 10px;
  font-size: 0.8rem;
}

.common-button--lg {
  min-height: 52px;
  padding: 0 24px;
  font-size: 0.95rem;
}

/* VARIANTES */
.common-button--primary {
  background: var(--color-button-primary);
  color: var(--color-text-inverse);
}

.common-button--success {
  background: var(--color-button-success);
  color: var(--color-text-inverse);
}

.common-button--secondary {
  border-color: var(--color-border-default);
  background: var(--color-button-secondary);
  color: var(--color-text-primary);
}

.common-button--ghost {
  background: transparent;
  color: var(--color-text-secondary);
}

.common-button--danger {
  border-color: var(--color-error-100);
  background: var(--color-error-50);
  color: var(--color-error-600);
}

.common-button--soft {
  background: var(--color-primary-50);
  color: var(--color-primary-600);
}

.common-button__icon {
  display: inline-flex;
  width: 18px;
  height: 18px;
}

.common-button--sm .common-button__icon {
  width: 15px;
  height: 15px;
}

.common-button__spinner {
  width: 16px;
  height: 16px;
  border: 2px solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: common-button-spin 700ms linear infinite;
}

@keyframes common-button-spin {
  to {
    transform: rotate(360deg);
  }
}

@media (hover: hover) and (pointer: fine) {
  .common-button--primary:not(:disabled):hover {
    background: var(--color-button-primary-hover);
    box-shadow: var(--shadow-primary);
  }
  .common-button--success:not(:disabled):hover {
    background: var(--color-button-success-hover);
  }
  .common-button--secondary:not(:disabled):hover {
    border-color: var(--color-border-dark);
    background: var(--color-button-secondary-hover);
  }
  .common-button--ghost:not(:disabled):hover {
    background: var(--color-neutral-100);
    color: var(--color-text-primary);
  }
  .common-button--danger:not(:disabled):hover {
    background: var(--color-error-100);
  }
  .common-button--soft:not(:disabled):hover {
    background: var(--color-primary-100);
  }
}
</style>
