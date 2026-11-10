<script setup>
import { ref } from 'vue';

import CommonDialog from '@/components/Common/CommonDialog/CommonDialog.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';

defineOptions({
  name: 'CommonConfirm',
});

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
  confirmLabel: {
    type: String,
    default: 'Confirmar',
  },
  // Função assíncrona executada ao confirmar
  action: {
    type: Function,
    required: true,
  },
});

const emit = defineEmits(['close']);

const carregando = ref(false);

async function confirmar(close) {
  carregando.value = true;
  try {
    await props.action();
    close();
  } finally {
    carregando.value = false;
  }
}
</script>

<template>
  <CommonDialog
    :title="title"
    :description="description"
    :width="420"
    @close="emit('close')"
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
        :loading="carregando"
        @click="confirmar(close)"
      >
        {{ confirmLabel }}
      </CommonButton>
    </template>
  </CommonDialog>
</template>
