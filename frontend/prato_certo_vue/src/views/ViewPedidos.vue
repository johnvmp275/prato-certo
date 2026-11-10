<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

import { useRoute, useRouter } from 'vue-router';

import CommonPageHeader from '@/components/Common/CommonPageHeader/CommonPageHeader.vue';
import CommonTabs from '@/components/Common/CommonTabs/CommonTabs.vue';
import CommonSearch from '@/components/Common/CommonSearch/CommonSearch.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonLoader from '@/components/Common/CommonLoader/CommonLoader.vue';
import CommonEmptyState from '@/components/Common/CommonEmptyState/CommonEmptyState.vue';
import CommonPagination from '@/components/Common/CommonPagination/CommonPagination.vue';
import CommonSheet from '@/components/Common/CommonSheet/CommonSheet.vue';

import PedidoListItem from '@/components/Pedidos/PedidoListItem/PedidoListItem.vue';
import PedidoDetalhe from '@/components/Pedidos/PedidoDetalhe/PedidoDetalhe.vue';

import IconPlus from '@/components/Icons/IconPlus.vue';
import IconReceipt from '@/components/Icons/IconReceipt.vue';

import { pedidosService } from '@/services/pedidos';
import { useToast } from '@/stores/useToast';
import { useDevice } from '@/composables/useDevice';
import { useAbaSalva } from '@/composables/useAbaSalva';
import { usePaginaQuery } from '@/composables/usePaginaQuery';

defineOptions({
  name: 'ViewPedidos',
});

const route = useRoute();
const router = useRouter();
const toast = useToast();
const { isDesktop } = useDevice();

// Cada aba filtra um conjunto de status/tipo na API
const FILTROS = {
  abertos: { status: 'aberto,em_espera' },
  online: { tipo: 'online', status: 'pendente,aberto,em_espera' },
  historico: { status: 'finalizado,cancelado' },
};

// Aba salva no localStorage (continua a mesma depois do F5)
// O link do dashboard (?aba=online) tem prioridade e passa a ser a aba salva
const { aba, garantirValida: garantirAbaValida } = useAbaSalva('painel:pedidos', 'abertos');
if (FILTROS[route.query.aba]) aba.value = route.query.aba;
garantirAbaValida(Object.keys(FILTROS));
const busca = ref('');
// Página ligada ao ?page= da URL (F5, link e "voltar" do navegador mantêm a página)
const { pagina, ajustarAoTotal } = usePaginaQuery();
const pedidos = ref([]);
const paginacao = ref({});
const carregando = ref(true);

const selecionado = ref(null);
const carregandoDetalhe = ref(false);

const abas = computed(() => [
  { value: 'abertos', label: 'Abertos e em espera' },
  { value: 'online', label: 'Online' },
  { value: 'historico', label: 'Histórico' },
]);

async function carregar() {
  carregando.value = true;
  try {
    const resposta = await pedidosService.listar({ ...FILTROS[aba.value], busca: busca.value, page: pagina.value });
    pedidos.value = resposta.pedidos;
    paginacao.value = resposta.paginacao;
    ajustarAoTotal(resposta.paginacao?.quantidadePaginas);
  } catch (error) {
    toast.error(error.mensagem);
  } finally {
    carregando.value = false;
  }
}

async function selecionar(pedido) {
  carregandoDetalhe.value = true;
  try {
    selecionado.value = await pedidosService.buscar(pedido.id);
  } catch (error) {
    toast.error(error.mensagem);
  } finally {
    carregandoDetalhe.value = false;
  }
}

function pedidoAtualizado(pedido) {
  selecionado.value = pedido;
  carregar();
}

watch([aba, busca], () => {
  pagina.value = 1;
  selecionado.value = null;
  carregar();
});

watch(pagina, carregar);

// Atualiza a lista a cada 30s (novos pedidos online)
let intervalo = null;
onMounted(() => {
  carregar();
  if (route.query.id) selecionar({ id: route.query.id });
  intervalo = setInterval(carregar, 30000);
});
onBeforeUnmount(() => clearInterval(intervalo));
</script>

<template>
  <main class="view-pedidos view-page row">
    <CommonPageHeader
      title="Pedidos"
      subtitle="Acompanhe comandas abertas, pedidos em espera e pedidos do cardápio online."
    >
      <template #actions>
        <CommonButton @click="router.push('/painel/pdv')">
          <template #icon><IconPlus /></template>
          Novo pedido
        </CommonButton>
      </template>
    </CommonPageHeader>

    <CommonTabs
      v-model="aba"
      :tabs="abas"
      variant="underline"
    />

    <div class="view-pedidos__layout">
      <section class="view-pedidos__list card">
        <div class="view-pedidos__search">
          <CommonSearch
            v-model="busca"
            placeholder="Buscar por código, cliente ou nº da mesa..."
          />
        </div>

        <CommonLoader v-if="carregando && !pedidos.length" />

        <div
          v-else-if="pedidos.length"
          class="view-pedidos__items"
        >
          <PedidoListItem
            v-for="pedido in pedidos"
            :key="pedido.id"
            :pedido="pedido"
            :active="selecionado?.id === pedido.id"
            @select="selecionar"
          />
        </div>

        <CommonEmptyState
          v-else
          :icon="IconReceipt"
          title="Nenhum pedido encontrado"
          description="Os pedidos desta aba aparecem aqui."
        />

        <CommonPagination
          :paginacao="paginacao"
          item-label="pedidos"
          @change="pagina = $event"
        />
      </section>

      <!-- DETALHE NO DESKTOP -->
      <aside
        v-if="isDesktop"
        class="view-pedidos__detail card"
      >
        <CommonLoader v-if="carregandoDetalhe" />
        <PedidoDetalhe
          v-else-if="selecionado"
          :pedido="selecionado"
          @atualizado="pedidoAtualizado"
        />
        <CommonEmptyState
          v-else
          :icon="IconReceipt"
          title="Selecione um pedido"
          description="Os itens, valores e ações do pedido aparecem aqui."
        />
      </aside>

      <!-- DETALHE EM GAVETA NO CELULAR -->
      <CommonSheet
        v-else-if="selecionado"
        position="bottom"
        size="90dvh"
        @close="selecionado = null"
      >
        <template #content>
          <div class="view-pedidos__sheet">
            <PedidoDetalhe
              :pedido="selecionado"
              @atualizado="pedidoAtualizado"
            />
          </div>
        </template>
      </CommonSheet>
    </div>
  </main>
</template>

<style scoped>
.view-pedidos__layout {
  display: grid;
  gap: 20px;
  align-items: start;
}

.view-pedidos__list {
  overflow: hidden;
}

.view-pedidos__search {
  padding: 16px;
  border-bottom: 1px solid var(--color-border-light);
}

.view-pedidos__items {
  display: grid;
  gap: 2px;
  padding: 8px;
}

.view-pedidos__detail {
  position: sticky;
  top: 24px;
  padding: 22px;
}

.view-pedidos__sheet {
  height: 100%;
  overflow-y: auto;
  padding: 22px 20px 32px;
  border-radius: 20px 20px 0 0;
  background: var(--color-surface);
}

@media (min-width: 1024px) {
  .view-pedidos__layout {
    grid-template-columns: minmax(0, 1fr) 400px;
  }
}
</style>
