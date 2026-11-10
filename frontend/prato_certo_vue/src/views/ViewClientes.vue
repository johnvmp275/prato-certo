<script setup>
import { onMounted, ref } from 'vue';

import CommonPageHeader from '@/components/Common/CommonPageHeader/CommonPageHeader.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonIconButton from '@/components/Common/CommonIconButton/CommonIconButton.vue';
import CommonSearch from '@/components/Common/CommonSearch/CommonSearch.vue';
import CommonSelect from '@/components/Common/CommonSelect/CommonSelect.vue';
import CommonTable from '@/components/Common/CommonTable/CommonTable.vue';
import CommonPagination from '@/components/Common/CommonPagination/CommonPagination.vue';
import CommonLoader from '@/components/Common/CommonLoader/CommonLoader.vue';
import CommonEmptyState from '@/components/Common/CommonEmptyState/CommonEmptyState.vue';
import CommonStatusBadge from '@/components/Common/CommonStatusBadge/CommonStatusBadge.vue';
import CommonAvatar from '@/components/Common/CommonAvatar/CommonAvatar.vue';
import CommonConfirm from '@/components/Common/CommonConfirm/CommonConfirm.vue';

import ClienteForm from '@/components/Forms/ClienteForm/ClienteForm.vue';
import ClienteHistoricoDialog from '@/components/Pedidos/ClienteHistoricoDialog/ClienteHistoricoDialog.vue';

import IconUserPlus from '@/components/Icons/IconUserPlus.vue';
import IconPencil from '@/components/Icons/IconPencil.vue';
import IconTrash from '@/components/Icons/IconTrash.vue';
import IconReceipt from '@/components/Icons/IconReceipt.vue';
import IconUsers from '@/components/Icons/IconUsers.vue';

import { clientesService } from '@/services/crud';
import { useListagem } from '@/composables/useListagem';
import { useToast } from '@/stores/useToast';
import { formatarMoeda, formatarTelefone } from '@/composables/useFormat';

defineOptions({
  name: 'ViewClientes',
});

const toast = useToast();

const { itens, paginacao, carregando, busca, pagina, filtros, carregar } = useListagem(
  clientesService.listar,
  'clientes',
  { status: 'ativo' },
);

const COLUNAS = [
  { key: 'nome', label: 'Cliente' },
  { key: 'contato', label: 'Contato' },
  { key: 'pedidos', label: 'Pedidos', align: 'center' },
  { key: 'gasto', label: 'Total gasto', align: 'right' },
  { key: 'status', label: 'Status' },
  { key: 'acoes', label: '', align: 'right' },
];

const STATUS = [
  { value: 'ativo', label: 'Ativos' },
  { value: 'inativo', label: 'Inativos' },
  { value: '', label: 'Todos' },
];

const formAberto = ref(false);
const clienteEditar = ref(null);
const clienteHistorico = ref(null);
const clienteInativar = ref(null);

function abrirForm(cliente = null) {
  clienteEditar.value = cliente;
  formAberto.value = true;
}

function salvo() {
  toast.success('Cliente salvo com sucesso.');
  carregar();
}

async function inativar() {
  try {
    await clientesService.inativar(clienteInativar.value.id);
    toast.success('Cliente inativado.');
    carregar();
  } catch (error) {
    toast.error(error.mensagem);
  }
}

onMounted(carregar);
</script>

<template>
  <main class="view-page row">
    <CommonPageHeader
      title="Clientes"
      subtitle="Cadastro de clientes e histórico de consumo."
    >
      <template #actions>
        <CommonButton @click="abrirForm()">
          <template #icon><IconUserPlus /></template>
          Novo cliente
        </CommonButton>
      </template>
    </CommonPageHeader>

    <section class="card">
      <div class="list-toolbar">
        <CommonSearch
          v-model="busca"
          placeholder="Buscar por nome, telefone, e-mail ou CPF..."
        />
        <CommonSelect
          v-model="filtros.status"
          :options="STATUS"
          aria-label="Filtrar por status"
        />
      </div>

      <CommonLoader v-if="carregando && !itens.length" />

      <CommonTable
        v-else-if="itens.length"
        :columns="COLUNAS"
      >
        <tr
          v-for="cliente in itens"
          :key="cliente.id"
          class="common-table-row"
        >
          <td class="common-table-cell">
            <span class="view-clientes__name">
              <CommonAvatar
                :name="cliente.nome"
                :size="34"
              />
              <span class="cell-stack">
                <strong>{{ cliente.nome }}</strong>
                <small>{{ cliente.email || 'Sem e-mail' }}</small>
              </span>
            </span>
          </td>
          <td class="common-table-cell">{{ formatarTelefone(cliente.telefone) }}</td>
          <td class="common-table-cell align--center tabular">{{ cliente.totalPedidos }}</td>
          <td class="common-table-cell align--right tabular">{{ formatarMoeda(cliente.totalGasto) }}</td>
          <td class="common-table-cell"><CommonStatusBadge :status="cliente.status" /></td>
          <td class="common-table-cell">
            <div class="cell-actions">
              <CommonIconButton
                label="Histórico de pedidos"
                @click="clienteHistorico = cliente"
              >
                <IconReceipt />
              </CommonIconButton>
              <CommonIconButton
                label="Editar cliente"
                @click="abrirForm(cliente)"
              >
                <IconPencil />
              </CommonIconButton>
              <CommonIconButton
                v-if="cliente.status === 'ativo'"
                label="Inativar cliente"
                variant="danger"
                @click="clienteInativar = cliente"
              >
                <IconTrash />
              </CommonIconButton>
            </div>
          </td>
        </tr>
      </CommonTable>

      <CommonEmptyState
        v-else
        :icon="IconUsers"
        title="Nenhum cliente encontrado"
        description="Ajuste a busca ou cadastre um novo cliente."
      />

      <CommonPagination
        :paginacao="paginacao"
        item-label="clientes"
        @change="pagina = $event"
      />
    </section>

    <ClienteForm
      v-if="formAberto"
      :cliente="clienteEditar"
      @saved="salvo"
      @close="formAberto = false"
    />

    <ClienteHistoricoDialog
      v-if="clienteHistorico"
      :cliente="clienteHistorico"
      @close="clienteHistorico = null"
    />

    <CommonConfirm
      v-if="clienteInativar"
      title="Inativar cliente?"
      :description="`${clienteInativar.nome} não aparecerá mais nas buscas do PDV. O histórico de pedidos é mantido.`"
      confirm-label="Inativar"
      :action="inativar"
      @close="clienteInativar = null"
    />
  </main>
</template>

<style scoped>
.view-clientes__name {
  display: flex;
  align-items: center;
  gap: 10px;
}
</style>
