<script setup>
import { computed } from 'vue';

import { formatarMoeda } from '@/composables/useFormat';

defineOptions({
  name: 'DashboardVendasChart',
});

const props = defineProps({
  dias: {
    type: Array,
    default: () => [],
  },
});

const DIAS_SEMANA = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

// Altura de cada barra proporcional ao maior valor da semana
const barras = computed(() => {
  const maior = Math.max(...props.dias.map(dia => dia.valor), 1);
  return props.dias.map((dia, index) => ({
    ...dia,
    altura: Math.max((dia.valor / maior) * 100, dia.valor > 0 ? 6 : 2),
    rotulo: DIAS_SEMANA[new Date(`${dia.dia}T12:00:00`).getDay()],
    hoje: index === props.dias.length - 1,
  }));
});

const total = computed(() => props.dias.reduce((soma, dia) => soma + dia.valor, 0));
</script>

<template>
  <section class="dashboard-vendas-chart card">
    <header class="dashboard-vendas-chart__header">
      <div>
        <h2 class="dashboard-vendas-chart__title">Vendas da semana</h2>
        <p class="dashboard-vendas-chart__subtitle">Pedidos finalizados nos últimos 7 dias</p>
      </div>
      <p class="dashboard-vendas-chart__total tabular">{{ formatarMoeda(total) }}</p>
    </header>

    <div
      class="dashboard-vendas-chart__bars"
      role="img"
      :aria-label="`Vendas dos últimos 7 dias, total ${formatarMoeda(total)}`"
    >
      <div
        v-for="barra in barras"
        :key="barra.dia"
        class="dashboard-vendas-chart__column"
      >
        <span class="dashboard-vendas-chart__value tabular">
          {{ barra.valor ? formatarMoeda(barra.valor).replace(',00', '') : '' }}
        </span>
        <span
          class="dashboard-vendas-chart__bar"
          :class="{ 'is-today': barra.hoje }"
          :style="{ height: `${barra.altura}%` }"
          :title="`${barra.rotulo}: ${formatarMoeda(barra.valor)}`"
        />
        <span class="dashboard-vendas-chart__label">{{ barra.rotulo }}</span>
      </div>
    </div>
  </section>
</template>

<style scoped>
.dashboard-vendas-chart {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 20px;
}

.dashboard-vendas-chart__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.dashboard-vendas-chart__title {
  font-size: 1rem;
}

.dashboard-vendas-chart__subtitle {
  color: var(--color-text-tertiary);
  font-size: 0.8rem;
}

.dashboard-vendas-chart__total {
  color: var(--color-text-title);
  font-family: var(--font-title);
  font-size: 1.15rem;
  font-weight: 600;
}

.dashboard-vendas-chart__bars {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 10px;
  height: 200px;
}

.dashboard-vendas-chart__column {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  gap: 6px;
  min-width: 0;
}

.dashboard-vendas-chart__value {
  color: var(--color-text-tertiary);
  font-size: 0.66rem;
  white-space: nowrap;
}

.dashboard-vendas-chart__bar {
  width: 100%;
  max-width: 40px;
  border-radius: 8px 8px 4px 4px;
  background: var(--color-primary-100);
  transition: height 400ms ease;
}

.dashboard-vendas-chart__bar.is-today {
  background: var(--color-primary-500);
}

.dashboard-vendas-chart__label {
  color: var(--color-text-secondary);
  font-size: 0.75rem;
  font-weight: 500;
}
</style>
