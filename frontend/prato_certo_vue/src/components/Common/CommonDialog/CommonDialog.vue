<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

import IconX from '@/components/Icons/IconX.vue';

import { useScrollControl } from '@/composables/useScrollControl';

defineOptions({
  name: 'CommonDialog',
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
  width: {
    type: Number,
    default: 480,
  },
});

const emit = defineEmits(['close']);

const { enableScrollLock, disableScrollLock } = useScrollControl();

const show = ref(false);
const dialogRef = ref(null);

function close() {
  show.value = false;
}

function onKeydown(event) {
  if (event.key === 'Escape') close();
}

onMounted(() => {
  show.value = true;
  enableScrollLock();
  window.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
  disableScrollLock();
  window.removeEventListener('keydown', onKeydown);
});

defineExpose({
  close,
});
</script>

<template>
  <Teleport to="body">
    <Transition
      name="common-dialog"
      appear
      @after-leave="emit('close')"
    >
      <div
        v-if="show"
        class="common-dialog"
        @click.self="close"
      >
        <div
          ref="dialogRef"
          class="common-dialog__panel"
          role="dialog"
          aria-modal="true"
          :aria-label="props.title"
          :style="{ maxWidth: `${props.width}px` }"
        >
          <header class="common-dialog__header">
            <div>
              <h2 class="common-dialog__title">{{ title }}</h2>
              <p
                v-if="description"
                class="common-dialog__description"
              >
                {{ description }}
              </p>
            </div>
            <button
              type="button"
              class="common-dialog__close"
              aria-label="Fechar"
              @click="close"
            >
              <IconX aria-hidden="true" />
            </button>
          </header>

          <div class="common-dialog__body">
            <slot :close="close" />
          </div>

          <footer
            v-if="$slots.footer"
            class="common-dialog__footer"
          >
            <slot
              name="footer"
              :close="close"
            />
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.common-dialog {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding: 0;
  background: var(--color-overlay);
}

.common-dialog__panel {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-height: 92dvh;
  border-radius: 20px 20px 0 0;
  background: var(--color-surface);
  box-shadow: var(--shadow-lg);
  overflow: hidden;
}

.common-dialog__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 20px 20px 12px;
}

.common-dialog__title {
  font-size: 1.1rem;
}

.common-dialog__description {
  margin-top: 4px;
  color: var(--color-text-secondary);
  font-size: 0.85rem;
}

.common-dialog__close {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 10px;
  color: var(--color-text-secondary);
}

.common-dialog__close svg {
  width: 18px;
}

.common-dialog__body {
  padding: 8px 20px 20px;
  overflow-y: auto;
}

.common-dialog__footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 14px 20px;
  border-top: 1px solid var(--color-border-light);
  background: var(--color-surface-muted);
}

.common-dialog-enter-active,
.common-dialog-leave-active {
  transition: opacity 200ms ease;
}
.common-dialog-enter-active .common-dialog__panel,
.common-dialog-leave-active .common-dialog__panel {
  transition: transform 220ms ease;
}
.common-dialog-enter-from,
.common-dialog-leave-to {
  opacity: 0;
}
.common-dialog-enter-from .common-dialog__panel,
.common-dialog-leave-to .common-dialog__panel {
  transform: translateY(24px);
}

@media (min-width: 640px) {
  .common-dialog {
    align-items: center;
    padding: 24px;
  }
  .common-dialog__panel {
    border-radius: 20px;
  }
}

@media (hover: hover) and (pointer: fine) {
  .common-dialog__close:hover {
    background: var(--color-neutral-100);
  }
}
</style>
