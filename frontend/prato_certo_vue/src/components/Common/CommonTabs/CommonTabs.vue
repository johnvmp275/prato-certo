<script setup>
defineOptions({
  name: 'CommonTabs',
});

defineProps({
  tabs: {
    type: Array,
    required: true,
  },
  variant: {
    type: String,
    default: 'pill',
    validator: value => ['pill', 'underline'].includes(value),
  },
});

const model = defineModel({ type: [String, Number], default: '' });
</script>

<template>
  <div
    class="common-tabs"
    :class="`common-tabs--${variant}`"
    role="tablist"
  >
    <button
      v-for="tab in tabs"
      :key="tab.value"
      type="button"
      role="tab"
      class="common-tabs__tab"
      :class="{ 'is-active': model === tab.value }"
      :aria-selected="model === tab.value"
      @click="model = tab.value"
    >
      {{ tab.label }}
      <span
        v-if="tab.badge"
        class="common-tabs__badge"
      >
        {{ tab.badge }}
      </span>
    </button>
  </div>
</template>

<style scoped>
.common-tabs {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  scrollbar-width: none;
}

.common-tabs::-webkit-scrollbar {
  display: none;
}

.common-tabs__tab {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  gap: 8px;
  color: var(--color-text-secondary);
  font-size: 0.86rem;
  font-weight: 600;
  white-space: nowrap;
  transition:
    background-color 160ms ease,
    color 160ms ease,
    border-color 160ms ease;
}

.common-tabs__badge {
  min-width: 20px;
  padding: 1px 6px;
  border-radius: 999px;
  background: var(--color-primary-500);
  color: #ffffff;
  font-size: 0.7rem;
  text-align: center;
}

/* PILL (categorias do PDV) */
.common-tabs--pill .common-tabs__tab {
  min-height: 38px;
  padding: 0 16px;
  border: 1px solid var(--color-border-default);
  border-radius: 999px;
  background: var(--color-surface);
}

.common-tabs--pill .common-tabs__tab.is-active {
  border-color: var(--color-primary-500);
  background: var(--color-primary-500);
  color: #ffffff;
}

.common-tabs--pill .common-tabs__tab.is-active .common-tabs__badge {
  background: #ffffff;
  color: var(--color-primary-600);
}

/* UNDERLINE (abas de pedidos) */
.common-tabs--underline {
  gap: 20px;
  border-bottom: 1px solid var(--color-border-default);
}

.common-tabs--underline .common-tabs__tab {
  min-height: 42px;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
}

.common-tabs--underline .common-tabs__tab.is-active {
  border-bottom-color: var(--color-primary-500);
  color: var(--color-primary-600);
}

@media (hover: hover) and (pointer: fine) {
  .common-tabs__tab:not(.is-active):hover {
    color: var(--color-text-title);
  }
  .common-tabs--pill .common-tabs__tab:not(.is-active):hover {
    border-color: var(--color-primary-200);
  }
}
</style>
