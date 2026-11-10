<script setup>
import { useId, useAttrs, computed } from 'vue';

import { vMaska } from 'maska/vue';

import CommonMessageError from '@/components/Common/CommonMessageError/CommonMessageError.vue';

const uid = useId();

defineOptions({
  name: 'CommonInput',
  inheritAttrs: false,
});

const props = defineProps({
  label: {
    type: String,
    default: '',
  },
  mode: {
    type: String,
    default: 'input',
    validator: value => ['input', 'textarea'].includes(value),
  },
  error: {
    type: String,
    default: '',
  },
  hint: {
    type: String,
    default: '',
  },
  required: {
    type: Boolean,
    default: false,
  },
  mask: {
    type: [String, Array, Object],
    default: null,
  },
});

const model = defineModel({ type: [String, Number], default: '' });

const attributes = useAttrs();
// Repassa os atributos para o input, exceto a classe (que fica no wrapper)
const inputAttributes = computed(() =>
  Object.fromEntries(Object.entries(attributes).filter(([chave]) => chave !== 'class')),
);

const isDisabled = computed(() => {
  const { disabled } = inputAttributes.value;
  return disabled !== undefined && disabled !== false;
});
</script>

<template>
  <div
    class="common-input"
    :class="$attrs.class"
  >
    <label
      v-if="props.label"
      :for="uid"
      class="common-input__label"
    >
      {{ props.label }}
      <span
        v-if="required"
        class="common-input__required"
      >
        *
      </span>
    </label>

    <div
      class="common-input__input-wrapper"
      :class="[
        { error: Boolean(props.error), 'is-disabled': isDisabled },
        mode === 'textarea' ? 'common-input__input-wrapper--textarea' : '',
      ]"
    >
      <span
        v-if="$slots['input-prepend']"
        class="common-input__icon common-input__icon--prepend"
      >
        <slot name="input-prepend" />
      </span>

      <textarea
        v-if="mode === 'textarea'"
        :id="uid"
        v-model="model"
        v-bind="inputAttributes"
        class="common-input__field common-input__field--textarea"
        :aria-invalid="Boolean(props.error)"
      />
      <input
        v-else
        :id="uid"
        v-model="model"
        v-maska="mask ?? undefined"
        v-bind="inputAttributes"
        class="common-input__field"
        :aria-invalid="Boolean(props.error)"
      />

      <span
        v-if="$slots['input-append']"
        class="common-input__icon common-input__icon--append"
      >
        <slot name="input-append" />
      </span>
    </div>

    <p
      v-if="hint && !error"
      class="common-input__hint"
    >
      {{ hint }}
    </p>

    <CommonMessageError :error="error" />
  </div>
</template>

<style scoped>
.common-input {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.common-input__label {
  display: block;
  margin-bottom: 6px;
  color: var(--color-text-primary);
  font-size: 0.82rem;
  font-weight: 600;
}

.common-input__required {
  margin-left: 2px;
  color: var(--color-error-500);
}

.common-input__input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  min-height: 46px;
  padding: 0 14px;
  border: 1px solid var(--color-input-border);
  border-radius: 12px;
  background: var(--color-input-background);
  transition:
    border-color 160ms ease,
    box-shadow 160ms ease;
}

.common-input__input-wrapper--textarea {
  align-items: stretch;
  min-height: 92px;
  padding: 12px 14px;
}

.common-input__input-wrapper:focus-within {
  border-color: var(--color-input-border-focus);
  box-shadow: 0 0 0 3px var(--color-primary-100);
}

.common-input__input-wrapper.error {
  border-color: var(--color-error-500);
  background: var(--color-error-50);
}

.common-input__input-wrapper.is-disabled {
  background: var(--color-neutral-100);
}

.common-input__field {
  flex: 1;
  min-width: 0;
  height: 100%;
  border: none;
  outline: none;
  background: transparent;
  padding: 0;
  color: var(--color-text-title);
  font-size: 0.9rem;
  line-height: 1.5;
}

.common-input__field--textarea {
  resize: vertical;
}

.common-input__field::placeholder {
  color: var(--color-input-placeholder);
}

.common-input__field:disabled {
  color: var(--color-text-tertiary);
  cursor: not-allowed;
}

.common-input__icon {
  display: flex;
  align-items: center;
  flex-shrink: 0;
  color: var(--color-text-tertiary);
}

.common-input__icon--prepend {
  margin-right: 10px;
}

.common-input__icon--append {
  margin-left: 10px;
}

.common-input__icon :slotted(svg) {
  width: 17px;
  height: 17px;
}

.common-input__hint {
  margin-top: 5px;
  color: var(--color-text-tertiary);
  font-size: 0.75rem;
}

@media (hover: hover) and (pointer: fine) {
  .common-input__input-wrapper:not(:focus-within):not(.error):hover {
    border-color: var(--color-input-border-hover);
  }
}
</style>
