<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

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
import IconGrip from '@/components/Icons/IconGrip.vue';

import { categoriasService } from '@/services/crud';
import { useListagem } from '@/composables/useListagem';
import { useToast } from '@/stores/useToast';

defineOptions({
  name: 'ViewCategorias',
});

const toast = useToast();

const { itens, carregando, busca, carregar } = useListagem(categoriasService.listar, 'categorias');

const COLUNAS = [
  { key: 'arrastar', label: '' },
  { key: 'ordem', label: 'Posição', align: 'center' },
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

/* ===========================
 * ARRASTAR E SOLTAR PARA ESCOLHER A POSIÇÃO
 * Feito com pointer events (funciona com mouse, dedo e caneta) e com as setas do teclado na alça
 * =========================== */
const tabelaRef = ref(null);
const arrastandoId = ref(null);
const salvandoOrdem = ref(false);
let ordemAntes = [];

// Com busca ativa a lista está incompleta, então a ordem não pode ser trocada
const podeReordenar = computed(() => !busca.value && itens.value.length > 1);

function moverItem(de, para) {
  if (de === para || para < 0 || para >= itens.value.length) return false;
  const lista = [...itens.value];
  const [item] = lista.splice(de, 1);
  lista.splice(para, 0, item);
  itens.value = lista;
  return true;
}

function iniciarArraste(event, categoria) {
  if (!podeReordenar.value || salvandoOrdem.value) return;
  // Não deixa a tabela rolar para o lado nem selecionar texto enquanto arrasta
  event.preventDefault();
  event.stopPropagation();

  arrastandoId.value = categoria.id;
  ordemAntes = itens.value.map(item => item.id);

  // Escuta na janela (e não na alça): ao trocar de posição o Vue move a linha no DOM e a alça perderia o ponteiro
  window.addEventListener('pointermove', arrastar);
  window.addEventListener('pointerup', soltar);
  window.addEventListener('pointercancel', soltar);
}

function pararDeEscutar() {
  window.removeEventListener('pointermove', arrastar);
  window.removeEventListener('pointerup', soltar);
  window.removeEventListener('pointercancel', soltar);
}

function arrastar(event) {
  if (!arrastandoId.value) return;

  // Nova posição = quantas outras linhas têm o meio acima do ponteiro
  const linhas = [...tabelaRef.value.querySelectorAll('[data-categoria-id]')];
  const outras = linhas.filter(linha => Number(linha.dataset.categoriaId) !== arrastandoId.value);
  const destino = outras.filter(linha => {
    const { top, height } = linha.getBoundingClientRect();
    return top + height / 2 < event.clientY;
  }).length;

  const atual = itens.value.findIndex(item => item.id === arrastandoId.value);
  moverItem(atual, destino);
}

function soltar() {
  pararDeEscutar();
  if (!arrastandoId.value) return;
  arrastandoId.value = null;

  // Só salva se a ordem mudou de verdade
  const mudou = itens.value.some((item, index) => item.id !== ordemAntes[index]);
  if (mudou) salvarOrdem(ordemAntes);
}

// Teclado: setas para cima/baixo com a alça em foco
function moverComTeclado(event, index) {
  if (!podeReordenar.value || salvandoOrdem.value) return;
  const anterior = itens.value.map(item => item.id);
  if (moverItem(index, index + (event.key === 'ArrowUp' ? -1 : 1))) {
    salvarOrdem(anterior);
    // Mantém o foco na alça da categoria que foi movida
    nextTick(() => tabelaRef.value.querySelectorAll('.view-categorias__grip')[event.key === 'ArrowUp' ? index - 1 : index + 1]?.focus());
  }
}

async function salvarOrdem(idsAnteriores) {
  salvandoOrdem.value = true;
  try {
    await categoriasService.reordenar(itens.value.map(item => item.id));
    // Atualiza as posições exibidas sem recarregar a lista
    itens.value = itens.value.map((item, index) => ({ ...item, ordem: index + 1 }));
    toast.success('Ordem das categorias salva. O PDV e o cardápio já mostram assim.');
  } catch (error) {
    // Volta para a ordem anterior se a API recusar
    itens.value = idsAnteriores.map(id => itens.value.find(item => item.id === id));
    toast.error(error.mensagem);
  } finally {
    salvandoOrdem.value = false;
  }
}

onMounted(carregar);
onBeforeUnmount(pararDeEscutar);
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

      <p
        v-if="itens.length > 1"
        class="view-categorias__hint"
      >
        {{
          busca
            ? 'Limpe a busca para mudar a ordem das categorias.'
            : 'Arraste pela alça ⋮⋮ (ou use as setas ↑ ↓ nela) para escolher a posição no PDV e no cardápio.'
        }}
      </p>

      <CommonLoader v-if="carregando && !itens.length" />

      <div
        v-else-if="itens.length"
        ref="tabelaRef"
        :class="{ 'is-dragging': arrastandoId, 'is-saving': salvandoOrdem }"
      >
        <CommonTable :columns="COLUNAS">
          <tr
            v-for="(categoria, index) in itens"
            :key="categoria.id"
            class="common-table-row"
            :class="{ 'is-dragged': arrastandoId === categoria.id }"
            :data-categoria-id="categoria.id"
          >
            <td class="common-table-cell view-categorias__grip-cell">
              <button
                type="button"
                class="view-categorias__grip"
                :disabled="!podeReordenar"
                :aria-label="`Mover ${categoria.nome} (posição ${index + 1} de ${itens.length}). Use as setas para cima e para baixo.`"
                @pointerdown="iniciarArraste($event, categoria)"
                @keydown.up.prevent="moverComTeclado($event, index)"
                @keydown.down.prevent="moverComTeclado($event, index)"
              >
                <IconGrip aria-hidden="true" />
              </button>
            </td>
            <!-- Com busca a lista está filtrada: mostra a posição salva, não a da lista filtrada -->
            <td class="common-table-cell align--center tabular">{{ busca ? categoria.ordem : index + 1 }}</td>
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
      </div>

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

<style scoped>
.view-categorias__hint {
  padding: 0 20px 12px;
  color: var(--color-text-tertiary);
  font-size: 0.8rem;
}

.view-categorias__grip-cell {
  width: 48px;
}

/* Alça de arrastar: touch-action none deixa arrastar com o dedo sem rolar a página */
.view-categorias__grip {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  color: var(--color-text-tertiary);
  cursor: grab;
  touch-action: none;
}

.view-categorias__grip svg {
  width: 18px;
}

.view-categorias__grip:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.view-categorias__grip:focus-visible {
  outline: 2px solid var(--color-primary-400);
  outline-offset: 2px;
}

.is-dragging,
.is-dragging .view-categorias__grip {
  cursor: grabbing;
  user-select: none;
}

/* Linha que está sendo arrastada fica destacada */
.is-dragged {
  position: relative;
  z-index: 1;
  background: var(--color-primary-50);
  box-shadow: var(--shadow-lg);
}

.is-dragged .view-categorias__grip {
  color: var(--color-primary-600);
}

.is-saving {
  opacity: 0.7;
}

@media (hover: hover) and (pointer: fine) {
  .view-categorias__grip:not(:disabled):hover {
    background: var(--color-neutral-100);
    color: var(--color-text-secondary);
  }
}
</style>
