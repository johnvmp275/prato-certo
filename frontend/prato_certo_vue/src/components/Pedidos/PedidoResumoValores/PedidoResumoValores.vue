<script setup>
import { formatarMoeda } from '@/composables/useFormat';

defineOptions({
  name: 'PedidoResumoValores',
});

defineProps({
  valores: {
    type: Object,
    required: true,
  },
  mostrarPagamento: {
    type: Boolean,
    default: true,
  },
});
</script>

<template>
  <dl class="pedido-resumo-valores">
    <div class="pedido-resumo-valores__row">
      <dt>Subtotal</dt>
      <dd class="tabular">{{ formatarMoeda(valores.subtotal) }}</dd>
    </div>
    <div
      v-if="valores.desconto"
      class="pedido-resumo-valores__row is-discount"
    >
      <dt>
        Desconto
        <template v-if="valores.cupom">({{ valores.cupom }})</template>
        <template v-else-if="valores.descontoTipo === 'percentual'">({{ valores.descontoValor }}%)</template>
      </dt>
      <dd class="tabular">- {{ formatarMoeda(valores.desconto) }}</dd>
    </div>
    <div class="pedido-resumo-valores__row">
      <dt>{{ valores.taxaDescricao }}</dt>
      <dd class="tabular">{{ formatarMoeda(valores.taxa) }}</dd>
    </div>
    <div class="pedido-resumo-valores__row is-total">
      <dt>Total</dt>
      <dd class="tabular">{{ formatarMoeda(valores.total) }}</dd>
    </div>
    <template v-if="mostrarPagamento && valores.pago !== undefined">
      <div class="pedido-resumo-valores__row">
        <dt>Pago</dt>
        <dd class="tabular">{{ formatarMoeda(valores.pago) }}</dd>
      </div>
      <div class="pedido-resumo-valores__row is-remaining">
        <dt>Restante</dt>
        <dd class="tabular">{{ formatarMoeda(valores.restante) }}</dd>
      </div>
    </template>
  </dl>
</template>

<style scoped>
.pedido-resumo-valores {
  display: grid;
  gap: 8px;
}

.pedido-resumo-valores__row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  color: var(--color-text-secondary);
  font-size: 0.86rem;
}

.pedido-resumo-valores__row dd {
  color: var(--color-text-primary);
  font-weight: 600;
}

.pedido-resumo-valores__row.is-discount dd {
  color: var(--color-success-600);
}

.pedido-resumo-valores__row.is-total {
  padding: 10px 0;
  border-top: 1px dashed var(--color-border-dark);
  border-bottom: 1px dashed var(--color-border-dark);
  color: var(--color-text-title);
  font-weight: 600;
}

.pedido-resumo-valores__row.is-total dd {
  color: var(--color-text-title);
  font-family: var(--font-title);
  font-size: 1.15rem;
}

.pedido-resumo-valores__row.is-remaining dd {
  color: var(--color-primary-600);
  font-size: 0.95rem;
}
</style>
