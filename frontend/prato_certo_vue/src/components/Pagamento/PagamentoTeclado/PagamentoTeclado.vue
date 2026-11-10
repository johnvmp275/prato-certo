<script setup>
import IconDelete from '@/components/Icons/IconDelete.vue';

defineOptions({
  name: 'PagamentoTeclado',
});

/**
 * O valor é controlado em centavos (string de dígitos), como nas maquininhas:
 * digitar 2, 0, 0, 0, 0 resulta em R$ 200,00.
 */
const centavos = defineModel({ type: String, default: '' });

const TECLAS = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '00', '0', 'apagar'];

function pressionar(tecla) {
  if (tecla === 'apagar') {
    centavos.value = centavos.value.slice(0, -1);
    return;
  }

  // Limite de R$ 999.999,99
  const novo = (centavos.value + tecla).replace(/^0+/, '');
  if (novo.length <= 8) centavos.value = novo;
}
</script>

<template>
  <div
    class="pagamento-teclado"
    role="group"
    aria-label="Teclado numérico"
  >
    <button
      v-for="tecla in TECLAS"
      :key="tecla"
      type="button"
      class="pagamento-teclado__key"
      :class="{ 'is-action': tecla === 'apagar' }"
      :aria-label="tecla === 'apagar' ? 'Apagar último dígito' : tecla"
      @click="pressionar(tecla)"
    >
      <IconDelete
        v-if="tecla === 'apagar'"
        aria-hidden="true"
      />
      <template v-else>{{ tecla }}</template>
    </button>
  </div>
</template>

<style scoped>
.pagamento-teclado {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.pagamento-teclado__key {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 56px;
  border: 1px solid var(--color-border-default);
  border-radius: 14px;
  background: var(--color-surface);
  color: var(--color-text-title);
  font-family: var(--font-title);
  font-size: 1.25rem;
  font-weight: 600;
  transition:
    background-color 120ms ease,
    transform 120ms ease;
}

.pagamento-teclado__key:active {
  transform: scale(0.96);
}

.pagamento-teclado__key.is-action {
  background: var(--color-neutral-50);
  color: var(--color-text-secondary);
}

.pagamento-teclado__key svg {
  width: 22px;
}

@media (hover: hover) and (pointer: fine) {
  .pagamento-teclado__key:hover {
    background: var(--color-primary-50);
  }
}
</style>
