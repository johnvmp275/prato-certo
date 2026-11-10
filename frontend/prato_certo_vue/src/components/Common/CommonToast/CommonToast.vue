<script setup>
import IconCheckCircle from '@/components/Icons/IconCheckCircle.vue';
import IconAlert from '@/components/Icons/IconAlert.vue';
import IconX from '@/components/Icons/IconX.vue';

import { useToast } from '@/stores/useToast';

defineOptions({
  name: 'CommonToast',
});

const toast = useToast();
</script>

<template>
  <Teleport to="body">
    <div
      class="common-toast"
      aria-live="polite"
    >
      <TransitionGroup name="common-toast">
        <div
          v-for="mensagem in toast.mensagens"
          :key="mensagem.id"
          class="common-toast__item"
          :class="`common-toast__item--${mensagem.tipo}`"
          role="status"
        >
          <span
            class="common-toast__icon"
            aria-hidden="true"
          >
            <IconCheckCircle v-if="mensagem.tipo === 'success'" />
            <IconAlert v-else />
          </span>
          <p class="common-toast__text">{{ mensagem.texto }}</p>
          <button
            type="button"
            class="common-toast__close"
            aria-label="Fechar aviso"
            @click="toast.fechar(mensagem.id)"
          >
            <IconX aria-hidden="true" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.common-toast {
  position: fixed;
  right: 16px;
  bottom: 16px;
  left: 16px;
  z-index: 2000;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  pointer-events: none;
}

.common-toast__item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  width: 100%;
  max-width: 400px;
  padding: 12px 12px 12px 14px;
  border-radius: 14px;
  background: var(--color-neutral-900);
  color: #ffffff;
  box-shadow: var(--shadow-lg);
  pointer-events: auto;
}

.common-toast__icon {
  flex-shrink: 0;
  width: 20px;
  margin-top: 1px;
}

.common-toast__item--success .common-toast__icon {
  color: var(--color-success-300);
}

.common-toast__item--error .common-toast__icon {
  color: #fca5a5;
}

.common-toast__item--info .common-toast__icon {
  color: var(--color-primary-300);
}

.common-toast__text {
  flex: 1;
  font-size: 0.86rem;
  line-height: 1.45;
}

.common-toast__close {
  flex-shrink: 0;
  width: 22px;
  color: rgba(255, 255, 255, 0.6);
}

.common-toast-enter-active,
.common-toast-leave-active {
  transition:
    opacity 200ms ease,
    transform 200ms ease;
}
.common-toast-enter-from,
.common-toast-leave-to {
  opacity: 0;
  transform: translateY(12px);
}

@media (min-width: 768px) {
  .common-toast {
    left: auto;
    align-items: flex-end;
  }
}
</style>
