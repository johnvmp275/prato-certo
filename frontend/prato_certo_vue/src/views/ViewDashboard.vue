<script setup>
import { computed, onMounted, ref } from 'vue';

import { useRouter } from 'vue-router';

import CommonPageHeader from '@/components/Common/CommonPageHeader/CommonPageHeader.vue';
import CommonStatCard from '@/components/Common/CommonStatCard/CommonStatCard.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonLoader from '@/components/Common/CommonLoader/CommonLoader.vue';
import CommonEmptyState from '@/components/Common/CommonEmptyState/CommonEmptyState.vue';

import DashboardVendasChart from '@/components/Dashboard/DashboardVendasChart/DashboardVendasChart.vue';
import PedidoListItem from '@/components/Pedidos/PedidoListItem/PedidoListItem.vue';

import IconDollar from '@/components/Icons/IconDollar.vue';
import IconReceipt from '@/components/Icons/IconReceipt.vue';
import IconTrendingUp from '@/components/Icons/IconTrendingUp.vue';
import IconTable from '@/components/Icons/IconTable.vue';
import IconGrid from '@/components/Icons/IconGrid.vue';
import IconShoppingBag from '@/components/Icons/IconShoppingBag.vue';

import { dashboardService } from '@/services/painel';
import { useAuth } from '@/stores/useAuth';
import { useToast } from '@/stores/useToast';
import { formatarMoeda } from '@/composables/useFormat';

defineOptions({
  name: 'ViewDashboard',
});

const auth = useAuth();
const toast = useToast();
const router = useRouter();

const resumo = ref(null);
const carregando = ref(true);

const saudacao = computed(() => {
  const hora = new Date().getHours();
  const periodo = hora < 12 ? 'Bom dia' : hora < 18 ? 'Boa tarde' : 'Boa noite';
  return `${periodo}, ${auth.usuario?.nome?.split(' ')[0] ?? ''}!`;
});

const hoje = new Date().toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long' });

const stats = computed(() => {
  if (!resumo.value) return [];
  return [
    { icon: IconDollar, label: 'Vendas hoje', value: formatarMoeda(resumo.value.vendasHoje), tone: 'primary' },
    { icon: IconReceipt, label: 'Pedidos finalizados', value: resumo.value.pedidosHoje, tone: 'success' },
    { icon: IconTrendingUp, label: 'Ticket médio', value: formatarMoeda(resumo.value.ticketMedio), tone: 'info' },
    {
      icon: IconTable,
      label: 'Mesas ocupadas',
      value: `${resumo.value.mesas.ocupadas}/${resumo.value.mesas.total}`,
      hint: `${resumo.value.pedidosAbertos} pedidos em andamento`,
      tone: 'warning',
    },
  ];
});

async function carregar() {
  carregando.value = true;
  try {
    resumo.value = await dashboardService.resumo();
  } catch (error) {
    toast.error(error.mensagem);
  } finally {
    carregando.value = false;
  }
}

onMounted(carregar);
</script>

<template>
  <main class="view-dashboard view-page row">
    <CommonPageHeader
      :title="saudacao"
      :subtitle="`Resumo de ${hoje}`"
    >
      <template #actions>
        <CommonButton
          variant="secondary"
          @click="router.push('/painel/mesas')"
        >
          <template #icon><IconTable /></template>
          Mesas
        </CommonButton>
        <CommonButton @click="router.push('/painel/pdv')">
          <template #icon><IconGrid /></template>
          Abrir PDV
        </CommonButton>
      </template>
    </CommonPageHeader>

    <CommonLoader v-if="carregando && !resumo" />

    <template v-else-if="resumo">
      <div class="view-dashboard__stats">
        <CommonStatCard
          v-for="stat in stats"
          :key="stat.label"
          v-bind="stat"
        />
      </div>

      <RouterLink
        v-if="resumo.pedidosOnline"
        to="/painel/pedidos?aba=online"
        class="view-dashboard__alert"
      >
        <IconShoppingBag aria-hidden="true" />
        <span>
          <strong>{{ resumo.pedidosOnline }} pedido(s) online</strong> aguardando aceite.
        </span>
        <span class="view-dashboard__alert-action">Ver pedidos →</span>
      </RouterLink>

      <div class="view-dashboard__grid">
        <DashboardVendasChart :dias="resumo.ultimosDias" />

        <section class="view-dashboard__panel card">
          <h2 class="view-dashboard__panel-title">Mais vendidos hoje</h2>
          <ol
            v-if="resumo.maisVendidos.length"
            class="view-dashboard__ranking"
          >
            <li
              v-for="(item, index) in resumo.maisVendidos"
              :key="item.nome"
            >
              <span class="view-dashboard__rank">{{ index + 1 }}</span>
              <span class="view-dashboard__rank-name">{{ item.nome }}</span>
              <span class="view-dashboard__rank-qty tabular">{{ item.quantidade }}x</span>
            </li>
          </ol>
          <CommonEmptyState
            v-else
            title="Nenhuma venda hoje"
            description="Os produtos mais vendidos aparecem aqui."
          />
        </section>
      </div>

      <section class="view-dashboard__panel card">
        <div class="view-dashboard__panel-header">
          <h2 class="view-dashboard__panel-title">Últimos pedidos</h2>
          <RouterLink
            to="/painel/pedidos"
            class="view-dashboard__link"
          >
            Ver todos
          </RouterLink>
        </div>
        <div
          v-if="resumo.ultimosPedidos.length"
          class="view-dashboard__orders"
        >
          <PedidoListItem
            v-for="pedido in resumo.ultimosPedidos"
            :key="pedido.id"
            :pedido="pedido"
            @select="router.push({ path: '/painel/pedidos', query: { id: pedido.id } })"
          />
        </div>
        <CommonEmptyState
          v-else
          :icon="IconReceipt"
          title="Nenhum pedido ainda"
          description="Abra uma comanda no PDV para começar."
        />
      </section>
    </template>
  </main>
</template>

<style scoped>
.view-dashboard__stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
}

.view-dashboard__alert {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  border: 1px solid var(--color-warning-100);
  border-radius: 14px;
  background: var(--color-warning-50);
  color: var(--color-warning-700);
  font-size: 0.9rem;
}

.view-dashboard__alert svg {
  width: 20px;
}

.view-dashboard__alert-action {
  margin-left: auto;
  font-weight: 600;
}

.view-dashboard__grid {
  display: grid;
  gap: 16px;
}

.view-dashboard__panel {
  padding: 20px;
}

.view-dashboard__panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.view-dashboard__panel-title {
  margin-bottom: 12px;
  font-size: 1rem;
}

.view-dashboard__panel-header .view-dashboard__panel-title {
  margin-bottom: 0;
}

.view-dashboard__link {
  color: var(--color-primary-600);
  font-size: 0.85rem;
  font-weight: 600;
}

.view-dashboard__ranking {
  display: grid;
  gap: 12px;
}

.view-dashboard__ranking li {
  display: flex;
  align-items: center;
  gap: 12px;
}

.view-dashboard__rank {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: var(--color-primary-50);
  color: var(--color-primary-600);
  font-size: 0.8rem;
  font-weight: 700;
}

.view-dashboard__rank-name {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.view-dashboard__rank-qty {
  color: var(--color-text-secondary);
  font-weight: 600;
}

.view-dashboard__orders {
  display: grid;
  gap: 4px;
  margin: 0 -8px;
}

@media (min-width: 768px) {
  .view-dashboard__orders {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 1100px) {
  .view-dashboard__grid {
    grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
  }
  .view-dashboard__orders {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}
</style>
