<script setup>
import { onMounted, ref } from 'vue';

import CommonPageHeader from '@/components/Common/CommonPageHeader/CommonPageHeader.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonIconButton from '@/components/Common/CommonIconButton/CommonIconButton.vue';
import CommonSearch from '@/components/Common/CommonSearch/CommonSearch.vue';
import CommonTable from '@/components/Common/CommonTable/CommonTable.vue';
import CommonLoader from '@/components/Common/CommonLoader/CommonLoader.vue';
import CommonEmptyState from '@/components/Common/CommonEmptyState/CommonEmptyState.vue';
import CommonStatusBadge from '@/components/Common/CommonStatusBadge/CommonStatusBadge.vue';
import CommonBadge from '@/components/Common/CommonBadge/CommonBadge.vue';
import CommonConfirm from '@/components/Common/CommonConfirm/CommonConfirm.vue';

import CupomForm from '@/components/Forms/CupomForm/CupomForm.vue';

import IconPlus from '@/components/Icons/IconPlus.vue';
import IconPencil from '@/components/Icons/IconPencil.vue';
import IconTrash from '@/components/Icons/IconTrash.vue';
import IconTicket from '@/components/Icons/IconTicket.vue';

import { cuponsService } from '@/services/crud';
import { useListagem } from '@/composables/useListagem';
import { useToast } from '@/stores/useToast';
import { formatarMoeda, formatarData } from '@/composables/useFormat';

defineOptions({
  name: 'ViewCupons',
});

const toast = useToast();

const { itens, carregando, busca, carregar } = useListagem(cuponsService.listar, 'cupons');

const COLUNAS = [
  { key: 'codigo', label: 'Código' },
  { key: 'desconto', label: 'Desconto' },
  { key: 'validade', label: 'Validade' },
  { key: 'situacao', label: 'Situação' },
  { key: 'status', label: 'Status' },
  { key: 'acoes', label: '', align: 'right' },
];

const formAberto = ref(false);
const cupomEditar = ref(null);
const cupomInativar = ref(null);

function abrirForm(cupom = null) {
  cupomEditar.value = cupom;
  formAberto.value = true;
}

function salvo() {
  toast.success('Cupom salvo com sucesso.');
  carregar();
}

async function inativar() {
  try {
    await cuponsService.inativar(cupomInativar.value.id);
    toast.success('Cupom inativado.');
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
      title="Cupons"
      subtitle="Códigos promocionais aplicados no pagamento."
    >
      <template #actions>
        <CommonButton @click="abrirForm()">
          <template #icon><IconPlus /></template>
          Novo cupom
        </CommonButton>
      </template>
    </CommonPageHeader>

    <section class="card">
      <div class="list-toolbar">
        <CommonSearch
          v-model="busca"
          placeholder="Buscar pelo código..."
        />
      </div>

      <CommonLoader v-if="carregando && !itens.length" />

      <CommonTable
        v-else-if="itens.length"
        :columns="COLUNAS"
      >
        <tr
          v-for="cupom in itens"
          :key="cupom.id"
          class="common-table-row"
        >
          <td class="common-table-cell">
            <code class="view-cupons__code">{{ cupom.codigo }}</code>
          </td>
          <td class="common-table-cell tabular">
            {{ cupom.tipo === 'percentual' ? `${cupom.valor}%` : formatarMoeda(cupom.valor) }}
          </td>
          <td class="common-table-cell">{{ cupom.validade ? formatarData(cupom.validade) : 'Sem validade' }}</td>
          <td class="common-table-cell">
            <CommonBadge
              :label="cupom.valido ? 'Válido' : 'Indisponível'"
              :variant="cupom.valido ? 'success' : 'danger'"
            />
          </td>
          <td class="common-table-cell"><CommonStatusBadge :status="cupom.status" /></td>
          <td class="common-table-cell">
            <div class="cell-actions">
              <CommonIconButton
                label="Editar cupom"
                @click="abrirForm(cupom)"
              >
                <IconPencil />
              </CommonIconButton>
              <CommonIconButton
                v-if="cupom.status === 'ativo'"
                label="Inativar cupom"
                variant="danger"
                @click="cupomInativar = cupom"
              >
                <IconTrash />
              </CommonIconButton>
            </div>
          </td>
        </tr>
      </CommonTable>

      <CommonEmptyState
        v-else
        :icon="IconTicket"
        title="Nenhum cupom cadastrado"
      />
    </section>

    <CupomForm
      v-if="formAberto"
      :cupom="cupomEditar"
      @saved="salvo"
      @close="formAberto = false"
    />

    <CommonConfirm
      v-if="cupomInativar"
      title="Inativar cupom?"
      :description="`O cupom ${cupomInativar.codigo} não poderá mais ser usado.`"
      confirm-label="Inativar"
      :action="inativar"
      @close="cupomInativar = null"
    />
  </main>
</template>

<style scoped>
.view-cupons__code {
  padding: 4px 10px;
  border: 1px dashed var(--color-primary-300);
  border-radius: 8px;
  background: var(--color-primary-50);
  color: var(--color-primary-700);
  font-family: ui-monospace, monospace;
  font-weight: 700;
}
</style>
