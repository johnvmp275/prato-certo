<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';

import CommonDialog from '@/components/Common/CommonDialog/CommonDialog.vue';
import CommonLoader from '@/components/Common/CommonLoader/CommonLoader.vue';
import CommonEmptyState from '@/components/Common/CommonEmptyState/CommonEmptyState.vue';
import CommonStatusBadge from '@/components/Common/CommonStatusBadge/CommonStatusBadge.vue';
import CommonPagination from '@/components/Common/CommonPagination/CommonPagination.vue';
import CommonMessageError from '@/components/Common/CommonMessageError/CommonMessageError.vue';

import CardapioPix from '@/components/Cardapio/CardapioPix/CardapioPix.vue';

import IconCheck from '@/components/Icons/IconCheck.vue';
import IconX from '@/components/Icons/IconX.vue';
import IconReceipt from '@/components/Icons/IconReceipt.vue';
import IconArrowLeft from '@/components/Icons/IconArrowLeft.vue';
import IconChevronRight from '@/components/Icons/IconChevronRight.vue';

import { cardapioService, contaService } from '@/services/painel';
import { useConta } from '@/stores/useConta';
import { usePedidosCardapio } from '@/stores/usePedidosCardapio';
import { formatarMoeda, formatarDataHora } from '@/composables/useFormat';

defineOptions({
  name: 'CardapioPedidos',
});

const conta = useConta();
const pedidosCardapio = usePedidosCardapio();

/* ===========================
 * LISTA DE PEDIDOS
 * Logado: pedidos da conta · Sem conta: pedidos feitos neste aparelho
 * =========================== */
const lista = ref([]);
const paginacao = ref(null);
const pagina = ref(1);
const carregandoLista = ref(false);
const erroLista = ref('');

async function carregarLista() {
  carregandoLista.value = true;
  erroLista.value = '';
  try {
    if (conta.isLogado) {
      const resposta = await contaService.pedidos({ page: pagina.value });
      lista.value = resposta.pedidos;
      paginacao.value = resposta.paginacao;
    } else {
      // Busca cada código salvo; os que não existem mais são ignorados
      const resultados = await Promise.allSettled(pedidosCardapio.codigos.map(codigo => cardapioService.acompanhar(codigo)));
      lista.value = resultados.filter(item => item.status === 'fulfilled').map(item => item.value);
      paginacao.value = null;
    }
  } catch (error) {
    erroLista.value = error.mensagem;
  } finally {
    carregandoLista.value = false;
  }
}

/* ===========================
 * ACOMPANHAMENTO DE UM PEDIDO
 * =========================== */
const pedido = ref(null);
const erroPedido = ref('');
const carregandoPedido = ref(false);

// Linha do tempo do pedido online
const ETAPAS = [
  { status: 'pendente', titulo: 'Pedido enviado', texto: 'Aguardando o restaurante aceitar.' },
  { status: 'aberto', titulo: 'Em preparo', texto: 'O restaurante aceitou seu pedido.' },
  { status: 'finalizado', titulo: 'Concluído', texto: 'Pedido pago e finalizado. Bom apetite!' },
];

const ORDEM = { pendente: 0, aberto: 1, em_espera: 1, finalizado: 2 };

const etapaAtual = computed(() => ORDEM[pedido.value?.status] ?? -1);
const cancelado = computed(() => pedido.value?.status === 'cancelado');
const encerrado = computed(() => ['finalizado', 'cancelado'].includes(pedido.value?.status));
const mostrarPix = computed(() => pedido.value?.pagamentoPrevisto?.forma === 'pix' && !encerrado.value);

async function carregarPedido(silencioso = false) {
  if (!silencioso) carregandoPedido.value = true;
  try {
    pedido.value = await cardapioService.acompanhar(pedidosCardapio.codigo);
    erroPedido.value = '';
    // Guarda o código neste aparelho para aparecer em "Meus pedidos" (mesmo sem conta)
    pedidosCardapio.registrar(pedido.value.codigo);
  } catch (error) {
    if (!silencioso) pedido.value = null;
    erroPedido.value = error.mensagem;
  } finally {
    carregandoPedido.value = false;
  }
}

// Atualiza o status automaticamente a cada 15 segundos enquanto o pedido está aberto na tela
let intervalo = null;
function pararAtualizacao() {
  clearInterval(intervalo);
  intervalo = null;
}

watch(
  () => pedidosCardapio.codigo,
  codigo => {
    pararAtualizacao();
    if (codigo) {
      pedido.value = null;
      carregarPedido();
      intervalo = setInterval(() => carregarPedido(true), 15000);
    } else {
      carregarLista();
    }
  },
  { immediate: true },
);

watch(pagina, carregarLista);
onBeforeUnmount(pararAtualizacao);

const titulo = computed(() => (pedidosCardapio.codigo ? `Pedido #${pedidosCardapio.codigo}` : 'Meus pedidos'));
const descricao = computed(() => {
  if (pedidosCardapio.codigo) return pedido.value ? `${pedido.value.origem} · ${formatarDataHora(pedido.value.criadoEm)}` : '';
  return conta.isLogado ? 'Pedidos feitos com a sua conta.' : 'Pedidos feitos neste aparelho.';
});
</script>

<template>
  <CommonDialog
    :title="titulo"
    :description="descricao"
    :width="560"
    @close="pedidosCardapio.fechar()"
  >
    <!-- ACOMPANHAMENTO DO PEDIDO -->
    <div
      v-if="pedidosCardapio.codigo"
      class="cardapio-pedidos"
    >
      <button
        type="button"
        class="cardapio-pedidos__back"
        @click="pedidosCardapio.abrirLista()"
      >
        <IconArrowLeft aria-hidden="true" />
        Todos os meus pedidos
      </button>

      <CommonLoader
        v-if="carregandoPedido && !pedido"
        label="Buscando seu pedido..."
      />

      <CommonEmptyState
        v-else-if="!pedido"
        :icon="IconReceipt"
        title="Pedido não encontrado"
        :description="erroPedido"
      />

      <template v-else>
        <p class="cardapio-pedidos__hello">Olá, {{ pedido.nomeContato }}! Guarde o código <strong>#{{ pedido.codigo }}</strong> para acompanhar.</p>

        <div
          v-if="cancelado"
          class="cardapio-pedidos__cancelled"
        >
          <IconX aria-hidden="true" />
          Este pedido foi cancelado pelo restaurante.
        </div>

        <ol
          v-else
          class="cardapio-pedidos__timeline"
        >
          <li
            v-for="(etapa, index) in ETAPAS"
            :key="etapa.status"
            :class="{ 'is-done': index < etapaAtual, 'is-current': index === etapaAtual }"
          >
            <span
              class="cardapio-pedidos__dot"
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

        <!-- PIX: QR CODE E COPIA E COLA -->
        <CardapioPix
          v-if="mostrarPix"
          :codigo="pedido.codigo"
          :total="pedido.valores.total"
        />

        <ul class="cardapio-pedidos__items">
          <li
            v-for="(item, index) in pedido.itens"
            :key="index"
          >
            <span class="cardapio-pedidos__qty">{{ item.quantidade }}x</span>
            <span class="cardapio-pedidos__name">{{ item.nomeProduto }}</span>
            <span class="tabular">{{ formatarMoeda(item.subtotal) }}</span>
          </li>
        </ul>

        <dl class="cardapio-pedidos__totals">
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
          <div v-if="pedido.pagamentoPrevisto">
            <dt>Pagamento</dt>
            <dd>
              {{ pedido.pagamentoPrevisto.descricao }}
              <template v-if="pedido.pagamentoPrevisto.trocoPara">
                · troco para {{ formatarMoeda(pedido.pagamentoPrevisto.trocoPara) }}
              </template>
            </dd>
          </div>
        </dl>
      </template>
    </div>

    <!-- LISTA DE PEDIDOS -->
    <div
      v-else
      class="cardapio-pedidos"
    >
      <CommonLoader v-if="carregandoLista && !lista.length" />
      <CommonMessageError
        v-else-if="erroLista"
        :error="erroLista"
      />
      <CommonEmptyState
        v-else-if="!lista.length"
        :icon="IconReceipt"
        title="Nenhum pedido por aqui"
        :description="
          conta.isLogado
            ? 'Os pedidos feitos com a sua conta aparecem aqui.'
            : 'Os pedidos feitos neste aparelho aparecem aqui. Entre na sua conta para ver os pedidos de qualquer lugar.'
        "
      />
      <template v-else>
        <ul class="cardapio-pedidos__list">
          <li
            v-for="item in lista"
            :key="item.codigo"
          >
            <button
              type="button"
              class="cardapio-pedidos__order"
              @click="pedidosCardapio.abrirPedido(item.codigo)"
            >
              <span class="cardapio-pedidos__order-info">
                <span class="cardapio-pedidos__order-head">
                  <strong>Pedido #{{ item.codigo }}</strong>
                  <CommonStatusBadge
                    :status="item.status"
                    :label="item.statusDescricao"
                  />
                </span>
                <small>
                  {{ formatarDataHora(item.criadoEm) }} · {{ item.origem }}
                  <template v-if="item.pagamentoPrevisto"> · {{ item.pagamentoPrevisto.descricao }}</template>
                </small>
                <small class="cardapio-pedidos__order-items">
                  {{ item.itens.map(produto => `${produto.quantidade}x ${produto.nomeProduto}`).join(', ') }}
                </small>
              </span>
              <strong class="tabular">{{ formatarMoeda(item.valores.total) }}</strong>
              <IconChevronRight
                class="cardapio-pedidos__chevron"
                aria-hidden="true"
              />
            </button>
          </li>
        </ul>
        <CommonPagination
          v-if="paginacao && paginacao.quantidadePaginas > 1"
          :paginacao="paginacao"
          item-label="pedidos"
          @change="pagina = $event"
        />
      </template>
    </div>
  </CommonDialog>
</template>

<style scoped>
.cardapio-pedidos {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.cardapio-pedidos__back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  align-self: flex-start;
  color: var(--color-primary-600);
  font-size: 0.84rem;
  font-weight: 600;
  cursor: pointer;
}

.cardapio-pedidos__back svg {
  width: 16px;
}

.cardapio-pedidos__hello {
  color: var(--color-text-secondary);
  font-size: 0.86rem;
}

.cardapio-pedidos__cancelled {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  border-radius: 14px;
  background: var(--color-error-50);
  color: var(--color-error-700);
  font-weight: 600;
}

.cardapio-pedidos__cancelled svg {
  width: 20px;
}

.cardapio-pedidos__timeline li {
  position: relative;
  display: flex;
  gap: 14px;
  padding-bottom: 20px;
  opacity: 0.5;
}

.cardapio-pedidos__timeline li:last-child {
  padding-bottom: 0;
}

.cardapio-pedidos__timeline li:not(:last-child)::after {
  content: '';
  position: absolute;
  top: 30px;
  bottom: 2px;
  left: 13px;
  width: 2px;
  background: var(--color-border-default);
}

.cardapio-pedidos__timeline li.is-done,
.cardapio-pedidos__timeline li.is-current {
  opacity: 1;
}

.cardapio-pedidos__timeline li.is-done::after {
  background: var(--color-success-500);
}

.cardapio-pedidos__dot {
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

.cardapio-pedidos__dot svg {
  width: 15px;
}

.is-done .cardapio-pedidos__dot {
  border-color: var(--color-success-500);
  background: var(--color-success-500);
}

.is-current .cardapio-pedidos__dot {
  border-color: var(--color-primary-500);
  background: var(--color-primary-500);
  box-shadow: 0 0 0 5px var(--color-primary-100);
}

.cardapio-pedidos__items {
  display: grid;
  gap: 10px;
  padding-top: 16px;
  border-top: 1px dashed var(--color-border-dark);
}

.cardapio-pedidos__items li {
  display: flex;
  gap: 10px;
  font-size: 0.9rem;
}

.cardapio-pedidos__qty {
  min-width: 28px;
  color: var(--color-primary-600);
  font-weight: 700;
}

.cardapio-pedidos__name {
  flex: 1;
}

.cardapio-pedidos__totals {
  display: grid;
  gap: 6px;
}

.cardapio-pedidos__totals div {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  color: var(--color-text-secondary);
  font-size: 0.86rem;
}

.cardapio-pedidos__totals dd {
  text-align: right;
}

.cardapio-pedidos__totals .is-total {
  padding-top: 8px;
  border-top: 1px solid var(--color-border-light);
  color: var(--color-text-title);
  font-size: 1.05rem;
  font-weight: 700;
}

.cardapio-pedidos__list {
  display: grid;
  gap: 10px;
}

.cardapio-pedidos__order {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 14px;
  border: 1px solid var(--color-border-light);
  border-radius: 14px;
  text-align: left;
  cursor: pointer;
  transition: border-color 160ms ease;
}

.cardapio-pedidos__order-info {
  display: grid;
  flex: 1;
  gap: 4px;
  min-width: 0;
}

.cardapio-pedidos__order-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.cardapio-pedidos__order small {
  color: var(--color-text-tertiary);
  font-size: 0.78rem;
}

.cardapio-pedidos__order-items {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.cardapio-pedidos__chevron {
  flex-shrink: 0;
  width: 18px;
  color: var(--color-text-tertiary);
}

@media (hover: hover) and (pointer: fine) {
  .cardapio-pedidos__order:hover {
    border-color: var(--color-primary-200);
  }
}
</style>
