<script setup>
import { computed } from 'vue';

import IconChevronLeft from '@/components/Icons/IconChevronLeft.vue';
import IconChevronRight from '@/components/Icons/IconChevronRight.vue';

defineOptions({
  name: 'CommonPagination',
});

const props = defineProps({
  paginacao: {
    type: Object,
    required: true,
  },
  itemLabel: {
    type: String,
    default: 'registros',
  },
});

const emit = defineEmits(['change']);

const currentPage = computed(() => props.paginacao.paginaAtual ?? 1);
const totalPages = computed(() => props.paginacao.quantidadePaginas ?? 1);
const totalItems = computed(() => props.paginacao.total ?? 0);
const pageSize = computed(() => props.paginacao.itensPorPagina ?? 0);

const rangeStart = computed(() => (currentPage.value - 1) * pageSize.value + 1);
const rangeEnd = computed(() => Math.min(currentPage.value * pageSize.value, totalItems.value));

const pages = computed(() => {
  const total = totalPages.value;
  const current = currentPage.value;
  const items = [];

  const addPage = page => items.push({ type: 'page', page });
  const addEllipsis = key => items.push({ type: 'ellipsis', key });

  addPage(1);

  if (current > 3) {
    addEllipsis('start');
  }

  for (let page = Math.max(2, current - 1); page <= Math.min(total - 1, current + 1); page += 1) {
    addPage(page);
  }

  if (current < total - 2) {
    addEllipsis('end');
  }

  if (total > 1) {
    addPage(total);
  }

  return items;
});

function goToPage(page) {
  if (page < 1 || page > totalPages.value || page === currentPage.value) {
    return;
  }
  emit('change', page);
}
</script>

<template>
  <div
    v-if="totalItems"
    class="common-pagination"
  >
    <p class="common-pagination__summary">
      Mostrando {{ rangeStart }} a {{ rangeEnd }} de {{ totalItems }} {{ itemLabel }}
    </p>

    <div
      v-if="totalPages > 1"
      class="common-pagination__controls"
    >
      <button
        type="button"
        class="common-pagination__nav"
        aria-label="Página anterior"
        :disabled="currentPage === 1"
        @click="goToPage(currentPage - 1)"
      >
        <IconChevronLeft aria-hidden="true" />
      </button>

      <template
        v-for="item in pages"
        :key="item.type === 'page' ? `page-${item.page}` : `ellipsis-${item.key}`"
      >
        <span
          v-if="item.type === 'ellipsis'"
          class="common-pagination__ellipsis"
        >
          ...
        </span>
        <button
          v-else
          type="button"
          class="common-pagination__page"
          :class="{ 'is-active': item.page === currentPage }"
          :aria-current="item.page === currentPage ? 'page' : undefined"
          @click="goToPage(item.page)"
        >
          {{ item.page }}
        </button>
      </template>

      <button
        type="button"
        class="common-pagination__nav"
        aria-label="Próxima página"
        :disabled="currentPage === totalPages"
        @click="goToPage(currentPage + 1)"
      >
        <IconChevronRight aria-hidden="true" />
      </button>
    </div>
  </div>
</template>

<style scoped>
.common-pagination {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  padding: 14px 20px;
  border-top: 1px solid var(--color-border-light);
}

.common-pagination__summary {
  color: var(--color-text-tertiary);
  font-size: 0.82rem;
  text-align: center;
}

.common-pagination__controls {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
  justify-content: center;
}

.common-pagination__nav,
.common-pagination__page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 34px;
  height: 34px;
  padding: 0 4px;
  border: 1px solid var(--color-border-default);
  border-radius: 10px;
  background: var(--color-surface);
  color: var(--color-text-primary);
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  transition:
    background-color 160ms ease,
    border-color 160ms ease,
    color 160ms ease;
}

.common-pagination__nav svg {
  width: 16px;
  height: 16px;
}

.common-pagination__nav:disabled {
  color: var(--color-text-disabled);
  cursor: not-allowed;
}

.common-pagination__page.is-active {
  border-color: var(--color-primary-500);
  background: var(--color-primary-500);
  color: var(--color-text-inverse);
}

.common-pagination__ellipsis {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 24px;
  color: var(--color-text-tertiary);
  font-size: 0.82rem;
}

@media (hover: hover) and (pointer: fine) {
  .common-pagination__nav:not(:disabled):hover,
  .common-pagination__page:not(.is-active):hover {
    border-color: var(--color-border-dark);
    background: var(--color-neutral-50);
  }
}

@media (min-width: 768px) {
  .common-pagination {
    flex-direction: row;
    justify-content: space-between;
  }
}
</style>
