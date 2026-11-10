<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

import IconX from '@/components/Icons/IconX.vue';

import { useScrollControl } from '@/composables/useScrollControl';
import { useDevice } from '@/composables/useDevice';

const { enableScrollLock, disableScrollLock } = useScrollControl();
const { isDesktop } = useDevice();

defineOptions({
  name: 'CommonSheet',
  inheritAttrs: false,
});

defineExpose({
  close,
});

const emit = defineEmits([
  'close',
  'before-enter',
  'after-enter',
  'enter-cancelled',
  'before-leave',
  'after-leave',
  'leave-cancelled',
]);

const show = ref(false);

const props = defineProps({
  size: {
    type: [String, Number],
    default: 'initial',
  },
  // 'auto': gaveta lateral (direita) no desktop e de baixo para cima no celular/tablet
  position: {
    type: String,
    default: 'top',
    validator: value => ['top', 'right', 'left', 'bottom', 'auto'].includes(value),
  },
  // Quando informado, a gaveta ganha cabeçalho, corpo com rolagem e rodapé (formulários)
  title: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
});

const posicaoAtual = computed(() => {
  if (props.position !== 'auto') return props.position;
  return isDesktop.value ? 'right' : 'bottom';
});

const sizeString = computed(() => {
  // Tamanho padrão do modo automático: 480px na lateral e 92% da altura no celular
  if (props.position === 'auto' && props.size === 'initial') {
    return posicaoAtual.value === 'right' ? '480px' : '92dvh';
  }

  if (typeof props.size === 'string') {
    return `${props.size}`;
  }
  return `${props.size}px`;
});

// Garante uma única trava por gaveta e que ela seja liberada mesmo se a gaveta sair da tela sem animação
let travouRolagem = false;
function travarRolagem() {
  if (travouRolagem) return;
  travouRolagem = true;
  enableScrollLock();
}
function liberarRolagem() {
  if (!travouRolagem) return;
  travouRolagem = false;
  disableScrollLock();
}

function onBeforeEnter(el) {
  travarRolagem();
  window.addEventListener('keydown', closeOnEscapeKey);
  emit('before-enter', el);
}
function onAfterEnter(el) {
  emit('after-enter', el);
}
function onEnterCancelled(el) {
  emit('enter-cancelled', el);
}
function onBeforeLeave(el) {
  emit('before-leave', el);
}
function onAfterLeave(el) {
  liberarRolagem();
  window.removeEventListener('keydown', closeOnEscapeKey);
  emit('after-leave', el);
  emit('close');
}
function isEscapeKeyPressed(event) {
  return event instanceof KeyboardEvent && event.key === 'Escape';
}
function closeOnEscapeKey(event) {
  if (isEscapeKeyPressed(event)) {
    close();
  }
}
function close() {
  show.value = false;
}
onMounted(() => {
  show.value = true;
});

// Removida sem animação de saída (ex.: virou desktop com a gaveta aberta): libera a rolagem e o Esc
onBeforeUnmount(() => {
  liberarRolagem();
  window.removeEventListener('keydown', closeOnEscapeKey);
});
</script>

<template>
  <Teleport to="body">
    <div
      class="common-sheet"
      v-bind="$attrs"
    >
      <Transition name="common-sheet-fade">
        <div
          v-if="show"
          class="common-sheet__background"
          @click.self="close"
        />
      </Transition>

      <Transition
        :name="`common-sheet-${posicaoAtual}`"
        @before-enter="onBeforeEnter"
        @after-enter="onAfterEnter"
        @enter-cancelled="onEnterCancelled"
        @before-leave="onBeforeLeave"
        @after-leave="onAfterLeave"
      >
        <div
          v-if="show"
          class="common-sheet__content"
          :class="`position--${posicaoAtual}`"
        >
          <!-- MODO PAINEL: CABEÇALHO + CORPO + RODAPÉ -->
          <section
            v-if="title"
            class="common-sheet__panel"
            role="dialog"
            aria-modal="true"
            :aria-label="title"
          >
            <span
              v-if="posicaoAtual === 'bottom'"
              class="common-sheet__handle"
              aria-hidden="true"
            />

            <header class="common-sheet__header">
              <div>
                <h2 class="common-sheet__title">{{ title }}</h2>
                <p
                  v-if="description"
                  class="common-sheet__description"
                >
                  {{ description }}
                </p>
              </div>
              <button
                type="button"
                class="common-sheet__close"
                aria-label="Fechar"
                @click="close"
              >
                <IconX aria-hidden="true" />
              </button>
            </header>

            <div class="common-sheet__body">
              <slot
                name="content"
                :close="close"
              />
            </div>

            <footer
              v-if="$slots.footer"
              class="common-sheet__footer"
            >
              <slot
                name="footer"
                :close="close"
              />
            </footer>
          </section>

          <!-- MODO LIVRE: O CONTEÚDO OCUPA A GAVETA INTEIRA -->
          <slot
            v-else
            name="content"
            :close="close"
          />
        </div>
      </Transition>
    </div>
  </Teleport>
</template>

<style scoped>
.common-sheet {
  --common_sheet_transition: 250ms;

  position: relative;
  z-index: 999;
}
.common-sheet__background {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100vh;
  height: 100dvh;
  background-color: var(--color-overlay);
  z-index: 1;
}
.common-sheet__content {
  position: fixed;
  z-index: 2;
  width: 100%;
  height: 100%;
}

/* COMMON SHEET: RIGHT */
.common-sheet__content.position--right {
  top: 0;
  right: 0;
  max-width: v-bind('sizeString');
}
.common-sheet-right-enter-active,
.common-sheet-right-leave-active {
  transition: transform var(--common_sheet_transition) ease;
}
.common-sheet-right-enter-from,
.common-sheet-right-leave-to {
  transform: translateX(100%);
}

/* COMMON SHEET: LEFT */
.common-sheet__content.position--left {
  top: 0;
  left: 0;
  max-width: v-bind('sizeString');
}
.common-sheet-left-enter-active,
.common-sheet-left-leave-active {
  transition: transform var(--common_sheet_transition) ease;
}
.common-sheet-left-enter-from,
.common-sheet-left-leave-to {
  transform: translateX(-100%);
}

/* COMMON SHEET: TOP */
.common-sheet__content.position--top {
  top: 0;
  left: 0;
  max-height: v-bind('sizeString');
}
.common-sheet-top-enter-active,
.common-sheet-top-leave-active {
  transition: transform var(--common_sheet_transition) ease;
}
.common-sheet-top-enter-from,
.common-sheet-top-leave-to {
  transform: translateY(-100%);
}

/* COMMON SHEET: BOTTOM */
.common-sheet__content.position--bottom {
  bottom: 0;
  left: 0;
  max-height: v-bind('sizeString');
}
.common-sheet-bottom-enter-active,
.common-sheet-bottom-leave-active {
  transition: transform var(--common_sheet_transition) ease;
}
.common-sheet-bottom-enter-from,
.common-sheet-bottom-leave-to {
  transform: translateY(100%);
}

.common-sheet-fade-enter-active,
.common-sheet-fade-leave-active {
  transition: opacity var(--common_sheet_transition) ease;
}
.common-sheet-fade-enter-from,
.common-sheet-fade-leave-to {
  opacity: 0;
}

/* MODO PAINEL (FORMULÁRIOS) */
.common-sheet__panel {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--color-surface);
  box-shadow: var(--shadow-lg);
}

.position--bottom .common-sheet__panel {
  border-radius: 20px 20px 0 0;
}

.common-sheet__handle {
  align-self: center;
  width: 44px;
  height: 5px;
  margin-top: 10px;
  border-radius: 999px;
  background: var(--color-neutral-300);
}

.common-sheet__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 20px 22px 16px;
  border-bottom: 1px solid var(--color-border-light);
}

.common-sheet__title {
  font-size: 1.15rem;
}

.common-sheet__description {
  margin-top: 4px;
  color: var(--color-text-secondary);
  font-size: 0.85rem;
}

.common-sheet__close {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: var(--color-neutral-100);
  color: var(--color-text-secondary);
}

.common-sheet__close svg {
  width: 18px;
}

.common-sheet__body {
  flex: 1;
  overflow-y: auto;
  padding: 20px 22px;
}

.common-sheet__footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 14px 22px;
  border-top: 1px solid var(--color-border-light);
  background: var(--color-surface-muted);
}

.common-sheet__footer :deep(.common-button) {
  flex: 1;
}

@media (min-width: 1024px) {
  .common-sheet__footer :deep(.common-button) {
    flex: none;
  }
}

@media (hover: hover) and (pointer: fine) {
  .common-sheet__close:hover {
    background: var(--color-neutral-200);
    color: var(--color-text-title);
  }
}
</style>
