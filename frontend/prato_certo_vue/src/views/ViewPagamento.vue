<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';

import { useRoute, useRouter } from 'vue-router';

import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonLoader from '@/components/Common/CommonLoader/CommonLoader.vue';
import CommonStatusBadge from '@/components/Common/CommonStatusBadge/CommonStatusBadge.vue';
import CommonSelect from '@/components/Common/CommonSelect/CommonSelect.vue';
import CommonMessageError from '@/components/Common/CommonMessageError/CommonMessageError.vue';

import PedidoResumoValores from '@/components/Pedidos/PedidoResumoValores/PedidoResumoValores.vue';
import PagamentoTeclado from '@/components/Pagamento/PagamentoTeclado/PagamentoTeclado.vue';
import PagamentoDesconto from '@/components/Pagamento/PagamentoDesconto/PagamentoDesconto.vue';

import IconArrowLeft from '@/components/Icons/IconArrowLeft.vue';
import IconBanknote from '@/components/Icons/IconBanknote.vue';
import IconCreditCard from '@/components/Icons/IconCreditCard.vue';
import IconQrCode from '@/components/Icons/IconQrCode.vue';
import IconCheck from '@/components/Icons/IconCheck.vue';

import { pedidosService } from '@/services/pedidos';
import { useToast } from '@/stores/useToast';
import { formatarMoeda, formatarTelefone } from '@/composables/useFormat';

defineOptions({
  name: 'ViewPagamento',
});

const route = useRoute();
const router = useRouter();
const toast = useToast();

const FORMAS = [
  { value: 'dinheiro', label: 'Dinheiro', icon: IconBanknote },
  { value: 'cartao', label: 'Cartão', icon: IconCreditCard },
  { value: 'pix', label: 'PIX', icon: IconQrCode },
];

const BANDEIRAS = ['Visa', 'Mastercard', 'Elo', 'American Express', 'Hipercard'].map(nome => ({
  value: nome,
  label: nome,
}));

const pedido = ref(null);
const carregando = ref(true);
const forma = ref('dinheiro');
const modalidade = ref('credito');
const bandeira = ref('Visa');
const centavos = ref('');
const erro = ref('');
const pagando = ref(false);

const valor = computed(() => Number(centavos.value || 0) / 100);
const restante = computed(() => pedido.value?.valores.restante ?? 0);
const troco = computed(() => (forma.value === 'dinheiro' ? Math.max(0, valor.value - restante.value) : 0));
const encerrado = computed(() => ['finalizado', 'cancelado'].includes(pedido.value?.status));

// Atalhos para dinheiro: valor exato e notas comuns acima do restante
const atalhos = computed(() => {
  const notas = [10, 20, 50, 100, 200].filter(nota => nota > restante.value).slice(0, 3);
  return [restante.value, ...notas];
});

function definirValor(reais) {
  centavos.value = String(Math.round(reais * 100));
}

async function carregar() {
  carregando.value = true;
  try {
    pedido.value = await pedidosService.buscar(route.params.id);
    // Pedido online: já seleciona a forma que o cliente escolheu no carrinho
    const previsto = pedido.value.pagamentoPrevisto;
    if (previsto && !pedido.value.pagamentos.length) forma.value = previsto.forma;
    // Espera o watch(forma) rodar antes, senão ele troca o valor de volta para o restante
    await nextTick();
    definirValor(previsto?.trocoPara && forma.value === 'dinheiro' ? previsto.trocoPara : pedido.value.valores.restante);
  } catch (error) {
    toast.error(error.mensagem);
    router.replace('/painel/pedidos');
  } finally {
    carregando.value = false;
  }
}

function pedidoAtualizado(atualizado) {
  pedido.value = atualizado;
  definirValor(atualizado.valores.restante);
}

async function confirmar() {
  erro.value = '';

  // Validações locais (o backend valida as mesmas regras)
  if (valor.value <= 0) {
    erro.value = 'Informe um valor maior que zero.';
    return;
  }
  if (forma.value !== 'dinheiro' && valor.value > restante.value) {
    erro.value = `No ${forma.value === 'pix' ? 'PIX' : 'cartão'} o valor não pode passar de ${formatarMoeda(restante.value)}.`;
    return;
  }

  pagando.value = true;
  try {
    const resposta = await pedidosService.pagar(pedido.value.id, {
      forma: forma.value,
      valor: valor.value,
      modalidade: forma.value === 'cartao' ? modalidade.value : undefined,
      bandeira: forma.value === 'cartao' ? bandeira.value : undefined,
    });

    // A tela continua a mesma: o resumo e o status são atualizados com o pedido retornado
    pedido.value = resposta.pedido;
    if (resposta.finalizado) {
      const troco = resposta.troco ? ` Troco: ${formatarMoeda(resposta.troco)}.` : '';
      toast.success(`Pagamento concluído. Pedido #${resposta.pedido.codigo} finalizado.${troco}`);
    } else {
      toast.success(`Pagamento de ${formatarMoeda(resposta.pagamento.valor)} registrado.`);
      definirValor(resposta.pedido.valores.restante);
    }
  } catch (error) {
    erro.value = error.mensagem;
  } finally {
    pagando.value = false;
  }
}

watch(forma, () => {
  erro.value = '';
  definirValor(restante.value);
});

onMounted(carregar);
</script>

<template>
  <main class="view-pagamento view-page row">
    <button
      type="button"
      class="view-pagamento__back"
      @click="router.back()"
    >
      <IconArrowLeft aria-hidden="true" />
      Voltar
    </button>

    <CommonLoader v-if="carregando" />

    <div
      v-else-if="pedido"
      class="view-pagamento__layout"
      :class="{ 'is-encerrado': encerrado }"
    >
      <!-- RESUMO DO PEDIDO -->
      <section class="view-pagamento__summary card">
        <header class="view-pagamento__summary-header">
          <div>
            <h1 class="view-pagamento__title">Pedido #{{ pedido.codigo }}</h1>
            <p class="view-pagamento__origin">
              {{ pedido.origem }}
              <template v-if="pedido.cliente"> · {{ pedido.cliente.nome }}</template>
              <template v-else-if="pedido.contato"> · {{ pedido.contato.nome }} {{ formatarTelefone(pedido.contato.telefone) }}</template>
            </p>
          </div>
          <CommonStatusBadge :status="pedido.status" />
        </header>

        <ul class="view-pagamento__items">
          <li
            v-for="item in pedido.itens"
            :key="item.id"
          >
            <span class="view-pagamento__qty">{{ item.quantidade }}x</span>
            <span class="view-pagamento__item-name">{{ item.nomeProduto }}</span>
            <span class="tabular">{{ formatarMoeda(item.subtotal) }}</span>
          </li>
        </ul>

        <PedidoResumoValores :valores="pedido.valores" />

        <ul
          v-if="pedido.pagamentos.length"
          class="view-pagamento__payments"
        >
          <li
            v-for="pagamento in pedido.pagamentos"
            :key="pagamento.id"
          >
            <IconCheck aria-hidden="true" />
            <span>{{ pagamento.descricao }}</span>
            <span class="tabular">{{ formatarMoeda(pagamento.valor) }}</span>
          </li>
        </ul>

        <PagamentoDesconto
          v-if="!encerrado"
          :pedido="pedido"
          @atualizado="pedidoAtualizado"
        />
      </section>

      <!-- RECEBIMENTO (some quando o pedido já foi encerrado) -->
      <section
        v-if="!encerrado"
        class="view-pagamento__checkout card"
      >
        <div class="view-pagamento__due">
          <span>Valor a receber</span>
          <strong class="tabular">{{ formatarMoeda(restante) }}</strong>
        </div>

        <div
          class="view-pagamento__methods"
          role="radiogroup"
          aria-label="Forma de pagamento"
        >
          <button
            v-for="item in FORMAS"
            :key="item.value"
            type="button"
            role="radio"
            class="view-pagamento__method"
            :class="{ 'is-active': forma === item.value }"
            :aria-checked="forma === item.value"
            @click="forma = item.value"
          >
            <component
              :is="item.icon"
              aria-hidden="true"
            />
            {{ item.label }}
          </button>
        </div>

        <div
          v-if="forma === 'cartao'"
          class="view-pagamento__card-options"
        >
          <div
            class="view-pagamento__toggle"
            role="radiogroup"
            aria-label="Modalidade"
          >
            <button
              v-for="opcao in ['credito', 'debito']"
              :key="opcao"
              type="button"
              role="radio"
              :class="{ 'is-active': modalidade === opcao }"
              :aria-checked="modalidade === opcao"
              @click="modalidade = opcao"
            >
              {{ opcao === 'credito' ? 'Crédito' : 'Débito' }}
            </button>
          </div>
          <CommonSelect
            v-model="bandeira"
            :options="BANDEIRAS"
            aria-label="Bandeira do cartão"
          />
        </div>

        <div class="view-pagamento__display">
          <span class="view-pagamento__display-label">{{ forma === 'dinheiro' ? 'Valor recebido' : 'Valor' }}</span>
          <span class="view-pagamento__display-value tabular">{{ formatarMoeda(valor) }}</span>
          <span
            v-if="troco > 0"
            class="view-pagamento__display-change tabular"
          >
            Troco: {{ formatarMoeda(troco) }}
          </span>
        </div>

        <div
          v-if="forma === 'dinheiro'"
          class="view-pagamento__shortcuts"
        >
          <button
            v-for="(atalho, index) in atalhos"
            :key="atalho"
            type="button"
            @click="definirValor(atalho)"
          >
            {{ index === 0 ? 'Exato' : formatarMoeda(atalho).replace(',00', '') }}
          </button>
        </div>

        <PagamentoTeclado v-model="centavos" />

        <CommonMessageError :error="erro" />

        <CommonButton
          variant="success"
          size="lg"
          block
          :loading="pagando"
          @click="confirmar"
        >
          <template #icon><IconCheck /></template>
          Confirmar {{ formatarMoeda(Math.min(valor, forma === 'dinheiro' ? valor : restante)) }}
        </CommonButton>
      </section>
    </div>

  </main>
</template>

<style scoped>
.view-pagamento__back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  align-self: flex-start;
  color: var(--color-text-secondary);
  font-size: 0.88rem;
  font-weight: 600;
}

.view-pagamento__back svg {
  width: 18px;
}

.view-pagamento__layout {
  display: grid;
  gap: 20px;
  align-items: start;
}

.view-pagamento__summary,
.view-pagamento__checkout {
  display: flex;
  flex-direction: column;
  gap: 18px;
  padding: 22px;
}

.view-pagamento__summary-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.view-pagamento__title {
  font-size: 1.25rem;
}

.view-pagamento__origin {
  color: var(--color-text-tertiary);
  font-size: 0.84rem;
}

.view-pagamento__items {
  display: grid;
  gap: 10px;
  max-height: 260px;
  overflow-y: auto;
}

.view-pagamento__items li {
  display: flex;
  gap: 10px;
  font-size: 0.88rem;
}

.view-pagamento__qty {
  min-width: 30px;
  color: var(--color-primary-600);
  font-weight: 700;
}

.view-pagamento__item-name {
  flex: 1;
  color: var(--color-text-title);
}

.view-pagamento__payments {
  display: grid;
  gap: 6px;
}

.view-pagamento__payments li {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--color-success-700);
  font-size: 0.84rem;
}

.view-pagamento__payments svg {
  width: 15px;
}

.view-pagamento__payments span:last-child {
  margin-left: auto;
  font-weight: 600;
}

.view-pagamento__due {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 18px;
  border-radius: 16px;
  background: var(--color-primary-50);
  color: var(--color-primary-700);
  font-weight: 600;
}

.view-pagamento__due strong {
  font-family: var(--font-title);
  font-size: 1.6rem;
}

.view-pagamento__methods {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
}

.view-pagamento__method {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 12px 6px;
  border: 1.5px solid var(--color-border-default);
  border-radius: 14px;
  color: var(--color-text-secondary);
  font-size: 0.84rem;
  font-weight: 600;
  transition:
    border-color 160ms ease,
    background-color 160ms ease,
    color 160ms ease;
}

.view-pagamento__method svg {
  width: 22px;
}

.view-pagamento__method.is-active {
  border-color: var(--color-primary-500);
  background: var(--color-primary-50);
  color: var(--color-primary-600);
}

.view-pagamento__card-options {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  gap: 10px;
}

.view-pagamento__toggle {
  display: flex;
  padding: 3px;
  border-radius: 12px;
  background: var(--color-neutral-100);
}

.view-pagamento__toggle button {
  padding: 0 14px;
  border-radius: 10px;
  color: var(--color-text-secondary);
  font-size: 0.84rem;
  font-weight: 600;
}

.view-pagamento__toggle button.is-active {
  background: var(--color-surface);
  color: var(--color-text-title);
  box-shadow: var(--shadow-xs);
}

.view-pagamento__display {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  padding: 14px 18px;
  border: 1px solid var(--color-border-default);
  border-radius: 16px;
}

.view-pagamento__display-label {
  color: var(--color-text-tertiary);
  font-size: 0.78rem;
}

.view-pagamento__display-value {
  color: var(--color-text-title);
  font-family: var(--font-title);
  font-size: 2rem;
  font-weight: 600;
  line-height: 1.2;
}

.view-pagamento__display-change {
  color: var(--color-success-600);
  font-size: 0.9rem;
  font-weight: 700;
}

.view-pagamento__shortcuts {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 8px;
}

.view-pagamento__shortcuts button {
  min-height: 38px;
  border-radius: 10px;
  background: var(--color-neutral-100);
  color: var(--color-text-primary);
  font-size: 0.82rem;
  font-weight: 600;
}

@media (hover: hover) and (pointer: fine) {
  .view-pagamento__method:not(.is-active):hover {
    border-color: var(--color-primary-200);
  }
  .view-pagamento__shortcuts button:hover {
    background: var(--color-primary-50);
    color: var(--color-primary-700);
  }
}

@media (min-width: 1024px) {
  .view-pagamento__layout {
    grid-template-columns: minmax(0, 1fr) 420px;
  }
  .view-pagamento__layout.is-encerrado {
    grid-template-columns: minmax(0, 640px);
    justify-content: center;
  }
}
</style>
