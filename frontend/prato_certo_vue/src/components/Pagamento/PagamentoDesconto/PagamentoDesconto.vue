<script setup>
import { ref } from 'vue';

import CommonTabs from '@/components/Common/CommonTabs/CommonTabs.vue';
import CommonInput from '@/components/Common/CommonInput/CommonInput.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';

import IconPercent from '@/components/Icons/IconPercent.vue';

import { pedidosService } from '@/services/pedidos';
import { useToast } from '@/stores/useToast';

defineOptions({
  name: 'PagamentoDesconto',
});

const props = defineProps({
  pedido: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['atualizado']);

const toast = useToast();

const TIPOS = [
  { value: 'cupom', label: 'Cupom' },
  { value: 'percentual', label: '%' },
  { value: 'valor', label: 'R$' },
];

const tipo = ref('cupom');
const valor = ref('');
const erro = ref('');
const salvando = ref(false);

async function aplicar() {
  erro.value = '';
  if (!String(valor.value).trim()) {
    erro.value = tipo.value === 'cupom' ? 'Informe o código do cupom.' : 'Informe o valor do desconto.';
    return;
  }

  salvando.value = true;
  try {
    const dados =
      tipo.value === 'cupom'
        ? { tipo: 'cupom', codigo: valor.value.trim() }
        : { tipo: tipo.value, valor: String(valor.value).replace(',', '.') };
    const pedido = await pedidosService.aplicarDesconto(props.pedido.id, dados);
    toast.success('Desconto aplicado.');
    valor.value = '';
    emit('atualizado', pedido);
  } catch (error) {
    erro.value = error.mensagem;
  } finally {
    salvando.value = false;
  }
}

async function remover() {
  salvando.value = true;
  try {
    emit('atualizado', await pedidosService.removerDesconto(props.pedido.id));
    toast.info('Desconto removido.');
  } catch (error) {
    toast.error(error.mensagem);
  } finally {
    salvando.value = false;
  }
}
</script>

<template>
  <section class="pagamento-desconto">
    <div class="pagamento-desconto__header">
      <h3 class="pagamento-desconto__title">
        <IconPercent aria-hidden="true" />
        Desconto
      </h3>
      <button
        v-if="pedido.valores.desconto"
        type="button"
        class="pagamento-desconto__remove"
        :disabled="salvando"
        @click="remover"
      >
        Remover desconto
      </button>
    </div>

    <CommonTabs
      v-model="tipo"
      :tabs="TIPOS"
    />

    <form
      class="pagamento-desconto__form"
      novalidate
      @submit.prevent="aplicar"
    >
      <CommonInput
        v-model="valor"
        :placeholder="tipo === 'cupom' ? 'Ex: BEMVINDO10' : tipo === 'percentual' ? 'Ex: 10' : 'Ex: 15,00'"
        :inputmode="tipo === 'cupom' ? 'text' : 'decimal'"
        :error="erro"
        :aria-label="tipo === 'cupom' ? 'Código do cupom' : 'Valor do desconto'"
      />
      <CommonButton
        type="submit"
        variant="secondary"
        :loading="salvando"
      >
        Aplicar
      </CommonButton>
    </form>
  </section>
</template>

<style scoped>
.pagamento-desconto {
  display: grid;
  gap: 12px;
  padding: 16px;
  border: 1px dashed var(--color-border-dark);
  border-radius: 14px;
}

.pagamento-desconto__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.pagamento-desconto__title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.92rem;
}

.pagamento-desconto__title svg {
  width: 16px;
  color: var(--color-primary-500);
}

.pagamento-desconto__remove {
  color: var(--color-error-600);
  font-size: 0.8rem;
  font-weight: 600;
}

.pagamento-desconto__form {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: start;
  gap: 10px;
}

.pagamento-desconto__form :deep(.common-button) {
  min-height: 46px;
}
</style>
