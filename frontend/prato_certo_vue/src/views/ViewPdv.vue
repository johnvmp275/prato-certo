<script setup>
import { computed, onMounted, ref, watch } from 'vue';

import { useRoute, useRouter } from 'vue-router';

import CommonSearch from '@/components/Common/CommonSearch/CommonSearch.vue';
import CommonTabs from '@/components/Common/CommonTabs/CommonTabs.vue';
import CommonLoader from '@/components/Common/CommonLoader/CommonLoader.vue';
import CommonEmptyState from '@/components/Common/CommonEmptyState/CommonEmptyState.vue';
import CommonSheet from '@/components/Common/CommonSheet/CommonSheet.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';

import PdvProductCard from '@/components/Pdv/PdvProductCard/PdvProductCard.vue';
import PdvCart from '@/components/Pdv/PdvCart/PdvCart.vue';

import IconSearch from '@/components/Icons/IconSearch.vue';
import IconShoppingBag from '@/components/Icons/IconShoppingBag.vue';
import IconAlert from '@/components/Icons/IconAlert.vue';

import { produtosService, categoriasService } from '@/services/crud';
import { pedidosService } from '@/services/pedidos';
import { usePdv } from '@/stores/usePdv';
import { useToast } from '@/stores/useToast';
import { useDevice } from '@/composables/useDevice';
import { formatarMoeda } from '@/composables/useFormat';

defineOptions({
  name: 'ViewPdv',
});

const route = useRoute();
const router = useRouter();
const pdv = usePdv();
const toast = useToast();
const { isDesktop } = useDevice();

const categorias = ref([]);
const produtos = ref([]);
const categoria = ref('todas');
const busca = ref('');
const carregandoProdutos = ref(true);
const carregandoPedido = ref(false);
const processando = ref('');
const mostrarCarrinho = ref(false);

const abas = computed(() => [
  { value: 'todas', label: 'Todos' },
  ...categorias.value.map(item => ({ value: item.id, label: item.nome })),
]);

// Quantidade de cada produto no carrinho (badge nos cards)
const quantidadePorProduto = computed(() =>
  pdv.itens.reduce((mapa, item) => {
    mapa[item.produtoId] = (mapa[item.produtoId] ?? 0) + item.quantidade;
    return mapa;
  }, {}),
);

async function carregarCategorias() {
  const resposta = await categoriasService.listar({ status: 'ativo' });
  categorias.value = resposta.categorias.filter(item => item.quantidadeProdutos > 0);
}

async function carregarProdutos() {
  carregandoProdutos.value = true;
  try {
    const resposta = await produtosService.listar({
      status: 'ativo',
      ipp: 100,
      busca: busca.value,
      categoriaId: categoria.value === 'todas' ? '' : categoria.value,
    });
    produtos.value = resposta.produtos;
  } catch (error) {
    toast.error(error.mensagem);
  } finally {
    carregandoProdutos.value = false;
  }
}

// Carrega o pedido da URL (/pdv/:id) ou inicia um novo pedido de balcão
async function carregarPedido() {
  const id = route.params.id;
  if (!id) {
    if (pdv.pedido) pdv.novo();
    return;
  }

  carregandoPedido.value = true;
  try {
    const pedido = await pedidosService.buscar(id);
    if (['finalizado', 'cancelado'].includes(pedido.status)) {
      toast.info(`O pedido #${pedido.codigo} está ${pedido.statusDescricao.toLowerCase()}.`);
      router.replace('/pdv');
      return;
    }
    pdv.carregar(pedido);
  } catch (error) {
    toast.error(error.mensagem);
    router.replace('/pdv');
  } finally {
    carregandoPedido.value = false;
  }
}

// Envia o carrinho para a API (abre o pedido de balcão ou atualiza o existente)
async function salvarPedido() {
  const pedido = pdv.pedido
    ? await pedidosService.atualizar(pdv.pedido.id, pdv.payload())
    : await pedidosService.abrir({ tipo: 'balcao', ...pdv.payload() });

  pdv.carregar(pedido);
  return pedido;
}

async function executar(acao, operacao) {
  processando.value = acao;
  try {
    await operacao();
  } catch (error) {
    toast.error(error.mensagem);
  } finally {
    processando.value = '';
  }
}

function salvar() {
  executar('salvar', async () => {
    const pedido = await salvarPedido();
    toast.success(`Pedido #${pedido.codigo} salvo.`);
    if (route.params.id != pedido.id) router.replace(`/pdv/${pedido.id}`);
  });
}

function colocarEmEspera() {
  executar('espera', async () => {
    let pedido = await salvarPedido();
    if (pedido.status === 'aberto') pedido = await pedidosService.colocarEmEspera(pedido.id);
    toast.success(`Pedido #${pedido.codigo} colocado em espera.`);
    pdv.novo();
    router.push(pedido.tipo === 'mesa' ? '/mesas' : '/pedidos');
  });
}

function pagar() {
  executar('pagar', async () => {
    let pedido = await salvarPedido();
    if (pedido.status === 'em_espera') pedido = await pedidosService.retomar(pedido.id);
    pdv.novo();
    router.push(`/pedidos/${pedido.id}/pagamento`);
  });
}

function novoPedido() {
  pdv.novo();
  mostrarCarrinho.value = false;
  if (route.params.id) router.push('/pdv');
}

async function aceitarPedido() {
  await executar('aceitar', async () => {
    pdv.carregar(await pedidosService.aceitar(pdv.pedido.id));
    toast.success('Pedido online aceito.');
  });
}

watch([categoria, busca], carregarProdutos);
watch(() => route.params.id, carregarPedido);

onMounted(() => {
  carregarCategorias().catch(() => {});
  carregarProdutos();
  carregarPedido();
});
</script>

<template>
  <main class="view-pdv">
    <section class="view-pdv__catalog">
      <header class="view-pdv__toolbar">
        <div class="view-pdv__title">
          <h1>PDV</h1>
          <p>{{ new Date().toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long' }) }}</p>
        </div>
        <CommonSearch
          v-model="busca"
          class="view-pdv__search"
          placeholder="Buscar produto..."
        />
      </header>

      <div
        v-if="pdv.pedido?.status === 'pendente'"
        class="view-pdv__banner"
      >
        <IconAlert aria-hidden="true" />
        <span>Este pedido online ainda não foi aceito.</span>
        <CommonButton
          size="sm"
          :loading="processando === 'aceitar'"
          @click="aceitarPedido"
        >
          Aceitar pedido
        </CommonButton>
      </div>

      <CommonTabs
        v-model="categoria"
        :tabs="abas"
      />

      <CommonLoader v-if="(carregandoProdutos && !produtos.length) || carregandoPedido" />

      <div
        v-else-if="produtos.length"
        class="view-pdv__grid"
      >
        <PdvProductCard
          v-for="produto in produtos"
          :key="produto.id"
          :produto="produto"
          :quantidade="quantidadePorProduto[produto.id] ?? 0"
          @add="pdv.adicionar"
        />
      </div>

      <CommonEmptyState
        v-else
        :icon="IconSearch"
        title="Nenhum produto encontrado"
        description="Tente outra busca ou categoria."
      />
    </section>

    <!-- CARRINHO FIXO NO DESKTOP -->
    <aside
      v-if="isDesktop"
      class="view-pdv__cart"
    >
      <PdvCart
        :processando="processando"
        @espera="colocarEmEspera"
        @salvar="salvar"
        @pagar="pagar"
        @novo="novoPedido"
      />
    </aside>

    <!-- CARRINHO EM GAVETA NO CELULAR/TABLET -->
    <template v-else>
      <button
        type="button"
        class="view-pdv__cart-bar"
        @click="mostrarCarrinho = true"
      >
        <span class="view-pdv__cart-bar-icon">
          <IconShoppingBag aria-hidden="true" />
          <span>{{ pdv.quantidadeItens }}</span>
        </span>
        <span>{{ pdv.pedido ? `Pedido #${pdv.pedido.codigo}` : 'Ver carrinho' }}</span>
        <strong class="tabular">{{ formatarMoeda(pdv.subtotal) }}</strong>
      </button>

      <CommonSheet
        v-if="mostrarCarrinho"
        position="bottom"
        size="92dvh"
        @close="mostrarCarrinho = false"
      >
        <template #content>
          <div class="view-pdv__sheet">
            <PdvCart
              :processando="processando"
              @espera="colocarEmEspera"
              @salvar="salvar"
              @pagar="pagar"
              @novo="novoPedido"
            />
          </div>
        </template>
      </CommonSheet>
    </template>
  </main>
</template>

<style scoped>
.view-pdv {
  display: grid;
  min-height: 100vh;
  min-height: 100dvh;
}

.view-pdv__catalog {
  display: flex;
  flex-direction: column;
  gap: 18px;
  min-width: 0;
  padding: 20px 16px 110px;
}

.view-pdv__toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
}

.view-pdv__title h1 {
  font-size: 1.45rem;
}

.view-pdv__title p {
  color: var(--color-text-tertiary);
  font-size: 0.82rem;
}

.view-pdv__title p::first-letter {
  text-transform: uppercase;
}

.view-pdv__search {
  max-width: 380px;
}

.view-pdv__banner {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  border-radius: 14px;
  background: var(--color-warning-50);
  color: var(--color-warning-700);
  font-size: 0.88rem;
}

.view-pdv__banner svg {
  width: 18px;
}

.view-pdv__banner :deep(.common-button) {
  margin-left: auto;
}

.view-pdv__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 14px;
}

.view-pdv__cart {
  position: sticky;
  top: 0;
  height: 100vh;
  height: 100dvh;
  border-left: 1px solid var(--color-border-light);
}

.view-pdv__cart-bar {
  position: fixed;
  right: 16px;
  bottom: 16px;
  left: 16px;
  z-index: 20;
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 58px;
  padding: 0 18px 0 10px;
  border-radius: 16px;
  background: var(--color-primary-500);
  color: #ffffff;
  font-weight: 600;
  box-shadow: var(--shadow-primary);
}

.view-pdv__cart-bar strong {
  margin-left: auto;
  font-size: 1.05rem;
}

.view-pdv__cart-bar-icon {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.2);
}

.view-pdv__cart-bar-icon svg {
  width: 20px;
}

.view-pdv__cart-bar-icon span {
  position: absolute;
  top: -6px;
  right: -6px;
  min-width: 20px;
  padding: 0 5px;
  border-radius: 999px;
  background: #ffffff;
  color: var(--color-primary-600);
  font-size: 0.7rem;
  line-height: 20px;
}

.view-pdv__sheet {
  height: 100%;
  overflow: hidden;
  border-radius: 20px 20px 0 0;
  background: var(--color-surface);
}

@media (min-width: 768px) {
  .view-pdv__catalog {
    padding: 28px 28px 110px;
  }
  .view-pdv__grid {
    grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  }
}

@media (min-width: 1024px) {
  .view-pdv {
    grid-template-columns: minmax(0, 1fr) 380px;
  }
  .view-pdv__catalog {
    padding: 28px 28px 40px;
  }
}

@media (min-width: 1400px) {
  .view-pdv {
    grid-template-columns: minmax(0, 1fr) 420px;
  }
}
</style>
