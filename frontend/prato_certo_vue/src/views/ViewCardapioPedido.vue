<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

import { useRoute } from 'vue-router';

import CommonLoader from '@/components/Common/CommonLoader/CommonLoader.vue';
import CommonEmptyState from '@/components/Common/CommonEmptyState/CommonEmptyState.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';

import IconCheck from '@/components/Icons/IconCheck.vue';
import IconX from '@/components/Icons/IconX.vue';
import IconReceipt from '@/components/Icons/IconReceipt.vue';

import { cardapioService } from '@/services/painel';
import { formatarMoeda, formatarDataHora } from '@/composables/useFormat';

defineOptions({
  name: 'ViewCardapioPedido',
});

const route = useRoute();

const pedido = ref(null);
const erro = ref('');
const carregando = ref(true);

// Linha do tempo do pedido online
const ETAPAS = [
  { status: 'pendente', titulo: 'Pedido enviado', texto: 'Aguardando o restaurante aceitar.' },
  { status: 'aberto', titulo: 'Em preparo', texto: 'O restaurante aceitou seu pedido.' },
  { status: 'finalizado', titulo: 'Concluído', texto: 'Pedido pago e finalizado. Bom apetite!' },
];

const ORDEM = { pendente: 0, aberto: 1, em_espera: 1, finalizado: 2 };

const etapaAtual = computed(() => ORDEM[pedido.value?.status] ?? -1);
const cancelado = computed(() => pedido.value?.status === 'cancelado');

async function carregar() {
  try {
    pedido.value = await cardapioService.acompanhar(route.params.codigo);
    erro.value = '';
  } catch (error) {
    erro.value = error.mensagem;
  } finally {
    carregando.value = false;
  }
}

// Atualiza o status automaticamente a cada 15 segundos
let intervalo = null;
onMounted(() => {
  carregar();
  intervalo = setInterval(carregar, 15000);
});
onBeforeUnmount(() => clearInterval(intervalo));
</script>

<template>
  <main class="view-cardapio-pedido">
    <CommonLoader
      v-if="carregando"
      label="Buscando seu pedido..."
    />

    <CommonEmptyState
      v-else-if="erro && !pedido"
      :icon="IconReceipt"
      title="Pedido não encontrado"
      :description="erro"
    >
      <RouterLink to="/">
        <CommonButton>Voltar ao cardápio</CommonButton>
      </RouterLink>
    </CommonEmptyState>

    <article
      v-else-if="pedido"
      class="view-cardapio-pedido__card card"
    >
      <header class="view-cardapio-pedido__header">
        <p class="view-cardapio-pedido__eyebrow">{{ pedido.origem }}</p>
        <h1 class="view-cardapio-pedido__title">Pedido #{{ pedido.codigo }}</h1>
        <p class="view-cardapio-pedido__subtitle">
          Olá, {{ pedido.nomeContato }}! Guarde este código para acompanhar. · {{ formatarDataHora(pedido.criadoEm) }}
        </p>
      </header>

      <div
        v-if="cancelado"
        class="view-cardapio-pedido__cancelled"
      >
        <IconX aria-hidden="true" />
        Este pedido foi cancelado pelo restaurante.
      </div>

      <ol
        v-else
        class="view-cardapio-pedido__timeline"
      >
        <li
          v-for="(etapa, index) in ETAPAS"
          :key="etapa.status"
          :class="{ 'is-done': index < etapaAtual, 'is-current': index === etapaAtual }"
        >
          <span
            class="view-cardapio-pedido__dot"
            aria-hidden="true"
          >
            <IconCheck v-if="index <= etapaAtual" />
          </span>
          <span class="cell-stack">
            <strong>{{ etapa.titulo }}</strong>
            <small>{{ etapa.texto }}</small>
          </span>
        </li>
      </ol>

      <ul class="view-cardapio-pedido__items">
        <li
          v-for="(item, index) in pedido.itens"
          :key="index"
        >
          <span class="view-cardapio-pedido__qty">{{ item.quantidade }}x</span>
          <span class="view-cardapio-pedido__name">{{ item.nomeProduto }}</span>
          <span class="tabular">{{ formatarMoeda(item.subtotal) }}</span>
        </li>
      </ul>

      <dl class="view-cardapio-pedido__totals">
        <div>
          <dt>Subtotal</dt>
          <dd class="tabular">{{ formatarMoeda(pedido.valores.subtotal) }}</dd>
        </div>
        <div v-if="pedido.valores.desconto">
          <dt>Desconto</dt>
          <dd class="tabular">- {{ formatarMoeda(pedido.valores.desconto) }}</dd>
        </div>
        <div>
          <dt>{{ pedido.valores.taxaDescricao }}</dt>
          <dd class="tabular">{{ formatarMoeda(pedido.valores.taxa) }}</dd>
        </div>
        <div class="is-total">
          <dt>Total</dt>
          <dd class="tabular">{{ formatarMoeda(pedido.valores.total) }}</dd>
        </div>
      </dl>

      <RouterLink
        to="/"
        class="view-cardapio-pedido__back"
      >
        ← Voltar ao cardápio
      </RouterLink>
    </article>
  </main>
</template>

<style scoped>
.view-cardapio-pedido {
  display: flex;
  justify-content: center;
  padding: 24px 16px 48px;
}

.view-cardapio-pedido__card {
  display: flex;
  flex-direction: column;
  gap: 22px;
  width: 100%;
  max-width: 560px;
  padding: 24px;
}

.view-cardapio-pedido__eyebrow {
  color: var(--color-primary-600);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.view-cardapio-pedido__title {
  font-size: 1.6rem;
}

.view-cardapio-pedido__subtitle {
  color: var(--color-text-secondary);
  font-size: 0.86rem;
}

.view-cardapio-pedido__cancelled {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  border-radius: 14px;
  background: var(--color-error-50);
  color: var(--color-error-700);
  font-weight: 600;
}

.view-cardapio-pedido__cancelled svg {
  width: 20px;
}

.view-cardapio-pedido__timeline {
  display: grid;
  gap: 0;
}

.view-cardapio-pedido__timeline li {
  position: relative;
  display: flex;
  gap: 14px;
  padding-bottom: 20px;
  opacity: 0.5;
}

.view-cardapio-pedido__timeline li:not(:last-child)::after {
  content: '';
  position: absolute;
  top: 30px;
  bottom: 2px;
  left: 13px;
  width: 2px;
  background: var(--color-border-default);
}

.view-cardapio-pedido__timeline li.is-done,
.view-cardapio-pedido__timeline li.is-current {
  opacity: 1;
}

.view-cardapio-pedido__timeline li.is-done::after {
  background: var(--color-success-500);
}

.view-cardapio-pedido__dot {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border: 2px solid var(--color-border-dark);
  border-radius: 50%;
  background: var(--color-surface);
  color: #ffffff;
}

.view-cardapio-pedido__dot svg {
  width: 15px;
}

.is-done .view-cardapio-pedido__dot {
  border-color: var(--color-success-500);
  background: var(--color-success-500);
}

.is-current .view-cardapio-pedido__dot {
  border-color: var(--color-primary-500);
  background: var(--color-primary-500);
  box-shadow: 0 0 0 5px var(--color-primary-100);
}

.view-cardapio-pedido__items {
  display: grid;
  gap: 10px;
  padding-top: 16px;
  border-top: 1px dashed var(--color-border-dark);
}

.view-cardapio-pedido__items li {
  display: flex;
  gap: 10px;
  font-size: 0.9rem;
}

.view-cardapio-pedido__qty {
  min-width: 28px;
  color: var(--color-primary-600);
  font-weight: 700;
}

.view-cardapio-pedido__name {
  flex: 1;
}

.view-cardapio-pedido__totals {
  display: grid;
  gap: 6px;
}

.view-cardapio-pedido__totals div {
  display: flex;
  justify-content: space-between;
  color: var(--color-text-secondary);
  font-size: 0.86rem;
}

.view-cardapio-pedido__totals .is-total {
  padding-top: 8px;
  border-top: 1px solid var(--color-border-light);
  color: var(--color-text-title);
  font-size: 1.05rem;
  font-weight: 700;
}

.view-cardapio-pedido__back {
  color: var(--color-primary-600);
  font-size: 0.88rem;
  font-weight: 600;
}
</style>
