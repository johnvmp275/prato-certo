<script setup>
import IconMinus from '@/components/Icons/IconMinus.vue';
import IconPlus from '@/components/Icons/IconPlus.vue';

defineOptions({
  name: 'CommonQuantity',
});

defineProps({
  min: {
    type: Number,
    default: 0,
  },
  max: {
    type: Number,
    default: 999,
  },
  size: {
    type: String,
    default: 'md',
    validator: value => ['sm', 'md'].includes(value),
  },
});

const model = defineModel({ type: Number, default: 1 });
</script>

<template>
  <div
    class="common-quantity"
    :class="`common-quantity--${size}`"
  >
    <button
      type="button"
      class="common-quantity__button"
      aria-label="Diminuir quantidade"
      :disabled="model <= min"
      @click="model = model - 1"
    >
      <IconMinus aria-hidden="true" />
    </button>
    <span
      class="common-quantity__value tabular"
      aria-live="polite"
    >
      {{ model }}
    </span>
    <button
      type="button"
      class="common-quantity__button common-quantity__button--plus"
      aria-label="Aumentar quantidade"
      :disabled="model >= max"
      @click="model = model + 1"
    >
      <IconPlus aria-hidden="true" />
    </button>
  </div>
</template>

<style scoped>
.common-quantity {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px;
  border: 1px solid var(--color-border-default);
  border-radius: 10px;
  background: var(--color-surface);
}

.common-quantity__button {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 8px;
  color: var(--color-text-secondary);
}

.common-quantity__button--plus {
  background: var(--color-primary-50);
  color: var(--color-primary-600);
}

.common-quantity__button svg {
  width: 15px;
}

.common-quantity__button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.common-quantity__value {
  min-width: 26px;
  color: var(--color-text-title);
  font-size: 0.9rem;
  font-weight: 700;
  text-align: center;
}

.common-quantity--sm .common-quantity__button {
  width: 26px;
  height: 26px;
}

.common-quantity--sm .common-quantity__value {
  min-width: 22px;
  font-size: 0.84rem;
}

@media (hover: hover) and (pointer: fine) {
  .common-quantity__button:not(:disabled):hover {
    background: var(--color-neutral-100);
  }
  .common-quantity__button--plus:not(:disabled):hover {
    background: var(--color-primary-100);
  }
}
</style>
