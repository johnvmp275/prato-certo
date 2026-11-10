<script setup>
import { computed, onMounted, ref, watch } from 'vue';

import { useRoute, useRouter } from 'vue-router';

import CommonTabs from '@/components/Common/CommonTabs/CommonTabs.vue';
import CommonSearch from '@/components/Common/CommonSearch/CommonSearch.vue';
import CommonLoader from '@/components/Common/CommonLoader/CommonLoader.vue';
import CommonEmptyState from '@/components/Common/CommonEmptyState/CommonEmptyState.vue';
import CommonSheet from '@/components/Common/CommonSheet/CommonSheet.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonThemeToggle from '@/components/Common/CommonThemeToggle/CommonThemeToggle.vue';

import CardapioProdutoCard from '@/components/Cardapio/CardapioProdutoCard/CardapioProdutoCard.vue';
import CardapioCarrinho from '@/components/Cardapio/CardapioCarrinho/CardapioCarrinho.vue';
import CardapioConta from '@/components/Cardapio/CardapioConta/CardapioConta.vue';
import CardapioPedidos from '@/components/Cardapio/CardapioPedidos/CardapioPedidos.vue';

import IconShoppingBag from '@/components/Icons/IconShoppingBag.vue';
import IconMapPin from '@/components/Icons/IconMapPin.vue';
import IconPhone from '@/components/Icons/IconPhone.vue';
import IconSearch from '@/components/Icons/IconSearch.vue';
import IconClock from '@/components/Icons/IconClock.vue';
import IconUser from '@/components/Icons/IconUser.vue';
import IconReceipt from '@/components/Icons/IconReceipt.vue';

import { cardapioService } from '@/services/painel';
import { useCardapio } from '@/stores/useCardapio';
import { useConta } from '@/stores/useConta';
import { usePedidosCardapio } from '@/stores/usePedidosCardapio';
import { useToast } from '@/stores/useToast';
import { useDevice } from '@/composables/useDevice';
import { formatarMoeda } from '@/composables/useFormat';
import { useAbaSalva } from '@/composables/useAbaSalva';

defineOptions({
  name: 'ViewCardapio',
});

const carrinho = useCardapio();
const conta = useConta();
const pedidosCardapio = usePedidosCardapio();
const route = useRoute();
const router = useRouter();
const toast = useToast();
const { isDesktop } = useDevice();

const cardapio = ref(null);
const carregando = ref(true);
const filtrando = ref(false);
const erro = ref('');

/* ===========================
 * FILTROS CONSULTADOS NA API
 * - Categoria (abas): salva no sessionStorage, continua selecionada depois do F5
 * - Busca: fica na URL (?busca=pizza), dá para compartilhar o link
 * =========================== */
const { aba: categoria, garantirValida: garantirCategoriaValida } = useAbaSalva('cardapio:categoria', 'todas', 'session');

// O CommonSearch já aplica o debounce (só muda depois que a pessoa para de digitar)
const busca = computed({
  get: () => String(route.query.busca ?? ''),
  set: valor => atualizarQuery({ busca: valor.trim() || undefined }),
});

// Substitui (não empilha no histórico) para não criar uma entrada a cada palavra digitada
function atualizarQuery(valores) {
  const query = { ...route.query, ...valores };
  Object.keys(query).forEach(chave => query[chave] === undefined && delete query[chave]);
  router.replace({ query });
}

function limparFiltros() {
  categoria.value = 'todas';
  atualizarQuery({ busca: undefined });
}

const abas = computed(() => [
  { value: 'todas', label: 'Tudo' },
  ...(cardapio.value?.abas ?? []).map(item => ({ value: item.id, label: item.nome })),
]);

// A API já devolve só as categorias e produtos que batem com os filtros
const categoriasVisiveis = computed(() => cardapio.value?.categorias ?? []);

const nomeCategoria = computed(() => abas.value.find(item => item.value === categoria.value)?.label ?? '');
const filtrosAtivos = computed(() => Boolean(busca.value) || categoria.value !== 'todas');
const mensagemVazio = computed(() => {
  if (busca.value && categoria.value !== 'todas') return `Não encontramos "${busca.value}" em ${nomeCategoria.value}.`;
  if (busca.value) return `Não encontramos nenhum produto com "${busca.value}". Tente outro nome.`;
  if (categoria.value !== 'todas') return `${nomeCategoria.value} não tem produtos disponíveis no momento.`;
  return 'O cardápio ainda não tem produtos disponíveis.';
});

const quantidadePorProduto = computed(() =>
  carrinho.itens.reduce((mapa, item) => {
    mapa[item.produtoId] = (mapa[item.produtoId] ?? 0) + item.quantidade;
    return mapa;
  }, {}),
);

function adicionar(produto) {
  carrinho.adicionar(produto);
  toast.success(`${produto.nome} adicionado ao carrinho.`);
}

// Cada consulta tem um número: se a pessoa trocar o filtro antes da resposta chegar, a resposta antiga é ignorada
let consultaAtual = 0;

async function carregar() {
  const consulta = ++consultaAtual;
  // Primeira carga mostra o loader; nas trocas de filtro a tela continua e só fica esmaecida
  if (cardapio.value) filtrando.value = true;
  else carregando.value = true;
  erro.value = '';

  try {
    const resposta = await cardapioService.buscar({
      categoria: categoria.value === 'todas' ? undefined : categoria.value,
      busca: busca.value || undefined,
    });
    if (consulta !== consultaAtual) return;
    cardapio.value = resposta;
    document.title = `Cardápio · ${resposta.restaurante.nome}`;
    // Categoria salva que não existe mais (inativada ou sem produtos): volta para "Tudo" e consulta de novo
    garantirCategoriaValida(['todas', ...resposta.abas.map(item => item.id)]);
  } catch (error) {
    if (consulta !== consultaAtual) return;
    if (cardapio.value) toast.error(error.mensagem);
    else erro.value = error.mensagem;
  } finally {
    if (consulta === consultaAtual) {
      carregando.value = false;
      filtrando.value = false;
    }
  }
}

// Consulta a API sempre que a categoria ou a busca mudam
watch([categoria, () => route.query.busca], carregar);

onMounted(() => {
  carregar();
  conta.carregar();

  // Link antigo de acompanhamento (/pedido/:codigo vira /?pedido=...): abre o pedido na janela
  const codigo = String(route.query.pedido ?? '');
  if (/^\d{7}$/.test(codigo)) pedidosCardapio.abrirPedido(codigo);
  // Tira da URL o ?pedido= e o ?categoria= dos links antigos (a categoria agora fica no sessionStorage)
  if (route.query.pedido || route.query.categoria) atualizarQuery({ pedido: undefined, categoria: undefined });
});
</script>

<template>
  <div class="view-cardapio">
    <CommonLoader
      v-if="carregando"
      label="Carregando cardápio..."
    />

    <CommonEmptyState
      v-else-if="erro"
      title="Não foi possível carregar o cardápio"
      :description="erro"
    >
      <CommonButton @click="carregar">Tentar novamente</CommonButton>
    </CommonEmptyState>

    <div
      v-else
      class="view-cardapio__layout row"
    >
      <main class="view-cardapio__main">
        <!-- CAPA DO RESTAURANTE -->
        <header class="view-cardapio__hero">
          <img
            src="/favicon.svg"
            alt=""
            class="view-cardapio__logo"
            aria-hidden="true"
          />
          <div class="view-cardapio__hero-info">
            <h1 class="view-cardapio__name">{{ cardapio.restaurante.nome }}</h1>
            <p
              class="view-cardapio__status"
              :class="{ 'is-open': cardapio.restaurante.aberto }"
            >
              <IconClock aria-hidden="true" />
              {{ cardapio.restaurante.aberto ? 'Aberto · recebendo pedidos' : 'Fechado para pedidos online' }}
            </p>
            <p
              v-if="cardapio.restaurante.endereco"
              class="view-cardapio__meta"
            >
              <IconMapPin aria-hidden="true" />
              {{ cardapio.restaurante.endereco }}
            </p>
            <p
              v-if="cardapio.restaurante.telefone"
              class="view-cardapio__meta"
            >
              <IconPhone aria-hidden="true" />
              {{ cardapio.restaurante.telefone }} · Entrega {{ formatarMoeda(cardapio.restaurante.taxaEntrega) }}
            </p>
          </div>

          <!-- MEUS PEDIDOS E CONTA DO CLIENTE (LOGIN OPCIONAL) -->
          <div class="view-cardapio__account">
            <CommonThemeToggle variant="glass" />
            <button
              type="button"
              class="view-cardapio__account-button"
              @click="pedidosCardapio.abrirLista()"
            >
              <IconReceipt aria-hidden="true" />
              Meus pedidos
            </button>
            <button
              type="button"
              class="view-cardapio__account-button"
              @click="conta.aberta = true"
            >
              <IconUser aria-hidden="true" />
              <span class="view-cardapio__account-name">{{ conta.isLogado ? `Olá, ${conta.primeiroNome}` : 'Entrar' }}</span>
            </button>
          </div>
        </header>

        <div class="view-cardapio__filters">
          <CommonSearch
            v-model="busca"
            :debounce="400"
            placeholder="Buscar no cardápio..."
          />
          <CommonTabs
            v-model="categoria"
            :tabs="abas"
          />
        </div>

        <section
          v-for="item in categoriasVisiveis"
          :key="item.id"
          class="view-cardapio__category"
          :class="{ 'is-filtering': filtrando }"
          :aria-busy="filtrando"
        >
          <h2 class="view-cardapio__category-title">{{ item.nome }}</h2>
          <div class="view-cardapio__grid">
            <CardapioProdutoCard
              v-for="produto in item.produtos"
              :key="produto.id"
              :produto="produto"
              :quantidade="quantidadePorProduto[produto.id] ?? 0"
              @add="adicionar"
            />
          </div>
        </section>

        <!-- NENHUM PRODUTO PARA OS FILTROS -->
        <CommonEmptyState
          v-if="!categoriasVisiveis.length"
          :class="{ 'is-filtering': filtrando }"
          :icon="IconSearch"
          title="Nenhum produto encontrado"
          :description="mensagemVazio"
        >
          <CommonButton
            v-if="filtrosAtivos"
            variant="secondary"
            @click="limparFiltros"
          >
            Ver todo o cardápio
          </CommonButton>
        </CommonEmptyState>
      </main>

      <!-- CARRINHO LATERAL (DESKTOP) -->
      <aside
        v-if="isDesktop"
        class="view-cardapio__cart card"
      >
        <CardapioCarrinho
          :restaurante="cardapio.restaurante"
          @close="carrinho.aberto = false"
        />
      </aside>
    </div>

    <!-- BOTÃO E GAVETA DO CARRINHO (CELULAR) -->
    <template v-if="!isDesktop && cardapio">
      <button
        v-if="carrinho.quantidadeItens"
        type="button"
        class="view-cardapio__cart-bar"
        @click="carrinho.aberto = true"
      >
        <IconShoppingBag aria-hidden="true" />
        Ver carrinho · {{ carrinho.quantidadeItens }} {{ carrinho.quantidadeItens === 1 ? 'item' : 'itens' }}
        <strong class="tabular">{{ formatarMoeda(carrinho.subtotal) }}</strong>
      </button>

      <CommonSheet
        v-if="carrinho.aberto"
        position="bottom"
        size="92dvh"
        @close="carrinho.aberto = false"
      >
        <template #content="{ close }">
          <div class="view-cardapio__sheet">
            <CardapioCarrinho
              :restaurante="cardapio.restaurante"
              @close="close"
            />
          </div>
        </template>
      </CommonSheet>
    </template>

    <CardapioConta
      v-if="conta.aberta"
      @close="conta.aberta = false"
    />

    <CardapioPedidos v-if="pedidosCardapio.aberto" />
  </div>
</template>

<style scoped>
.view-cardapio__layout {
  display: grid;
  gap: 24px;
  padding: 0 0 110px;
}

.view-cardapio__main {
  display: flex;
  flex-direction: column;
  gap: 22px;
  min-width: 0;
  padding: 0 16px;
}

.view-cardapio__hero {
  position: relative;
  display: flex;
  align-items: center;
  gap: 16px;
  margin: 0 -16px;
  padding: 52px 16px 24px;
  background: linear-gradient(140deg, var(--color-primary-500), var(--color-primary-700));
  color: #ffffff;
}

.view-cardapio__account {
  position: absolute;
  top: 12px;
  right: 12px;
  left: 12px;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.view-cardapio__account-button {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.18);
  color: #ffffff;
  font-size: 0.82rem;
  font-weight: 600;
  white-space: nowrap;
  cursor: pointer;
}

.view-cardapio__account-name {
  overflow: hidden;
  text-overflow: ellipsis;
}

.view-cardapio__account-button svg {
  flex-shrink: 0;
  width: 16px;
}

.view-cardapio__logo {
  width: 72px;
  height: 72px;
  border: 4px solid rgba(255, 255, 255, 0.4);
  border-radius: 22px;
}

.view-cardapio__hero-info {
  display: grid;
  gap: 4px;
}

.view-cardapio__name {
  color: #ffffff;
  font-size: 1.5rem;
}

.view-cardapio__status,
.view-cardapio__meta {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.82rem;
  opacity: 0.92;
}

.view-cardapio__status {
  width: fit-content;
  padding: 3px 10px;
  border-radius: 999px;
  background: rgba(17, 17, 24, 0.25);
  font-weight: 600;
}

.view-cardapio__status.is-open {
  background: var(--color-success-500);
}

.view-cardapio__status svg,
.view-cardapio__meta svg {
  width: 14px;
}

.view-cardapio__filters {
  position: sticky;
  top: 0;
  z-index: 5;
  display: grid;
  gap: 12px;
  margin: 0 -16px;
  padding: 12px 16px;
  background: var(--color-background);
}

.view-cardapio__category {
  display: grid;
  gap: 12px;
  transition: opacity 160ms ease;
}

/* Enquanto a API responde a um novo filtro, o resultado anterior fica esmaecido */
.is-filtering {
  opacity: 0.5;
  pointer-events: none;
}

.view-cardapio__category-title {
  font-size: 1.15rem;
}

.view-cardapio__grid {
  display: grid;
  gap: 12px;
}

.view-cardapio__cart {
  position: sticky;
  top: 24px;
  height: calc(100dvh - 48px);
  overflow: hidden;
}

.view-cardapio__cart-bar {
  position: fixed;
  right: 16px;
  bottom: 16px;
  left: 16px;
  z-index: 20;
  display: flex;
  align-items: center;
  gap: 10px;
  min-height: 56px;
  padding: 0 18px;
  border-radius: 16px;
  background: var(--color-primary-500);
  color: #ffffff;
  font-weight: 600;
  box-shadow: var(--shadow-primary);
}

.view-cardapio__cart-bar svg {
  width: 20px;
}

.view-cardapio__cart-bar strong {
  margin-left: auto;
}

.view-cardapio__sheet {
  height: 100%;
  overflow: hidden;
  border-radius: 20px 20px 0 0;
}

@media (min-width: 768px) {
  .view-cardapio__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .view-cardapio__hero {
    margin: 24px 0 0;
    padding: 52px 32px 32px;
    border-radius: 24px;
  }
  .view-cardapio__main {
    padding: 0 24px;
  }
  .view-cardapio__filters {
    margin: 0 -24px;
    padding: 12px 24px;
  }
}

/* DESKTOP: A PÁGINA NÃO ROLA; SÓ A COLUNA DO CARDÁPIO E O CARRINHO TÊM SCROLL PRÓPRIO */
@media (min-width: 1024px) {
  .view-cardapio__layout {
    grid-template-columns: minmax(0, 1fr) 380px;
    height: 100vh;
    height: 100dvh;
    padding: 0 24px;
    overflow: hidden;
  }
  .view-cardapio__main {
    height: 100%;
    padding: 0 10px 48px 0;
    overflow-y: auto;
    overscroll-behavior: contain;
  }
  .view-cardapio__filters {
    margin: 0;
    padding: 12px 0;
  }
  .view-cardapio__cart {
    position: static;
    align-self: start;
    height: calc(100dvh - 48px);
    margin-top: 24px;
  }
}
</style>
