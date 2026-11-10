<script setup>
import { onMounted, ref } from 'vue';

import CommonDialog from '@/components/Common/CommonDialog/CommonDialog.vue';
import CommonLoader from '@/components/Common/CommonLoader/CommonLoader.vue';
import CommonEmptyState from '@/components/Common/CommonEmptyState/CommonEmptyState.vue';
import CommonStatusBadge from '@/components/Common/CommonStatusBadge/CommonStatusBadge.vue';

import IconReceipt from '@/components/Icons/IconReceipt.vue';

import { clientesService } from '@/services/crud';
import { useToast } from '@/stores/useToast';
import { formatarMoeda, formatarDataHora } from '@/composables/useFormat';

defineOptions({
  name: 'ClienteHistoricoDialog',
});

const props = defineProps({
  cliente: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['close']);

const toast = useToast();
const historico = ref([]);
const carregando = ref(true);

onMounted(async () => {
  try {
    historico.value = (await clientesService.buscar(props.cliente.id)).historico;
  } catch (error) {
    toast.error(error.mensagem);
  } finally {
    carregando.value = false;
  }
});
</script>

<template>
  <CommonDialog
    :title="`Histórico de ${cliente.nome}`"
    description="Últimos 20 pedidos do cliente."
    :width="560"
    @close="emit('close')"
  >
    <CommonLoader v-if="carregando" />
    <ul
      v-else-if="historico.length"
      class="cliente-historico"
    >
      <li
        v-for="pedido in historico"
        :key="pedido.id"
      >
        <span class="cell-stack">
          <strong>#{{ pedido.codigo }} · {{ pedido.origem }}</strong>
          <small>{{ formatarDataHora(pedido.criadoEm) }} · {{ pedido.quantidadeItens }} itens</small>
        </span>
        <span class="cliente-historico__right">
          <strong class="tabular">{{ formatarMoeda(pedido.valores.total) }}</strong>
          <CommonStatusBadge :status="pedido.status" />
        </span>
      </li>
    </ul>
    <CommonEmptyState
      v-else
      :icon="IconReceipt"
      title="Nenhum pedido ainda"
      description="Vincule o cliente a um pedido no PDV."
    />
  </CommonDialog>
</template>

<style scoped>
.cliente-historico {
  display: grid;
  gap: 4px;
}

.cliente-historico li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid var(--color-border-light);
}

.cliente-historico__right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}
</style>
