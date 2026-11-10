<script setup>
import { computed, ref } from 'vue';

import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonAvatar from '@/components/Common/CommonAvatar/CommonAvatar.vue';
import CommonStatusBadge from '@/components/Common/CommonStatusBadge/CommonStatusBadge.vue';
import CommonEmptyState from '@/components/Common/CommonEmptyState/CommonEmptyState.vue';

import PdvCartItem from '@/components/Pdv/PdvCartItem/PdvCartItem.vue';
import PdvClienteDialog from '@/components/Pdv/PdvClienteDialog/PdvClienteDialog.vue';

import IconUserPlus from '@/components/Icons/IconUserPlus.vue';
import IconX from '@/components/Icons/IconX.vue';
import IconPause from '@/components/Icons/IconPause.vue';
import IconCheck from '@/components/Icons/IconCheck.vue';
import IconShoppingBag from '@/components/Icons/IconShoppingBag.vue';
import IconPlus from '@/components/Icons/IconPlus.vue';

import { usePdv } from '@/stores/usePdv';
import { formatarMoeda, formatarTelefone } from '@/composables/useFormat';

defineOptions({
  name: 'PdvCart',
});

defineProps({
  processando: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['espera', 'salvar', 'pagar', 'novo']);

const pdv = usePdv();
const mostrarClientes = ref(false);

// Valores: pedido salvo usa os valores calculados pelo servidor (desconto/taxas)
const valores = computed(() => {
  if (pdv.pedido && !pdv.alterado) return pdv.pedido.valores;
  return { subtotal: pdv.subtotal, desconto: 0, taxa: null, total: pdv.subtotal };
});

const titulo = computed(() => (pdv.pedido ? `Pedido #${pdv.pedido.codigo}` : 'Novo pedido'));
const origem = computed(() => pdv.pedido?.origem ?? 'Balcão');
</script>

<template>
  <section class="pdv-cart">
    <header class="pdv-cart__header">
      <div class="pdv-cart__heading">
        <div>
          <h2 class="pdv-cart__title">{{ titulo }}</h2>
          <p class="pdv-cart__origin">
            {{ origem }}
            <template v-if="pdv.pedido?.numPessoas"> · {{ pdv.pedido.numPessoas }} pessoas</template>
          </p>
        </div>
        <CommonStatusBadge
          v-if="pdv.pedido"
          :status="pdv.pedido.status"
        />
        <button
          v-else-if="pdv.itens.length"
          type="button"
          class="pdv-cart__clear"
          @click="emit('novo')"
        >
          Limpar
        </button>
      </div>

      <!-- CLIENTE DO PEDIDO -->
      <div
        v-if="pdv.cliente"
        class="pdv-cart__customer"
      >
        <CommonAvatar
          :name="pdv.cliente.nome"
          :size="36"
        />
        <span class="pdv-cart__customer-info">
          <span class="pdv-cart__customer-name">{{ pdv.cliente.nome }}</span>
          <span class="pdv-cart__customer-phone">{{ formatarTelefone(pdv.cliente.telefone) }}</span>
        </span>
        <button
          type="button"
          class="pdv-cart__customer-remove"
          aria-label="Remover cliente do pedido"
          @click="pdv.definirCliente(null)"
        >
          <IconX aria-hidden="true" />
        </button>
      </div>
      <button
        v-else
        type="button"
        class="pdv-cart__add-customer"
        @click="mostrarClientes = true"
      >
        <IconUserPlus aria-hidden="true" />
        Adicionar cliente
      </button>
    </header>

    <!-- ITENS -->
    <div class="pdv-cart__items">
      <ul v-if="pdv.itens.length">
        <PdvCartItem
          v-for="(item, index) in pdv.itens"
          :key="`${item.produtoId}-${index}`"
          :item="item"
          :index="index"
          @quantidade="pdv.alterarQuantidade"
          @observacao="pdv.alterarObservacao"
          @remover="pdv.remover"
        />
      </ul>
      <CommonEmptyState
        v-else
        :icon="IconShoppingBag"
        title="Carrinho vazio"
        description="Toque nos produtos para adicioná-los ao pedido."
      />
    </div>

    <!-- OBSERVAÇÃO GERAL -->
    <textarea
      v-model="pdv.observacao"
      class="pdv-cart__note"
      rows="2"
      maxlength="255"
      placeholder="Observação do pedido (opcional)"
      aria-label="Observação do pedido"
      @input="pdv.alterado = true"
    />

    <!-- RESUMO -->
    <dl class="pdv-cart__summary">
      <div class="pdv-cart__summary-row">
        <dt>Subtotal ({{ pdv.quantidadeItens }} itens)</dt>
        <dd class="tabular">{{ formatarMoeda(valores.subtotal) }}</dd>
      </div>
      <div
        v-if="valores.desconto"
        class="pdv-cart__summary-row is-discount"
      >
        <dt>Desconto</dt>
        <dd class="tabular">- {{ formatarMoeda(valores.desconto) }}</dd>
      </div>
      <div class="pdv-cart__summary-row">
        <dt>{{ valores.taxaDescricao ?? 'Taxas' }}</dt>
        <dd class="tabular">{{ valores.taxa === null ? 'no pagamento' : formatarMoeda(valores.taxa) }}</dd>
      </div>
      <div class="pdv-cart__summary-row is-total">
        <dt>Total a pagar</dt>
        <dd class="tabular">{{ formatarMoeda(valores.total) }}</dd>
      </div>
    </dl>

    <!-- AÇÕES -->
    <div class="pdv-cart__actions">
      <CommonButton
        variant="soft"
        :disabled="!pdv.itens.length || pdv.pedido?.status === 'pendente'"
        :loading="processando === 'espera'"
        @click="emit('espera')"
      >
        <template #icon><IconPause /></template>
        Em espera
      </CommonButton>
      <CommonButton
        variant="success"
        :disabled="!pdv.itens.length || pdv.pedido?.status === 'pendente'"
        :loading="processando === 'pagar'"
        @click="emit('pagar')"
      >
        <template #icon><IconCheck /></template>
        Pagar
      </CommonButton>
    </div>

    <div
      v-if="pdv.pedido"
      class="pdv-cart__secondary"
    >
      <CommonButton
        variant="ghost"
        size="sm"
        :disabled="!pdv.alterado"
        :loading="processando === 'salvar'"
        @click="emit('salvar')"
      >
        Salvar alterações
      </CommonButton>
      <CommonButton
        variant="ghost"
        size="sm"
        @click="emit('novo')"
      >
        <template #icon><IconPlus /></template>
        Novo pedido
      </CommonButton>
    </div>

    <PdvClienteDialog
      v-if="mostrarClientes"
      @select="pdv.definirCliente"
      @close="mostrarClientes = false"
    />
  </section>
</template>

<style scoped>
.pdv-cart {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--color-surface);
}

.pdv-cart__header {
  display: grid;
  gap: 14px;
  padding: 20px 20px 14px;
  border-bottom: 1px solid var(--color-border-light);
}

.pdv-cart__heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.pdv-cart__title {
  font-size: 1.05rem;
}

.pdv-cart__origin {
  color: var(--color-text-tertiary);
  font-size: 0.8rem;
}

.pdv-cart__clear {
  color: var(--color-error-600);
  font-size: 0.8rem;
  font-weight: 600;
}

.pdv-cart__customer {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 12px;
  background: var(--color-neutral-50);
}

.pdv-cart__customer-info {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: 0;
}

.pdv-cart__customer-name {
  color: var(--color-text-title);
  font-size: 0.88rem;
  font-weight: 600;
}

.pdv-cart__customer-phone {
  color: var(--color-text-tertiary);
  font-size: 0.76rem;
}

.pdv-cart__customer-remove {
  width: 18px;
  color: var(--color-text-tertiary);
}

.pdv-cart__add-customer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 42px;
  border: 1.5px dashed var(--color-primary-300);
  border-radius: 12px;
  color: var(--color-primary-600);
  font-size: 0.86rem;
  font-weight: 600;
}

.pdv-cart__add-customer svg {
  width: 17px;
}

.pdv-cart__items {
  flex: 1;
  min-height: 120px;
  overflow-y: auto;
  padding: 4px 20px;
}

.pdv-cart__note {
  margin: 0 20px 12px;
  padding: 10px 12px;
  border: 1px solid var(--color-input-border);
  border-radius: 12px;
  font-size: 0.82rem;
  resize: none;
}

.pdv-cart__note:focus {
  border-color: var(--color-input-border-focus);
}

.pdv-cart__summary {
  display: grid;
  gap: 8px;
  margin: 0 20px;
  padding: 14px 16px;
  border-radius: 14px;
  background: var(--color-neutral-50);
}

.pdv-cart__summary-row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  color: var(--color-text-secondary);
  font-size: 0.84rem;
}

.pdv-cart__summary-row dd {
  color: var(--color-text-primary);
  font-weight: 600;
}

.pdv-cart__summary-row.is-discount dd {
  color: var(--color-success-600);
}

.pdv-cart__summary-row.is-total {
  margin-top: 4px;
  padding-top: 10px;
  border-top: 1px dashed var(--color-border-dark);
  color: var(--color-text-title);
  font-size: 0.95rem;
  font-weight: 600;
}

.pdv-cart__summary-row.is-total dd {
  color: var(--color-primary-600);
  font-family: var(--font-title);
  font-size: 1.2rem;
}

.pdv-cart__actions {
  display: grid;
  grid-template-columns: 1fr 1.4fr;
  gap: 10px;
  padding: 16px 20px 8px;
}

.pdv-cart__actions :deep(.common-button) {
  min-height: 50px;
}

.pdv-cart__secondary {
  display: flex;
  justify-content: space-between;
  padding: 0 14px 12px;
}
</style>
