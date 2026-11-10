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
import CommonConfirm from '@/components/Common/CommonConfirm/CommonConfirm.vue';

import CategoriaForm from '@/components/Forms/CategoriaForm/CategoriaForm.vue';

import IconPlus from '@/components/Icons/IconPlus.vue';
import IconPencil from '@/components/Icons/IconPencil.vue';
import IconTrash from '@/components/Icons/IconTrash.vue';
import IconTag from '@/components/Icons/IconTag.vue';

import { categoriasService } from '@/services/crud';
import { useListagem } from '@/composables/useListagem';
import { useToast } from '@/stores/useToast';

defineOptions({
  name: 'ViewCategorias',
});

const toast = useToast();

const { itens, carregando, busca, carregar } = useListagem(categoriasService.listar, 'categorias');

const COLUNAS = [
  { key: 'ordem', label: 'Ordem', align: 'center' },
  { key: 'nome', label: 'Categoria' },
  { key: 'produtos', label: 'Produtos ativos', align: 'center' },
  { key: 'status', label: 'Status' },
  { key: 'acoes', label: '', align: 'right' },
];

const formAberto = ref(false);
const categoriaEditar = ref(null);
const categoriaInativar = ref(null);

function abrirForm(categoria = null) {
  categoriaEditar.value = categoria;
  formAberto.value = true;
}

function salvo() {
  toast.success('Categoria salva com sucesso.');
  carregar();
}

async function inativar() {
  try {
    await categoriasService.inativar(categoriaInativar.value.id);
    toast.success('Categoria inativada.');
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
      title="Categorias"
      subtitle="Organizam os produtos no PDV e no cardápio online."
    >
      <template #actions>
        <CommonButton @click="abrirForm()">
          <template #icon><IconPlus /></template>
          Nova categoria
        </CommonButton>
      </template>
    </CommonPageHeader>

    <section class="card">
      <div class="list-toolbar">
        <CommonSearch
          v-model="busca"
          placeholder="Buscar categoria..."
        />
      </div>

      <CommonLoader v-if="carregando && !itens.length" />

      <CommonTable
        v-else-if="itens.length"
        :columns="COLUNAS"
      >
        <tr
          v-for="categoria in itens"
          :key="categoria.id"
          class="common-table-row"
        >
          <td class="common-table-cell align--center tabular">{{ categoria.ordem }}</td>
          <td class="common-table-cell">
            <strong>{{ categoria.nome }}</strong>
          </td>
          <td class="common-table-cell align--center tabular">{{ categoria.quantidadeProdutos }}</td>
          <td class="common-table-cell"><CommonStatusBadge :status="categoria.status" /></td>
          <td class="common-table-cell">
            <div class="cell-actions">
              <CommonIconButton
                label="Editar categoria"
                @click="abrirForm(categoria)"
              >
                <IconPencil />
              </CommonIconButton>
              <CommonIconButton
                v-if="categoria.status === 'ativo'"
                label="Inativar categoria"
                variant="danger"
                @click="categoriaInativar = categoria"
              >
                <IconTrash />
              </CommonIconButton>
            </div>
          </td>
        </tr>
      </CommonTable>

      <CommonEmptyState
        v-else
        :icon="IconTag"
        title="Nenhuma categoria encontrada"
      />
    </section>

    <CategoriaForm
      v-if="formAberto"
      :categoria="categoriaEditar"
      @saved="salvo"
      @close="formAberto = false"
    />

    <CommonConfirm
      v-if="categoriaInativar"
      title="Inativar categoria?"
      :description="`A categoria ${categoriaInativar.nome} sairá do PDV e do cardápio. Os produtos continuam cadastrados.`"
      confirm-label="Inativar"
      :action="inativar"
      @close="categoriaInativar = null"
    />
  </main>
</template>
