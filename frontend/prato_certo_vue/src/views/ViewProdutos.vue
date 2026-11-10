<script setup>
import { computed, onMounted, ref } from 'vue';

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
import CommonBadge from '@/components/Common/CommonBadge/CommonBadge.vue';
import CommonFoodImage from '@/components/Common/CommonFoodImage/CommonFoodImage.vue';
import CommonConfirm from '@/components/Common/CommonConfirm/CommonConfirm.vue';

import ProdutoForm from '@/components/Forms/ProdutoForm/ProdutoForm.vue';

import IconPlus from '@/components/Icons/IconPlus.vue';
import IconPencil from '@/components/Icons/IconPencil.vue';
import IconTrash from '@/components/Icons/IconTrash.vue';
import IconBox from '@/components/Icons/IconBox.vue';

import { produtosService, categoriasService } from '@/services/crud';
import { useListagem } from '@/composables/useListagem';
import { useAuth } from '@/stores/useAuth';
import { useToast } from '@/stores/useToast';
import { formatarMoeda } from '@/composables/useFormat';

defineOptions({
  name: 'ViewProdutos',
});

const auth = useAuth();
const toast = useToast();

const podeGerenciar = computed(() => auth.pode('produtos.gerenciar'));

const { itens, paginacao, carregando, busca, pagina, filtros, carregar } = useListagem(
  produtosService.listar,
  'produtos',
  { status: 'ativo', categoriaId: '' },
);

const categorias = ref([]);

const COLUNAS = computed(() => [
  { key: 'produto', label: 'Produto' },
  { key: 'categoria', label: 'Categoria' },
  { key: 'preco', label: 'Preço', align: 'right' },
  { key: 'estoque', label: 'Estoque', align: 'center' },
  { key: 'status', label: 'Status' },
  ...(podeGerenciar.value ? [{ key: 'acoes', label: '', align: 'right' }] : []),
]);

const STATUS = [
  { value: 'ativo', label: 'Ativos' },
  { value: 'inativo', label: 'Inativos' },
  { value: '', label: 'Todos' },
];

const opcoesCategorias = computed(() => [
  { value: '', label: 'Todas as categorias' },
  ...categorias.value.map(item => ({ value: item.id, label: item.nome })),
]);

const formAberto = ref(false);
const produtoEditar = ref(null);
const produtoInativar = ref(null);

function abrirForm(produto = null) {
  produtoEditar.value = produto;
  formAberto.value = true;
}

function salvo() {
  toast.success('Produto salvo com sucesso.');
  carregar();
}

async function inativar() {
  try {
    await produtosService.inativar(produtoInativar.value.id);
    toast.success('Produto inativado.');
    carregar();
  } catch (error) {
    toast.error(error.mensagem);
  }
}

onMounted(async () => {
  carregar();
  try {
    categorias.value = (await categoriasService.listar()).categorias;
  } catch (error) {
    toast.error(error.mensagem);
  }
});
</script>

<template>
  <main class="view-page row">
    <CommonPageHeader
      title="Produtos"
      subtitle="Itens vendidos no PDV e exibidos no cardápio online."
    >
      <template
        v-if="podeGerenciar"
        #actions
      >
        <CommonButton @click="abrirForm()">
          <template #icon><IconPlus /></template>
          Novo produto
        </CommonButton>
      </template>
    </CommonPageHeader>

    <section class="card">
      <div class="list-toolbar">
        <CommonSearch
          v-model="busca"
          placeholder="Buscar produto..."
        />
        <CommonSelect
          v-model="filtros.categoriaId"
          :options="opcoesCategorias"
          aria-label="Filtrar por categoria"
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
          v-for="produto in itens"
          :key="produto.id"
          class="common-table-row"
        >
          <td class="common-table-cell">
            <span class="view-produtos__product">
              <CommonFoodImage
                :src="produto.imagem"
                :alt="produto.nome"
                :size="44"
              />
              <span class="cell-stack">
                <strong>{{ produto.nome }}</strong>
                <small>{{ produto.descricao || '—' }}</small>
              </span>
            </span>
          </td>
          <td class="common-table-cell">{{ produto.categoria }}</td>
          <td class="common-table-cell align--right tabular">
            <strong>{{ formatarMoeda(produto.preco) }}</strong>
          </td>
          <td class="common-table-cell align--center">
            <CommonBadge
              v-if="produto.controlaEstoque"
              :label="`${produto.estoque} un.`"
              :variant="produto.estoque > 5 ? 'neutral' : produto.estoque > 0 ? 'warning' : 'danger'"
            />
            <span
              v-else
              class="view-produtos__muted"
            >
              Livre
            </span>
          </td>
          <td class="common-table-cell"><CommonStatusBadge :status="produto.status" /></td>
          <td
            v-if="podeGerenciar"
            class="common-table-cell"
          >
            <div class="cell-actions">
              <CommonIconButton
                label="Editar produto"
                @click="abrirForm(produto)"
              >
                <IconPencil />
              </CommonIconButton>
              <CommonIconButton
                v-if="produto.status === 'ativo'"
                label="Inativar produto"
                variant="danger"
                @click="produtoInativar = produto"
              >
                <IconTrash />
              </CommonIconButton>
            </div>
          </td>
        </tr>
      </CommonTable>

      <CommonEmptyState
        v-else
        :icon="IconBox"
        title="Nenhum produto encontrado"
      />

      <CommonPagination
        :paginacao="paginacao"
        item-label="produtos"
        @change="pagina = $event"
      />
    </section>

    <ProdutoForm
      v-if="formAberto"
      :produto="produtoEditar"
      :categorias="categorias"
      @saved="salvo"
      @close="formAberto = false"
    />

    <CommonConfirm
      v-if="produtoInativar"
      title="Inativar produto?"
      :description="`${produtoInativar.nome} deixará de aparecer no PDV e no cardápio. Os pedidos antigos continuam com o produto.`"
      confirm-label="Inativar"
      :action="inativar"
      @close="produtoInativar = null"
    />
  </main>
</template>

<style scoped>
.view-produtos__product {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 240px;
}

.view-produtos__product .cell-stack small {
  max-width: 320px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.view-produtos__muted {
  color: var(--color-text-tertiary);
  font-size: 0.8rem;
}
</style>
