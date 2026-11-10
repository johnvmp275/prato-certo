<script setup>
import { computed, reactive, ref, watch } from 'vue';

import CommonInput from '@/components/Common/CommonInput/CommonInput.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonQuantity from '@/components/Common/CommonQuantity/CommonQuantity.vue';
import CommonFoodImage from '@/components/Common/CommonFoodImage/CommonFoodImage.vue';
import CommonEmptyState from '@/components/Common/CommonEmptyState/CommonEmptyState.vue';
import CommonMessageError from '@/components/Common/CommonMessageError/CommonMessageError.vue';

import IconX from '@/components/Icons/IconX.vue';
import IconShoppingBag from '@/components/Icons/IconShoppingBag.vue';
import IconStore from '@/components/Icons/IconStore.vue';
import IconTruck from '@/components/Icons/IconTruck.vue';
import IconArrowLeft from '@/components/Icons/IconArrowLeft.vue';
import IconBanknote from '@/components/Icons/IconBanknote.vue';
import IconCreditCard from '@/components/Icons/IconCreditCard.vue';
import IconQrCode from '@/components/Icons/IconQrCode.vue';
import IconUser from '@/components/Icons/IconUser.vue';
import IconTicket from '@/components/Icons/IconTicket.vue';
import IconTrash from '@/components/Icons/IconTrash.vue';

import { cardapioService } from '@/services/painel';
import { useCardapio } from '@/stores/useCardapio';
import { useConta } from '@/stores/useConta';
import { usePedidosCardapio } from '@/stores/usePedidosCardapio';
import { useDevice } from '@/composables/useDevice';
import { useFormulario } from '@/composables/useFormulario';
import { formatarMoeda } from '@/composables/useFormat';

defineOptions({
  name: 'CardapioCarrinho',
});

const props = defineProps({
  restaurante: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['close']);

const carrinho = useCardapio();
const conta = useConta();
const pedidosCardapio = usePedidosCardapio();
const { isDesktop } = useDevice();

// Formas de pagamento que o cliente escolhe no carrinho (paga na entrega/retirada)
const FORMAS_PAGAMENTO = [
  { value: 'pix', label: 'PIX', icon: IconQrCode },
  { value: 'cartao', label: 'Cartão', icon: IconCreditCard },
  { value: 'dinheiro', label: 'Dinheiro', icon: IconBanknote },
];

// Etapa 1: carrinho · Etapa 2: dados de entrega
const etapa = ref('carrinho');

const form = reactive({
  nomeContato: '',
  telefoneContato: '',
  tipoEntrega: 'retirada',
  enderecoEntrega: '',
  formaPagamento: '',
  trocoPara: '',
  observacao: '',
});

const { erros, erroGeral, salvando, validar, enviar } = useFormulario([
  'nomeContato',
  'telefoneContato',
  'tipoEntrega',
  'enderecoEntrega',
  'formaPagamento',
  'trocoPara',
  'cupom',
  'itens',
]);

/* ===========================
 * CUPOM DE DESCONTO (VALIDADO NA API; O DESCONTO É SOBRE O SUBTOTAL, NÃO SOBRE A ENTREGA)
 * =========================== */
const codigoCupom = ref('');
const cupom = ref(null);
const validandoCupom = ref(false);

async function aplicarCupom() {
  erros.cupom = '';
  if (!codigoCupom.value.trim()) {
    erros.cupom = 'Digite o código do cupom.';
    return;
  }

  validandoCupom.value = true;
  try {
    cupom.value = await cardapioService.validarCupom(codigoCupom.value.trim(), carrinho.subtotal);
    codigoCupom.value = '';
  } catch (error) {
    erros.cupom = error.mensagem;
  } finally {
    validandoCupom.value = false;
  }
}

function removerCupom() {
  cupom.value = null;
  erros.cupom = '';
}

// Mesmo cálculo do backend: percentual sobre o subtotal ou valor fixo, nunca maior que o subtotal
const desconto = computed(() => {
  if (!cupom.value) return 0;
  const valor = cupom.value.tipo === 'percentual' ? carrinho.subtotal * (cupom.value.valor / 100) : cupom.value.valor;
  return Math.round(Math.min(valor, carrinho.subtotal) * 100) / 100;
});

// Cliente logado: preenche os dados de contato (sem apagar o que ele já digitou)
watch(
  () => conta.cliente,
  cliente => {
    if (!cliente) return;
    form.nomeContato ||= cliente.nome ?? '';
    form.telefoneContato ||= cliente.telefone ?? '';
    form.enderecoEntrega ||= cliente.endereco ?? '';
  },
  { immediate: true },
);

const taxaEntrega = computed(() => (form.tipoEntrega === 'entrega' ? props.restaurante.taxaEntrega : 0));
const total = computed(() => Math.round((carrinho.subtotal - desconto.value + taxaEntrega.value) * 100) / 100);
const trocoPara = computed(() => Number(String(form.trocoPara).replace(/\./g, '').replace(',', '.')) || 0);

async function enviarPedido() {
  const valido = validar({
    nomeContato: form.nomeContato.trim().length >= 2 ? '' : 'Informe seu nome.',
    telefoneContato: form.telefoneContato.replace(/\D/g, '').length >= 10 ? '' : 'Informe seu telefone com DDD.',
    enderecoEntrega:
      form.tipoEntrega === 'retirada' || form.enderecoEntrega.trim().length >= 5 ? '' : 'Informe o endereço de entrega.',
    formaPagamento: form.formaPagamento ? '' : 'Escolha como vai pagar.',
    trocoPara:
      form.formaPagamento !== 'dinheiro' || !form.trocoPara || trocoPara.value >= total.value
        ? ''
        : `O troco deve ser para um valor maior que ${formatarMoeda(total.value)}.`,
  });
  if (!valido) return;

  const pedido = await enviar(() =>
    cardapioService.enviarPedido({
      ...form,
      trocoPara: form.formaPagamento === 'dinheiro' && form.trocoPara ? trocoPara.value : null,
      cupom: cupom.value?.codigo ?? null,
      itens: carrinho.itens.map(item => ({
        produtoId: item.produtoId,
        quantidade: item.quantidade,
        observacao: item.observacao,
      })),
    }),
  );

  // Cupom recusado no envio (ex.: expirou): volta para a etapa do carrinho, onde fica o campo
  if (!pedido && erros.cupom) {
    cupom.value = null;
    etapa.value = 'carrinho';
  }

  if (pedido) {
    carrinho.limpar();
    removerCupom();
    emit('close');
    // Continua no cardápio: o acompanhamento (e o PIX, se escolhido) abre na janela de pedidos
    pedidosCardapio.registrar(pedido.codigo);
    pedidosCardapio.abrirPedido(pedido.codigo);
  }
}
</script>

<template>
  <section class="cardapio-carrinho">
    <header class="cardapio-carrinho__header">
      <button
        v-if="etapa === 'dados' && !isDesktop"
        type="button"
        class="cardapio-carrinho__icon"
        aria-label="Voltar ao carrinho"
        @click="etapa = 'carrinho'"
      >
        <IconArrowLeft aria-hidden="true" />
      </button>
      <h2 class="cardapio-carrinho__title">{{ etapa === 'carrinho' ? 'Seu pedido' : 'Entrega e contato' }}</h2>
      <!-- Ícones só no celular (no desktop o carrinho fica fixo na lateral) -->
      <button
        v-if="!isDesktop"
        type="button"
        class="cardapio-carrinho__icon"
        aria-label="Fechar carrinho"
        @click="emit('close')"
      >
        <IconX aria-hidden="true" />
      </button>
    </header>

    <div class="cardapio-carrinho__body">
      <!-- ETAPA 1: ITENS -->
      <template v-if="etapa === 'carrinho'">
        <ul
          v-if="carrinho.itens.length"
          class="cardapio-carrinho__items"
        >
          <li
            v-for="(item, index) in carrinho.itens"
            :key="`${item.produtoId}-${index}`"
          >
            <!-- Foto do produto (sem imagem, mostra o ícone padrão) -->
            <CommonFoodImage
              :src="item.imagem"
              :alt="item.nome"
              :size="64"
            />
            <div class="cardapio-carrinho__item-body">
              <!-- Linha 1: nome e preço à esquerda, lixeira à direita -->
              <div class="cardapio-carrinho__item-head">
                <div class="cell-stack">
                  <strong>{{ item.nome }}</strong>
                  <small class="tabular">{{ formatarMoeda(item.preco) }}</small>
                </div>
                <!-- Lixeira sempre visível: remove o produto inteiro, com qualquer quantidade -->
                <button
                  type="button"
                  class="cardapio-carrinho__remove"
                  :aria-label="`Remover ${item.nome} do carrinho`"
                  @click="carrinho.alterarQuantidade(index, 0)"
                >
                  <IconTrash aria-hidden="true" />
                </button>
              </div>
              <!-- Linha 2: quantidade abaixo do nome e do preço -->
              <CommonQuantity
                size="sm"
                :min="1"
                :model-value="item.quantidade"
                @update:model-value="carrinho.alterarQuantidade(index, $event)"
              />
            </div>
          </li>
        </ul>

        <!-- CUPOM DE DESCONTO -->
        <div
          v-if="carrinho.itens.length"
          class="cardapio-carrinho__coupon"
        >
          <div
            v-if="cupom"
            class="cardapio-carrinho__coupon-applied"
          >
            <IconTicket aria-hidden="true" />
            <span class="cell-stack">
              <strong>{{ cupom.codigo }}</strong>
              <small>{{ cupom.descricao }}</small>
            </span>
            <button
              type="button"
              class="cardapio-carrinho__coupon-remove"
              @click="removerCupom"
            >
              Remover
            </button>
          </div>
          <form
            v-else
            class="cardapio-carrinho__coupon-form"
            novalidate
            @submit.prevent="aplicarCupom"
          >
            <CommonInput
              v-model="codigoCupom"
              label="Cupom de desconto"
              placeholder="Digite o código"
              autocomplete="off"
              :error="erros.cupom"
              @input="codigoCupom = codigoCupom.toUpperCase()"
            />
            <CommonButton
              type="submit"
              variant="secondary"
              :loading="validandoCupom"
            >
              Aplicar
            </CommonButton>
          </form>
        </div>

        <CommonEmptyState
          v-else
          :icon="IconShoppingBag"
          title="Seu carrinho está vazio"
          description="Escolha os pratos no cardápio."
        />
      </template>

      <!-- ETAPA 2: DADOS -->
      <form
        v-else
        class="cardapio-carrinho__form"
        novalidate
        @submit.prevent="enviarPedido"
      >
        <!-- ATALHO PARA A CONTA (LOGIN OPCIONAL) -->
        <button
          v-if="!conta.isLogado"
          type="button"
          class="cardapio-carrinho__login"
          @click="conta.aberta = true"
        >
          <IconUser aria-hidden="true" />
          <span>Já tem conta? <strong>Entre</strong> para preencher seus dados.</span>
        </button>

        <div
          class="cardapio-carrinho__delivery"
          role="radiogroup"
          aria-label="Tipo de entrega"
        >
          <button
            type="button"
            role="radio"
            :class="{ 'is-active': form.tipoEntrega === 'retirada' }"
            :aria-checked="form.tipoEntrega === 'retirada'"
            @click="form.tipoEntrega = 'retirada'"
          >
            <IconStore aria-hidden="true" />
            Retirar no local
          </button>
          <button
            type="button"
            role="radio"
            :class="{ 'is-active': form.tipoEntrega === 'entrega' }"
            :aria-checked="form.tipoEntrega === 'entrega'"
            @click="form.tipoEntrega = 'entrega'"
          >
            <IconTruck aria-hidden="true" />
            Entrega · {{ formatarMoeda(restaurante.taxaEntrega) }}
          </button>
        </div>

        <CommonInput
          v-model="form.nomeContato"
          label="Seu nome"
          autocomplete="name"
          :error="erros.nomeContato"
          required
        />
        <CommonInput
          v-model="form.telefoneContato"
          label="Telefone / WhatsApp"
          mask="(##) #####-####"
          inputmode="tel"
          autocomplete="tel"
          :error="erros.telefoneContato"
          required
        />
        <CommonInput
          v-if="form.tipoEntrega === 'entrega'"
          v-model="form.enderecoEntrega"
          label="Endereço de entrega"
          placeholder="Rua, número, bairro e complemento"
          autocomplete="street-address"
          :error="erros.enderecoEntrega"
          required
        />

        <!-- FORMA DE PAGAMENTO (O CLIENTE PAGA NA ENTREGA/RETIRADA) -->
        <fieldset class="cardapio-carrinho__payment">
          <legend class="cardapio-carrinho__legend">
            Como você vai pagar? <span class="cardapio-carrinho__required">*</span>
          </legend>
          <div
            class="cardapio-carrinho__delivery cardapio-carrinho__delivery--3"
            role="radiogroup"
            aria-label="Forma de pagamento"
          >
            <button
              v-for="opcao in FORMAS_PAGAMENTO"
              :key="opcao.value"
              type="button"
              role="radio"
              :class="{ 'is-active': form.formaPagamento === opcao.value }"
              :aria-checked="form.formaPagamento === opcao.value"
              @click="form.formaPagamento = opcao.value"
            >
              <component
                :is="opcao.icon"
                aria-hidden="true"
              />
              {{ opcao.label }}
            </button>
          </div>
          <CommonMessageError :error="erros.formaPagamento" />
        </fieldset>
        <CommonInput
          v-if="form.formaPagamento === 'dinheiro'"
          v-model="form.trocoPara"
          label="Troco para quanto?"
          inputmode="decimal"
          placeholder="Ex: 100,00"
          hint="Deixe vazio se não precisar de troco"
          :error="erros.trocoPara"
        />

        <CommonInput
          v-model="form.observacao"
          label="Observação"
          mode="textarea"
          maxlength="255"
          placeholder="Ex: sem cebola, interfone quebrado..."
        />
        <CommonMessageError :error="erros.itens || erroGeral" />
      </form>
    </div>

    <footer
      v-if="carrinho.itens.length"
      class="cardapio-carrinho__footer"
    >
      <dl class="cardapio-carrinho__totals">
        <div>
          <dt>Subtotal</dt>
          <dd class="tabular">{{ formatarMoeda(carrinho.subtotal) }}</dd>
        </div>
        <div
          v-if="desconto"
          class="is-discount"
        >
          <dt>Desconto ({{ cupom.codigo }})</dt>
          <dd class="tabular">- {{ formatarMoeda(desconto) }}</dd>
        </div>
        <div v-if="etapa === 'dados'">
          <dt>Taxa de entrega</dt>
          <dd class="tabular">{{ formatarMoeda(taxaEntrega) }}</dd>
        </div>
        <div class="is-total">
          <dt>Total</dt>
          <dd class="tabular">{{ formatarMoeda(etapa === 'dados' ? total : carrinho.subtotal - desconto) }}</dd>
        </div>
      </dl>

      <p
        v-if="!restaurante.aberto"
        class="cardapio-carrinho__closed"
      >
        O restaurante não está recebendo pedidos agora.
      </p>

      <CommonButton
        v-if="etapa === 'carrinho'"
        size="lg"
        block
        :disabled="!restaurante.aberto"
        @click="etapa = 'dados'"
      >
        Continuar
      </CommonButton>
      <CommonButton
        v-else
        variant="success"
        size="lg"
        block
        :loading="salvando"
        :disabled="!restaurante.aberto"
        @click="enviarPedido"
      >
        Enviar pedido
      </CommonButton>
      <!-- No desktop substitui a seta do cabeçalho -->
      <CommonButton
        v-if="etapa === 'dados' && isDesktop"
        variant="ghost"
        block
        @click="etapa = 'carrinho'"
      >
        Voltar ao carrinho
      </CommonButton>
    </footer>
  </section>
</template>

<style scoped>
.cardapio-carrinho {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--color-surface);
}

.cardapio-carrinho__header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 18px 20px;
  border-bottom: 1px solid var(--color-border-light);
}

.cardapio-carrinho__title {
  flex: 1;
  font-size: 1.1rem;
}

.cardapio-carrinho__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: var(--color-neutral-100);
  color: var(--color-text-secondary);
}

.cardapio-carrinho__icon svg {
  width: 18px;
}

.cardapio-carrinho__body {
  flex: 1;
  overflow-y: auto;
  padding: 16px 20px;
}

.cardapio-carrinho__items {
  display: grid;
}

.cardapio-carrinho__items li {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px dashed var(--color-border-default);
}

.cardapio-carrinho__item-body {
  display: flex;
  flex: 1;
  flex-direction: column;
  align-items: flex-start;
  gap: 8px;
  min-width: 0;
}

.cardapio-carrinho__item-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
}

/* Lixeira no canto superior direito de cada produto */
.cardapio-carrinho__remove {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 8px;
  color: var(--color-text-tertiary);
  cursor: pointer;
  transition:
    background-color 160ms ease,
    color 160ms ease;
}

.cardapio-carrinho__remove svg {
  width: 16px;
}

@media (hover: hover) and (pointer: fine) {
  .cardapio-carrinho__remove:hover {
    background: var(--color-error-50);
    color: var(--color-error-600);
  }
}

.cardapio-carrinho__form {
  display: grid;
  gap: 14px;
}

.cardapio-carrinho__delivery {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.cardapio-carrinho__delivery button {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 12px 8px;
  border: 1.5px solid var(--color-border-default);
  border-radius: 14px;
  color: var(--color-text-secondary);
  font-size: 0.8rem;
  font-weight: 600;
}

.cardapio-carrinho__delivery svg {
  width: 22px;
}

.cardapio-carrinho__delivery--3 {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.cardapio-carrinho__payment {
  display: grid;
  gap: 8px;
  min-width: 0;
  margin: 0;
  padding: 0;
  border: 0;
}

.cardapio-carrinho__legend {
  margin-bottom: 8px;
  padding: 0;
  color: var(--color-text-title);
  font-size: 0.86rem;
  font-weight: 600;
}

.cardapio-carrinho__required {
  color: var(--color-error-500);
}

.cardapio-carrinho__login {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 12px;
  background: var(--color-primary-50);
  color: var(--color-primary-700);
  font-size: 0.82rem;
  text-align: left;
  cursor: pointer;
}

.cardapio-carrinho__login svg {
  flex-shrink: 0;
  width: 18px;
}

.cardapio-carrinho__delivery button.is-active {
  border-color: var(--color-primary-500);
  background: var(--color-primary-50);
  color: var(--color-primary-700);
}

.cardapio-carrinho__footer {
  display: grid;
  gap: 12px;
  padding: 16px 20px 20px;
  border-top: 1px solid var(--color-border-light);
}

.cardapio-carrinho__totals {
  display: grid;
  gap: 6px;
}

.cardapio-carrinho__totals div {
  display: flex;
  justify-content: space-between;
  color: var(--color-text-secondary);
  font-size: 0.86rem;
}

.cardapio-carrinho__totals .is-discount {
  color: var(--color-success-700);
}

.cardapio-carrinho__coupon {
  margin-top: 16px;
}

.cardapio-carrinho__coupon-form {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: start;
  gap: 8px;
}

/* Alinha o botão com o campo (o rótulo fica acima do input) */
.cardapio-carrinho__coupon-form > button {
  margin-top: 24px;
}

.cardapio-carrinho__coupon-applied {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border: 1.5px dashed var(--color-success-500);
  border-radius: 12px;
  background: var(--color-success-50);
  color: var(--color-success-700);
}

.cardapio-carrinho__coupon-applied svg {
  flex-shrink: 0;
  width: 20px;
}

.cardapio-carrinho__coupon-applied .cell-stack {
  flex: 1;
}

.cardapio-carrinho__coupon-remove {
  color: var(--color-text-secondary);
  font-size: 0.8rem;
  font-weight: 600;
  text-decoration: underline;
  cursor: pointer;
}

.cardapio-carrinho__totals .is-total {
  color: var(--color-text-title);
  font-size: 1rem;
  font-weight: 700;
}

.cardapio-carrinho__closed {
  padding: 10px 12px;
  border-radius: 10px;
  background: var(--color-error-50);
  color: var(--color-error-700);
  font-size: 0.82rem;
  text-align: center;
}
</style>
