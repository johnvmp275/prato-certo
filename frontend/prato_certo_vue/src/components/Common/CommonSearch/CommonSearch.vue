<script setup>
import { ref, watch } from 'vue';

import IconSearch from '@/components/Icons/IconSearch.vue';
import IconX from '@/components/Icons/IconX.vue';

defineOptions({
  name: 'CommonSearch',
});

const props = defineProps({
  placeholder: {
    type: String,
    default: 'Buscar...',
  },
  debounce: {
    type: Number,
    default: 350,
  },
});

const model = defineModel({ type: String, default: '' });

// Valor digitado (o model só é atualizado depois do debounce)
const texto = ref(model.value);
let timer = null;

watch(texto, valor => {
  clearTimeout(timer);
  timer = setTimeout(() => {
    model.value = valor.trim();
  }, props.debounce);
});

watch(model, valor => {
  if (valor !== texto.value.trim()) texto.value = valor;
});

function limpar() {
  texto.value = '';
  model.value = '';
}
</script>

<template>
  <label class="common-search">
    <IconSearch
      class="common-search__icon"
      aria-hidden="true"
    />
    <span class="sr-only">{{ placeholder }}</span>
    <input
      v-model="texto"
      type="search"
      class="common-search__field"
      :placeholder="placeholder"
    />
    <button
      v-if="texto"
      type="button"
      class="common-search__clear"
      aria-label="Limpar busca"
      @click="limpar"
    >
      <IconX aria-hidden="true" />
    </button>
  </label>
</template>

<style scoped>
.common-search {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
  min-height: 44px;
  padding: 0 12px 0 40px;
  border: 1px solid var(--color-input-border);
  border-radius: 12px;
  background: var(--color-surface);
  transition:
    border-color 160ms ease,
    box-shadow 160ms ease;
}

.common-search:focus-within {
  border-color: var(--color-input-border-focus);
  box-shadow: 0 0 0 3px var(--color-primary-100);
}

.common-search__icon {
  position: absolute;
  left: 14px;
  width: 17px;
  color: var(--color-text-tertiary);
}

.common-search__field {
  flex: 1;
  min-width: 0;
  height: 42px;
  color: var(--color-text-title);
  font-size: 0.88rem;
}

.common-search__field::placeholder {
  color: var(--color-input-placeholder);
}

.common-search__field::-webkit-search-cancel-button {
  display: none;
}

.common-search__clear {
  display: flex;
  width: 18px;
  color: var(--color-text-tertiary);
}
</style>
