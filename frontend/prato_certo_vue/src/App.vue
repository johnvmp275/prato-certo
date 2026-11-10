<script setup>
import { computed } from 'vue';

import { useRoute } from 'vue-router';

import DefaultLayout from './layouts/DefaultLayout.vue';

import CommonToast from '@/components/Common/CommonToast/CommonToast.vue';

import { useScrollControl } from '@/composables/useScrollControl.js';
import { useDevice } from '@/composables/useDevice.js';
import { useTema } from '@/composables/useTema.js';

const route = useRoute();
const layout = computed(() => {
  // Antes da primeira navegação a rota ainda não foi resolvida (START_LOCATION, sem meta):
  // não renderiza nada para o layout do painel não montar e chamar a API sem login
  if (!route.matched.length) {
    return null;
  }

  if (route.meta.layout) {
    return route.meta.layout;
  }

  return DefaultLayout;
});

const { updateScrollbarWidth } = useScrollControl();
const { updateDevice } = useDevice();

// Aplica o tema salvo (claro/escuro) e acompanha o tema do sistema em todas as telas
useTema();

const DESKTOP_BREAKPOINT_QUERY = window.matchMedia('(min-width: 1024px)');
function initResizeForDevice() {
  updateDevice();
  DESKTOP_BREAKPOINT_QUERY.addEventListener('change', updateDevice);
  window.addEventListener('resize', updateDevice);
}
function initResizeForScrollbarWidth() {
  updateScrollbarWidth();
  DESKTOP_BREAKPOINT_QUERY.addEventListener('change', updateScrollbarWidth);
  window.addEventListener('resize', updateScrollbarWidth);
}

initResizeForScrollbarWidth();
initResizeForDevice();
</script>

<template>
  <component
    :is="layout"
    v-if="layout"
  >
    <RouterView />
  </component>

  <CommonToast />
</template>
