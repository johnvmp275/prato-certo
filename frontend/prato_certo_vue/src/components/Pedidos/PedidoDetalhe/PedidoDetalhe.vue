<script setup>
import { computed, ref } from 'vue';

import { useRouter } from 'vue-router';

import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonStatusBadge from '@/components/Common/CommonStatusBadge/CommonStatusBadge.vue';
import CommonDialog from '@/components/Common/CommonDialog/CommonDialog.vue';

import PedidoResumoValores from '@/components/Pedidos/PedidoResumoValores/PedidoResumoValores.vue';

import IconCheck from '@/components/Icons/IconCheck.vue';
import IconGrid from '@/components/Icons/IconGrid.vue';
import IconPlay from '@/components/Icons/IconPlay.vue';
import IconPause from '@/components/Icons/IconPause.vue';
import IconDollar from '@/components/Icons/IconDollar.vue';
import IconPhone from '@/components/Icons/IconPhone.vue';
import IconMapPin from '@/components/Icons/IconMapPin.vue';
import IconUser from '@/components/Icons/IconUser.vue';

import { pedidosService } from '@/services/pedidos';
import { useAuth } from '@/stores/useAuth';
import { useToast } from '@/stores/useToast';
import { formatarMoeda, formatarDataHora, formatarTelefone } from '@/composables/useFormat';

defineOptions({
  name: 'PedidoDetalhe',
});

const props = defineProps({
  pedido: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['atualizado']);

const auth = useAuth();
const toast = useToast();
const router = useRouter();

const processando = ref('');
const confirmarCancelamento = ref(false);

const encerrado = computed(() => ['finalizado', 'cancelado'].includes(props.pedido.status));
const contato = computed(() => props.pedido.contato ?? null);

async function executar(acao, operacao, mensagem) {
  processando.value = acao;
  try {
    const pedido = await operacao();
    if (mensagem) toast.success(mensagem);
    emit('atualizado', pedido);
    return pedido;
  } catch (error) {
    toast.error(error.mensagem);
    return null;
  } finally {
    processando.value = '';
  }
}

const aceitar = () => executar('aceitar', () => pedidosService.aceitar(props.pedido.id), 'Pedido online aceito.');
const espera = () => executar('espera', () => pedidosService.colocarEmEspera(props.pedido.id), 'Pedido colocado em espera.');

async function retomar() {
  const pedido = await executar('retomar', () => pedidosService.retomar(props.pedido.id), 'Pedido retomado.');
  if (pedido) router.push(`/painel/pdv/${pedido.id}`);
}

async function cancelar(close) {
  await executar('cancelar', () => pedidosService.cancelar(props.pedido.id), 'Pedido cancelado.');
  close();
}
</script>

<template>
  <article class="pedido-detalhe">
    <header class="pedido-detalhe__header">
      <div>
        <p class="pedido-detalhe__eyebrow">{{ pedido.origem }}</p>
        <h2 class="pedido-detalhe__title">Pedido #{{ pedido.codigo }}</h2>
        <p class="pedido-detalhe__date">{{ formatarDataHora(pedido.criadoEm) }}</p>
      </div>
      <CommonStatusBadge :status="pedido.status" />
    </header>

    <!-- CLIENTE / CONTATO -->
    <div
      v-if="pedido.cliente || contato"
      class="pedido-detalhe__contact"
    >
      <p>
        <IconUser aria-hidden="true" />
        {{ pedido.cliente?.nome ?? contato?.nome }}
      </p>
      <p v-if="pedido.cliente?.telefone || contato?.telefone">
        <IconPhone aria-hidden="true" />
        {{ formatarTelefone(pedido.cliente?.telefone ?? contato?.telefone) }}
      </p>
      <p v-if="contato?.endereco">
        <IconMapPin aria-hidden="true" />
        {{ contato.endereco }}
      </p>
      <!-- FORMA DE PAGAMENTO ESCOLHIDA PELO CLIENTE NO CARDÁPIO -->
      <p v-if="pedido.pagamentoPrevisto">
        <IconDollar aria-hidden="true" />
        Vai pagar com <strong>{{ pedido.pagamentoPrevisto.descricao }}</strong>
        <template v-if="pedido.pagamentoPrevisto.trocoPara">
          · troco para {{ formatarMoeda(pedido.pagamentoPrevisto.trocoPara) }}
        </template>
      </p>
    </div>

    <!-- ITENS -->
    <ul class="pedido-detalhe__items">
      <li
        v-for="item in pedido.itens"
        :key="item.id"
      >
        <span class="pedido-detalhe__qty">{{ item.quantidade }}x</span>
        <span class="pedido-detalhe__item-name">
          {{ item.nomeProduto }}
          <small v-if="item.observacao">“{{ item.observacao }}”</small>
        </span>
        <span class="tabular">{{ formatarMoeda(item.subtotal) }}</span>
      </li>
      <li
        v-if="!pedido.itens?.length"
        class="pedido-detalhe__empty"
      >
        Nenhum item lançado.
      </li>
    </ul>

    <p
      v-if="pedido.observacao"
      class="pedido-detalhe__note"
    >
      <strong>Obs:</strong> {{ pedido.observacao }}
    </p>

    <PedidoResumoValores :valores="pedido.valores" />

    <!-- PAGAMENTOS -->
    <ul
      v-if="pedido.pagamentos?.length"
      class="pedido-detalhe__payments"
    >
      <li
        v-for="pagamento in pedido.pagamentos"
        :key="pagamento.id"
      >
        <span>{{ pagamento.descricao }}</span>
        <span class="tabular">
          {{ formatarMoeda(pagamento.valor) }}
          <small v-if="pagamento.troco">(troco {{ formatarMoeda(pagamento.troco) }})</small>
        </span>
      </li>
    </ul>

    <!-- AÇÕES DE ACORDO COM O STATUS -->
    <div
      v-if="!encerrado"
      class="pedido-detalhe__actions"
    >
      <template v-if="pedido.status === 'pendente'">
        <CommonButton
          variant="success"
          block
          :loading="processando === 'aceitar'"
          @click="aceitar"
        >
          <template #icon><IconCheck /></template>
          Aceitar pedido
        </CommonButton>
      </template>

      <template v-else>
        <CommonButton
          v-if="pedido.status === 'em_espera'"
          variant="soft"
          :loading="processando === 'retomar'"
          @click="retomar"
        >
          <template #icon><IconPlay /></template>
          Retomar
        </CommonButton>
        <template v-else>
          <CommonButton
            variant="secondary"
            @click="router.push(`/painel/pdv/${pedido.id}`)"
          >
            <template #icon><IconGrid /></template>
            Abrir no PDV
          </CommonButton>
          <CommonButton
            variant="soft"
            :loading="processando === 'espera'"
            @click="espera"
          >
            <template #icon><IconPause /></template>
            Em espera
          </CommonButton>
        </template>
        <CommonButton
          variant="success"
          class="pedido-detalhe__pay"
          :disabled="!pedido.itens?.length"
          @click="router.push(`/painel/pedidos/${pedido.id}/pagamento`)"
        >
          <template #icon><IconDollar /></template>
          Pagar {{ formatarMoeda(pedido.valores.restante) }}
        </CommonButton>
      </template>

      <CommonButton
        v-if="auth.pode('pedidos.cancelar')"
        variant="danger"
        size="sm"
        class="pedido-detalhe__cancel"
        @click="confirmarCancelamento = true"
      >
        Cancelar pedido
      </CommonButton>
    </div>

    <CommonDialog
      v-if="confirmarCancelamento"
      title="Cancelar pedido?"
      :description="`O pedido #${pedido.codigo} será cancelado e não poderá mais ser alterado.`"
      :width="420"
      @close="confirmarCancelamento = false"
    >
      <template #footer="{ close }">
        <CommonButton
          variant="secondary"
          @click="close"
        >
          Voltar
        </CommonButton>
        <CommonButton
          variant="danger"
          :loading="processando === 'cancelar'"
          @click="cancelar(close)"
        >
          Sim, cancelar
        </CommonButton>
      </template>
    </CommonDialog>
  </article>
</template>

<style scoped>
.pedido-detalhe {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.pedido-detalhe__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.pedido-detalhe__eyebrow {
  color: var(--color-primary-600);
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.pedido-detalhe__title {
  font-size: 1.2rem;
}

.pedido-detalhe__date {
  color: var(--color-text-tertiary);
  font-size: 0.8rem;
}

.pedido-detalhe__contact {
  display: grid;
  gap: 6px;
  padding: 12px 14px;
  border-radius: 12px;
  background: var(--color-neutral-50);
  font-size: 0.85rem;
}

.pedido-detalhe__contact p {
  display: flex;
  align-items: center;
  gap: 8px;
}

.pedido-detalhe__contact svg {
  width: 15px;
  color: var(--color-text-tertiary);
}

.pedido-detalhe__items {
  display: grid;
  gap: 10px;
}

.pedido-detalhe__items li {
  display: flex;
  gap: 10px;
  font-size: 0.88rem;
}

.pedido-detalhe__qty {
  min-width: 30px;
  color: var(--color-primary-600);
  font-weight: 700;
}

.pedido-detalhe__item-name {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: 0;
  color: var(--color-text-title);
}

.pedido-detalhe__item-name small {
  color: var(--color-text-tertiary);
  font-style: italic;
}

.pedido-detalhe__empty {
  color: var(--color-text-tertiary);
}

.pedido-detalhe__note {
  padding: 10px 12px;
  border-radius: 10px;
  background: var(--color-warning-50);
  color: var(--color-warning-700);
  font-size: 0.82rem;
}

.pedido-detalhe__payments {
  display: grid;
  gap: 6px;
  padding: 12px 14px;
  border-radius: 12px;
  background: var(--color-success-50);
  font-size: 0.82rem;
}

.pedido-detalhe__payments li {
  display: flex;
  justify-content: space-between;
  color: var(--color-success-700);
}

.pedido-detalhe__actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.pedido-detalhe__pay,
.pedido-detalhe__cancel {
  grid-column: 1 / -1;
}
</style>
