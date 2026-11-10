<script setup>
defineOptions({
  name: 'CommonSwitch',
});

defineProps({
  label: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
});

const model = defineModel({ type: Boolean, default: false });
</script>

<template>
  <label class="common-switch">
    <span class="common-switch__text">
      <span class="common-switch__label">{{ label }}</span>
      <span
        v-if="description"
        class="common-switch__description"
      >
        {{ description }}
      </span>
    </span>

    <input
      v-model="model"
      type="checkbox"
      role="switch"
      class="common-switch__input"
    />
    <span
      class="common-switch__track"
      aria-hidden="true"
    >
      <span class="common-switch__thumb" />
    </span>
  </label>
</template>

<style scoped>
.common-switch {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 12px 14px;
  border: 1px solid var(--color-border-default);
  border-radius: 12px;
  background: var(--color-surface);
  cursor: pointer;
}

.common-switch__text {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.common-switch__label {
  color: var(--color-text-title);
  font-size: 0.88rem;
  font-weight: 600;
}

.common-switch__description {
  color: var(--color-text-tertiary);
  font-size: 0.78rem;
}

.common-switch__input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.common-switch__track {
  position: relative;
  flex-shrink: 0;
  width: 44px;
  height: 26px;
  border-radius: 999px;
  background: var(--color-neutral-300);
  transition: background-color 180ms ease;
}

.common-switch__thumb {
  position: absolute;
  top: 3px;
  left: 3px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #ffffff;
  box-shadow: var(--shadow-sm);
  transition: transform 180ms ease;
}

.common-switch__input:checked + .common-switch__track {
  background: var(--color-primary-500);
}

.common-switch__input:checked + .common-switch__track .common-switch__thumb {
  transform: translateX(18px);
}

.common-switch__input:focus-visible + .common-switch__track {
  outline: 2px solid var(--color-primary-300);
  outline-offset: 2px;
}
</style>
