<script setup>
import { useId } from 'vue';

import CommonMessageError from '@/components/Common/CommonMessageError/CommonMessageError.vue';

import IconChevronDown from '@/components/Icons/IconChevronDown.vue';

const uid = useId();

defineOptions({
  name: 'CommonSelect',
  inheritAttrs: false,
});

defineProps({
  label: {
    type: String,
    default: '',
  },
  options: {
    type: Array,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: '',
  },
  error: {
    type: String,
    default: '',
  },
  required: {
    type: Boolean,
    default: false,
  },
});

const model = defineModel({ type: [String, Number], default: '' });
</script>

<template>
  <div
    class="common-select"
    :class="$attrs.class"
  >
    <label
      v-if="label"
      :for="uid"
      class="common-select__label"
    >
      {{ label }}
      <span
        v-if="required"
        class="common-select__required"
      >
        *
      </span>
    </label>

    <div
      class="common-select__wrapper"
      :class="{ error: Boolean(error) }"
    >
      <select
        :id="uid"
        v-model="model"
        class="common-select__field"
        :aria-invalid="Boolean(error)"
      >
        <option
          v-if="placeholder"
          value=""
          disabled
        >
          {{ placeholder }}
        </option>
        <option
          v-for="option in options"
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select>
      <IconChevronDown
        class="common-select__chevron"
        aria-hidden="true"
      />
    </div>

    <CommonMessageError :error="error" />
  </div>
</template>

<style scoped>
.common-select {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.common-select__label {
  margin-bottom: 6px;
  color: var(--color-text-primary);
  font-size: 0.82rem;
  font-weight: 600;
}

.common-select__required {
  margin-left: 2px;
  color: var(--color-error-500);
}

.common-select__wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.common-select__field {
  appearance: none;
  width: 100%;
  min-height: 46px;
  padding: 0 40px 0 14px;
  border: 1px solid var(--color-input-border);
  border-radius: 12px;
  background: var(--color-input-background);
  color: var(--color-text-title);
  font-size: 0.9rem;
  cursor: pointer;
  transition:
    border-color 160ms ease,
    box-shadow 160ms ease;
}

.common-select__field:focus {
  border-color: var(--color-input-border-focus);
  box-shadow: 0 0 0 3px var(--color-primary-100);
}

.common-select__wrapper.error .common-select__field {
  border-color: var(--color-error-500);
  background: var(--color-error-50);
}

.common-select__chevron {
  position: absolute;
  right: 14px;
  width: 16px;
  color: var(--color-text-tertiary);
  pointer-events: none;
}
</style>
