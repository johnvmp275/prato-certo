<script setup>
import IconMoon from '@/components/Icons/IconMoon.vue';
import IconSun from '@/components/Icons/IconSun.vue';

import { useTema } from '@/composables/useTema';

defineOptions({
  name: 'CommonThemeToggle',
});

defineProps({
  // 'default': botão neutro (painel e login) · 'glass': translúcido, para fundo laranja (topo do cardápio)
  variant: {
    type: String,
    default: 'default',
    validator: value => ['default', 'glass'].includes(value),
  },
});

const { escuro, alternar } = useTema();
</script>

<template>
  <button
    type="button"
    class="common-theme-toggle"
    :class="`common-theme-toggle--${variant}`"
    :aria-label="escuro ? 'Usar tema claro' : 'Usar tema escuro'"
    :title="escuro ? 'Tema claro' : 'Tema escuro'"
    @click="alternar"
  >
    <IconSun
      v-if="escuro"
      aria-hidden="true"
    />
    <IconMoon
      v-else
      aria-hidden="true"
    />
  </button>
</template>

<style scoped>
.common-theme-toggle {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  color: var(--color-text-tertiary);
  cursor: pointer;
  transition:
    background-color 160ms ease,
    color 160ms ease;
}

.common-theme-toggle svg {
  width: 18px;
}

.common-theme-toggle--glass {
  width: 32px;
  height: 32px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.18);
  color: #ffffff;
}

.common-theme-toggle--glass svg {
  width: 16px;
}

@media (hover: hover) and (pointer: fine) {
  .common-theme-toggle--default:hover {
    background: var(--color-neutral-100);
    color: var(--color-text-primary);
  }
  .common-theme-toggle--glass:hover {
    background: rgba(255, 255, 255, 0.28);
  }
}
</style>
