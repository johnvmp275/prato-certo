<script setup>
import { computed, ref } from 'vue';

import CommonDialog from '@/components/Common/CommonDialog/CommonDialog.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonSwitch from '@/components/Common/CommonSwitch/CommonSwitch.vue';
import CommonMessageError from '@/components/Common/CommonMessageError/CommonMessageError.vue';

import IconArrowRight from '@/components/Icons/IconChevronRight.vue';

import { pedidosService } from '@/services/pedidos';

defineOptions({
  name: 'MesaAbrirDialog',
});

const props = defineProps({
  mesa: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['aberto', 'close']);

const numPessoas = ref(Math.min(2, props.mesa.capacidade));
const cobrarServico = ref(true);
const salvando = ref(false);
const erro = ref('');

// Opções rápidas de pessoas (1 até a capacidade da mesa)
const opcoes = computed(() => Array.from({ length: props.mesa.capacidade }, (_, index) => index + 1));

async function abrir() {
  erro.value = '';
  salvando.value = true;
  try {
    const pedido = await pedidosService.abrir({
      tipo: 'mesa',
      mesaId: props.mesa.id,
      numPessoas: numPessoas.value,
      cobrarServico: cobrarServico.value,
      itens: [],
    });
    emit('aberto', pedido);
  } catch (error) {
    erro.value = error.mensagem;
  } finally {
    salvando.value = false;
  }
}
</script>

<template>
  <CommonDialog
    :title="`Mesa ${String(mesa.numero).padStart(2, '0')}`"
    :description="`Capacidade para ${mesa.capacidade} pessoas. Quantas pessoas vão sentar?`"
    @close="emit('close')"
  >
    <div class="mesa-abrir-dialog">
      <div
        class="mesa-abrir-dialog__options"
        role="radiogroup"
        aria-label="Número de pessoas"
      >
        <button
          v-for="opcao in opcoes"
          :key="opcao"
          type="button"
          role="radio"
          class="mesa-abrir-dialog__option"
          :class="{ 'is-active': numPessoas === opcao }"
          :aria-checked="numPessoas === opcao"
          @click="numPessoas = opcao"
        >
          {{ opcao }}
        </button>
      </div>

      <CommonSwitch
        v-model="cobrarServico"
        label="Cobrar taxa de serviço"
        description="Aplicada sobre o subtotal no fechamento da conta."
      />

      <CommonMessageError :error="erro" />
    </div>

    <template #footer="{ close }">
      <CommonButton
        variant="secondary"
        @click="close"
      >
        Cancelar
      </CommonButton>
      <CommonButton
        variant="success"
        :loading="salvando"
        @click="abrir"
      >
        <template #icon><IconArrowRight /></template>
        Abrir comanda
      </CommonButton>
    </template>
  </CommonDialog>
</template>

<style scoped>
.mesa-abrir-dialog {
  display: grid;
  gap: 18px;
}

.mesa-abrir-dialog__options {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(52px, 1fr));
  gap: 8px;
}

.mesa-abrir-dialog__option {
  height: 52px;
  border: 1.5px solid var(--color-border-default);
  border-radius: 14px;
  color: var(--color-text-primary);
  font-size: 1rem;
  font-weight: 700;
  transition:
    border-color 160ms ease,
    background-color 160ms ease,
    color 160ms ease;
}

.mesa-abrir-dialog__option.is-active {
  border-color: var(--color-primary-500);
  background: var(--color-primary-50);
  color: var(--color-primary-600);
}

@media (hover: hover) and (pointer: fine) {
  .mesa-abrir-dialog__option:not(.is-active):hover {
    border-color: var(--color-primary-200);
  }
}
</style>
