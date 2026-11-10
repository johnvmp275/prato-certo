<script setup>
import { computed } from 'vue';

import CommonBadge from '@/components/Common/CommonBadge/CommonBadge.vue';

defineOptions({
  name: 'CommonStatusBadge',
});

const props = defineProps({
  status: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    default: '',
  },
});

// Cor de cada status (pedidos e cadastros)
const VARIANTES = {
  pendente: 'warning',
  aberto: 'info',
  em_espera: 'primary',
  finalizado: 'success',
  cancelado: 'neutral',
  ativo: 'success',
  inativo: 'neutral',
};

const NOMES = {
  pendente: 'Aguardando aceite',
  aberto: 'Aberto',
  em_espera: 'Em espera',
  finalizado: 'Finalizado',
  cancelado: 'Cancelado',
  ativo: 'Ativo',
  inativo: 'Inativo',
};

const variant = computed(() => VARIANTES[props.status] ?? 'neutral');
const texto = computed(() => props.label || NOMES[props.status] || props.status);
</script>

<template>
  <CommonBadge
    :label="texto"
    :variant="variant"
    dot
  />
</template>
