<script setup>
import { computed, reactive, ref } from 'vue';

import { useRouter } from 'vue-router';

import CommonInput from '@/components/Common/CommonInput/CommonInput.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonQuantity from '@/components/Common/CommonQuantity/CommonQuantity.vue';
import CommonEmptyState from '@/components/Common/CommonEmptyState/CommonEmptyState.vue';
import CommonMessageError from '@/components/Common/CommonMessageError/CommonMessageError.vue';

import IconX from '@/components/Icons/IconX.vue';
import IconShoppingBag from '@/components/Icons/IconShoppingBag.vue';
import IconStore from '@/components/Icons/IconStore.vue';
import IconTruck from '@/components/Icons/IconTruck.vue';
import IconArrowLeft from '@/components/Icons/IconArrowLeft.vue';

import { cardapioService } from '@/services/painel';
import { useCardapio } from '@/stores/useCardapio';
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
const router = useRouter();

// Etapa 1: carrinho · Etapa 2: dados de entrega
const etapa = ref('carrinho');

const form = reactive({
  nomeContato: '',
  telefoneContato: '',
  tipoEntrega: 'retirada',
  enderecoEntrega: '',
  observacao: '',
});

const { erros, erroGeral, salvando, validar, enviar } = useFormulario([
  'nomeContato',
  'telefoneContato',
  'tipoEntrega',
  'enderecoEntrega',
  'itens',
]);

const taxaEntrega = computed(() => (form.tipoEntrega === 'entrega' ? props.restaurante.taxaEntrega : 0));
const total = computed(() => carrinho.subtotal + taxaEntrega.value);

async function enviarPedido() {
  const valido = validar({
    nomeContato: form.nomeContato.trim().length >= 2 ? '' : 'Informe seu nome.',
    telefoneContato: form.telefoneContato.replace(/\D/g, '').length >= 10 ? '' : 'Informe seu telefone com DDD.',
    enderecoEntrega:
      form.tipoEntrega === 'retirada' || form.enderecoEntrega.trim().length >= 5 ? '' : 'Informe o endereço de entrega.',
  });
  if (!valido) return;

  const pedido = await enviar(() =>
    cardapioService.enviarPedido({
      ...form,
      itens: carrinho.itens.map(item => ({
        produtoId: item.produtoId,
        quantidade: item.quantidade,
        observacao: item.observacao,
      })),
    }),
  );

  if (pedido) {
    carrinho.limpar();
    emit('close');
    router.push(`/cardapio/pedido/${pedido.codigo}`);
  }
}
</script>

<template>
  <section class="cardapio-carrinho">
    <header class="cardapio-carrinho__header">
      <button
        v-if="etapa === 'dados'"
        type="button"
        class="cardapio-carrinho__icon"
        aria-label="Voltar ao carrinho"
        @click="etapa = 'carrinho'"
      >
        <IconArrowLeft aria-hidden="true" />
      </button>
      <h2 class="cardapio-carrinho__title">{{ etapa === 'carrinho' ? 'Seu pedido' : 'Entrega e contato' }}</h2>
      <button
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
            <div class="cell-stack">
              <strong>{{ item.nome }}</strong>
              <small class="tabular">{{ formatarMoeda(item.preco) }}</small>
            </div>
            <CommonQuantity
              size="sm"
              :model-value="item.quantidade"
              @update:model-value="carrinho.alterarQuantidade(index, $event)"
            />
          </li>
        </ul>
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
        <CommonInput
          v-model="form.observacao"
          label="Observação"
          mode="textarea"
          maxlength="255"
          placeholder="Ex: troco para R$ 100, sem cebola..."
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
        <div v-if="etapa === 'dados'">
          <dt>Taxa de entrega</dt>
          <dd class="tabular">{{ formatarMoeda(taxaEntrega) }}</dd>
        </div>
        <div class="is-total">
          <dt>Total</dt>
          <dd class="tabular">{{ formatarMoeda(etapa === 'dados' ? total : carrinho.subtotal) }}</dd>
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
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px dashed var(--color-border-default);
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
