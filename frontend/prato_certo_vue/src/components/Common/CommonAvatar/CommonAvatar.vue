<script setup>
import { computed } from 'vue';

import { iniciais } from '@/composables/useFormat';

defineOptions({
  name: 'CommonAvatar',
});

const props = defineProps({
  name: {
    type: String,
    default: '',
  },
  size: {
    type: Number,
    default: 38,
  },
});

const texto = computed(() => iniciais(props.name) || '?');
</script>

<template>
  <span
    class="common-avatar"
    :style="{ width: `${size}px`, height: `${size}px`, fontSize: `${size * 0.36}px` }"
    aria-hidden="true"
  >
    {{ texto }}
  </span>
</template>

<style scoped>
.common-avatar {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--color-primary-100), var(--color-primary-200));
  color: var(--color-primary-700);
  font-weight: 700;
}
</style>
