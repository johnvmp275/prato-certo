<script setup>
import { computed, onMounted, ref } from 'vue';

import { useScrollControl } from '@/composables/useScrollControl';

const { enableScrollLock, disableScrollLock } = useScrollControl();

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
  position: {
    type: String,
    default: 'top',
    validator: value => ['top', 'right', 'left', 'bottom'].includes(value),
  },
});

const sizeString = computed(() => {
  if (typeof props.size === 'string') {
    return `${props.size}`;
  }
  return `${props.size}px`;
});

function onBeforeEnter(el) {
  enableScrollLock();
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
  disableScrollLock();
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
        :name="`common-sheet-${props.position}`"
        @before-enter="onBeforeEnter"
        @after-enter="onAfterEnter"
        @enter-cancelled="onEnterCancelled"
        @before-leave="onBeforeLeave"
        @after-leave="onAfterLeave"
      >
        <div
          v-if="show"
          class="common-sheet__content"
          :class="`position--${props.position}`"
        >
          <slot
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
</style>
