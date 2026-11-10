<script setup>
import { onBeforeUnmount, ref } from 'vue';

defineOptions({
  name: 'CommonTable',
});

defineProps({
  columns: {
    type: Array,
    required: true,
  },
});

const scrollRef = ref(null);
const isDragging = ref(false);

let startX = 0;
let startScrollLeft = 0;
let dragMoved = false;

function onPointerDown(event) {
  if (event.pointerType !== 'mouse' || !scrollRef.value) {
    return;
  }

  isDragging.value = true;
  dragMoved = false;
  startX = event.clientX;
  startScrollLeft = scrollRef.value.scrollLeft;

  window.addEventListener('pointermove', onPointerMove);
  window.addEventListener('pointerup', onPointerUp);
}

function onPointerMove(event) {
  if (!isDragging.value || !scrollRef.value) {
    return;
  }

  const delta = event.clientX - startX;
  if (Math.abs(delta) > 3) {
    dragMoved = true;
  }

  scrollRef.value.scrollLeft = startScrollLeft - delta;
}

function onPointerUp() {
  isDragging.value = false;
  window.removeEventListener('pointermove', onPointerMove);
  window.removeEventListener('pointerup', onPointerUp);
}

onBeforeUnmount(() => {
  window.removeEventListener('pointermove', onPointerMove);
  window.removeEventListener('pointerup', onPointerUp);
});

function onClickCapture(event) {
  if (dragMoved) {
    event.preventDefault();
    event.stopPropagation();
  }
}

function onWheel(event) {
  if (!event.shiftKey || !scrollRef.value) {
    return;
  }

  event.preventDefault();
  scrollRef.value.scrollLeft += event.deltaY;
}
</script>

<template>
  <div
    ref="scrollRef"
    class="common-table"
    :class="{ 'is-dragging': isDragging }"
    @pointerdown="onPointerDown"
    @click.capture="onClickCapture"
    @wheel="onWheel"
  >
    <table class="common-table__table">
      <thead class="common-table__head">
        <tr>
          <th
            v-for="column in columns"
            :key="column.key"
            class="common-table__head-cell"
            :class="`align--${column.align ?? 'left'}`"
          >
            {{ column.label }}
          </th>
        </tr>
      </thead>

      <tbody class="common-table__body">
        <slot />
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.common-table {
  width: 100%;
  overflow-x: auto;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.common-table::-webkit-scrollbar {
  display: none;
}

.common-table.is-dragging {
  cursor: grabbing;
  user-select: none;
}

@media (hover: hover) and (pointer: fine) {
  .common-table {
    cursor: grab;
  }
}

.common-table__table {
  width: 100%;
  border-collapse: collapse;
}

.common-table__head-cell {
  padding: 12px 20px;
  border-bottom: 1px solid var(--color-border-default);
  background: var(--color-table-header);
  color: var(--color-text-tertiary);
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  text-align: left;
  white-space: nowrap;
}

.common-table__head-cell.align--right {
  text-align: right;
}

.common-table__head-cell.align--center {
  text-align: center;
}
</style>

<style>
.common-table-row {
  border-bottom: 1px solid var(--color-border-light);
  transition: background-color 160ms ease;
}

.common-table-row:last-child {
  border-bottom: none;
}

.common-table-cell {
  padding: 16px 20px;
  color: var(--color-text-primary);
  font-size: 0.85rem;
  vertical-align: middle;
}

.common-table-cell.align--right {
  text-align: right;
}

.common-table-cell.align--center {
  text-align: center;
}

@media (hover: hover) and (pointer: fine) {
  .common-table-row:hover {
    background-color: var(--color-table-row-hover);
  }
}
</style>
