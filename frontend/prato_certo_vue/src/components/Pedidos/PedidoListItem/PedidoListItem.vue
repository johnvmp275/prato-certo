<script setup>
import CommonStatusBadge from '@/components/Common/CommonStatusBadge/CommonStatusBadge.vue';

import { formatarMoeda, formatarHora } from '@/composables/useFormat';

defineOptions({
  name: 'PedidoListItem',
});

defineProps({
  pedido: {
    type: Object,
    required: true,
  },
  active: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['select']);

// Nome exibido: cliente, contato do pedido online ou origem
function nomeExibido(pedido) {
  return pedido.cliente?.nome ?? pedido.contato?.nome ?? 'Cliente não identificado';
}
</script>

<template>
  <button
    type="button"
    class="pedido-list-item"
    :class="{ 'is-active': active }"
    @click="emit('select', pedido)"
  >
    <span class="pedido-list-item__main">
      <span class="pedido-list-item__code">#{{ pedido.codigo }}</span>
      <span class="pedido-list-item__origin">{{ pedido.origem }} · {{ nomeExibido(pedido) }}</span>
    </span>

    <span class="pedido-list-item__meta">
      <span class="pedido-list-item__total tabular">{{ formatarMoeda(pedido.valores.total) }}</span>
      <span class="pedido-list-item__time">{{ formatarHora(pedido.criadoEm) }}</span>
    </span>

    <CommonStatusBadge
      class="pedido-list-item__status"
      :status="pedido.status"
    />
  </button>
</template>

<style scoped>
.pedido-list-item {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 8px 12px;
  width: 100%;
  padding: 14px 16px;
  border: 1px solid transparent;
  border-radius: 14px;
  text-align: left;
  transition:
    background-color 160ms ease,
    border-color 160ms ease;
}

.pedido-list-item.is-active {
  border-color: var(--color-primary-300);
  background: var(--color-primary-50);
}

.pedido-list-item__main,
.pedido-list-item__meta {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.pedido-list-item__meta {
  align-items: flex-end;
}

.pedido-list-item__code {
  color: var(--color-text-title);
  font-weight: 700;
}

.pedido-list-item__origin {
  overflow: hidden;
  color: var(--color-text-secondary);
  font-size: 0.8rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.pedido-list-item__total {
  color: var(--color-text-title);
  font-weight: 700;
}

.pedido-list-item__time {
  color: var(--color-text-tertiary);
  font-size: 0.76rem;
}

.pedido-list-item__status {
  grid-column: 1 / -1;
  justify-self: start;
}

@media (hover: hover) and (pointer: fine) {
  .pedido-list-item:not(.is-active):hover {
    background: var(--color-neutral-50);
  }
}
</style>
