<script setup>
import { computed, onMounted, ref } from 'vue';

import CommonTabs from '@/components/Common/CommonTabs/CommonTabs.vue';
import CommonSearch from '@/components/Common/CommonSearch/CommonSearch.vue';
import CommonLoader from '@/components/Common/CommonLoader/CommonLoader.vue';
import CommonEmptyState from '@/components/Common/CommonEmptyState/CommonEmptyState.vue';
import CommonSheet from '@/components/Common/CommonSheet/CommonSheet.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';

import CardapioProdutoCard from '@/components/Cardapio/CardapioProdutoCard/CardapioProdutoCard.vue';
import CardapioCarrinho from '@/components/Cardapio/CardapioCarrinho/CardapioCarrinho.vue';

import IconShoppingBag from '@/components/Icons/IconShoppingBag.vue';
import IconMapPin from '@/components/Icons/IconMapPin.vue';
import IconPhone from '@/components/Icons/IconPhone.vue';
import IconSearch from '@/components/Icons/IconSearch.vue';
import IconClock from '@/components/Icons/IconClock.vue';

import { cardapioService } from '@/services/painel';
import { useCardapio } from '@/stores/useCardapio';
import { useToast } from '@/stores/useToast';
import { useDevice } from '@/composables/useDevice';
import { formatarMoeda } from '@/composables/useFormat';

defineOptions({
  name: 'ViewCardapio',
});

const carrinho = useCardapio();
const toast = useToast();
const { isDesktop } = useDevice();

const cardapio = ref(null);
const carregando = ref(true);
const erro = ref('');
const categoria = ref('todas');
const busca = ref('');
const codigoAcompanhar = ref('');

const abas = computed(() => [
  { value: 'todas', label: 'Tudo' },
  ...(cardapio.value?.categorias ?? []).map(item => ({ value: item.id, label: item.nome })),
]);

// Filtra por categoria e pela busca (no navegador, o cardápio já vem completo)
const categoriasVisiveis = computed(() => {
  const termo = busca.value.toLowerCase();
  return (cardapio.value?.categorias ?? [])
    .filter(item => categoria.value === 'todas' || item.id === categoria.value)
    .map(item => ({
      ...item,
      produtos: item.produtos.filter(
        produto =>
          !termo ||
          produto.nome.toLowerCase().includes(termo) ||
          (produto.descricao ?? '').toLowerCase().includes(termo),
      ),
    }))
    .filter(item => item.produtos.length);
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

async function carregar() {
  carregando.value = true;
  erro.value = '';
  try {
    cardapio.value = await cardapioService.buscar();
    document.title = `Cardápio · ${cardapio.value.restaurante.nome}`;
  } catch (error) {
    erro.value = error.mensagem;
  } finally {
    carregando.value = false;
  }
}

onMounted(carregar);
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
        </header>

        <div class="view-cardapio__filters">
          <CommonSearch
            v-model="busca"
            :debounce="150"
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

        <CommonEmptyState
          v-if="!categoriasVisiveis.length"
          :icon="IconSearch"
          title="Nenhum prato encontrado"
        />

        <!-- ACOMPANHAR PEDIDO -->
        <form
          class="view-cardapio__track card"
          @submit.prevent="codigoAcompanhar.length === 7 && $router.push(`/cardapio/pedido/${codigoAcompanhar}`)"
        >
          <div>
            <h2 class="view-cardapio__track-title">Já fez um pedido?</h2>
            <p class="view-cardapio__track-text">Digite o código de 7 números para acompanhar.</p>
          </div>
          <div class="view-cardapio__track-form">
            <input
              v-model="codigoAcompanhar"
              class="view-cardapio__track-input"
              inputmode="numeric"
              maxlength="7"
              placeholder="0000000"
              aria-label="Código do pedido"
              @input="codigoAcompanhar = codigoAcompanhar.replace(/\D/g, '')"
            />
            <CommonButton
              type="submit"
              variant="secondary"
              :disabled="codigoAcompanhar.length !== 7"
            >
              Acompanhar
            </CommonButton>
          </div>
        </form>
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
  display: flex;
  align-items: center;
  gap: 16px;
  margin: 0 -16px;
  padding: 28px 16px 24px;
  background: linear-gradient(140deg, var(--color-primary-500), var(--color-primary-700));
  color: #ffffff;
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
}

.view-cardapio__category-title {
  font-size: 1.15rem;
}

.view-cardapio__grid {
  display: grid;
  gap: 12px;
}

.view-cardapio__track {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 14px;
  padding: 20px;
}

.view-cardapio__track-title {
  font-size: 1rem;
}

.view-cardapio__track-text {
  color: var(--color-text-secondary);
  font-size: 0.84rem;
}

.view-cardapio__track-form {
  display: flex;
  gap: 10px;
}

.view-cardapio__track-input {
  flex: 1;
  min-width: 0;
  min-height: 44px;
  padding: 0 14px;
  border: 1px solid var(--color-input-border);
  border-radius: 12px;
  font-size: 1rem;
  letter-spacing: 0.2em;
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
    padding: 32px;
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

@media (min-width: 1024px) {
  .view-cardapio__layout {
    grid-template-columns: minmax(0, 1fr) 380px;
    padding: 0 24px 48px;
  }
  .view-cardapio__main {
    padding: 0;
  }
  .view-cardapio__filters {
    margin: 0;
    padding: 12px 0;
  }
}
</style>
