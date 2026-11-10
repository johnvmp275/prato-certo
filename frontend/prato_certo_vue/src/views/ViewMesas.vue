<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

import { useRouter } from 'vue-router';

import CommonPageHeader from '@/components/Common/CommonPageHeader/CommonPageHeader.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonTabs from '@/components/Common/CommonTabs/CommonTabs.vue';
import CommonLoader from '@/components/Common/CommonLoader/CommonLoader.vue';
import CommonEmptyState from '@/components/Common/CommonEmptyState/CommonEmptyState.vue';

import MesaCard from '@/components/Mesas/MesaCard/MesaCard.vue';
import MesaAbrirDialog from '@/components/Mesas/MesaAbrirDialog/MesaAbrirDialog.vue';
import MesaForm from '@/components/Forms/MesaForm/MesaForm.vue';

import IconPlus from '@/components/Icons/IconPlus.vue';
import IconRefresh from '@/components/Icons/IconRefresh.vue';
import IconTable from '@/components/Icons/IconTable.vue';

import { mesasService } from '@/services/crud';
import { useAuth } from '@/stores/useAuth';
import { useToast } from '@/stores/useToast';
import { useAbaSalva } from '@/composables/useAbaSalva';

defineOptions({
  name: 'ViewMesas',
});

const auth = useAuth();
const toast = useToast();
const router = useRouter();

const mesas = ref([]);
const resumo = ref({ livres: 0, ocupadas: 0, emEspera: 0 });
// Aba salva no localStorage: continua a mesma depois do F5
const { aba: filtro } = useAbaSalva('painel:mesas', 'todas');
const carregando = ref(true);

const mesaAbrir = ref(null);
const mesaEditar = ref(null);
const mostrarForm = ref(false);

const podeGerenciar = computed(() => auth.pode('mesas.gerenciar'));

const abas = computed(() => [
  { value: 'todas', label: 'Todas', badge: mesas.value.length },
  { value: 'livre', label: 'Livres', badge: resumo.value.livres },
  { value: 'ocupada', label: 'Ocupadas', badge: resumo.value.ocupadas },
  { value: 'em_espera', label: 'Em espera', badge: resumo.value.emEspera },
]);

const mesasFiltradas = computed(() =>
  filtro.value === 'todas' ? mesas.value : mesas.value.filter(mesa => mesa.estado === filtro.value),
);

async function carregar() {
  try {
    const resposta = await mesasService.listar(podeGerenciar.value ? { todas: 1 } : {});
    mesas.value = resposta.mesas;
    resumo.value = resposta.resumo;
  } catch (error) {
    toast.error(error.mensagem);
  } finally {
    carregando.value = false;
  }
}

// Mesa livre abre a comanda; ocupada/em espera vai direto para o pedido no PDV
function selecionar(mesa) {
  if (mesa.pedido) {
    router.push(`/painel/pdv/${mesa.pedido.id}`);
  } else {
    mesaAbrir.value = mesa;
  }
}

function comandaAberta(pedido) {
  toast.success(`Comanda #${pedido.codigo} aberta na ${pedido.origem}.`);
  router.push(`/painel/pdv/${pedido.id}`);
}

function editar(mesa) {
  mesaEditar.value = mesa;
  mostrarForm.value = true;
}

function novaMesa() {
  mesaEditar.value = null;
  mostrarForm.value = true;
}

function mesaSalva() {
  toast.success('Mesa salva com sucesso.');
  carregar();
}

// Atualiza o mapa automaticamente a cada 20 segundos
let intervalo = null;
onMounted(() => {
  carregar();
  intervalo = setInterval(carregar, 20000);
});
onBeforeUnmount(() => clearInterval(intervalo));
</script>

<template>
  <main class="view-mesas view-page row">
    <CommonPageHeader
      title="Mesas"
      subtitle="Selecione uma mesa livre para abrir a comanda."
    >
      <template #actions>
        <CommonButton
          variant="secondary"
          @click="carregar"
        >
          <template #icon><IconRefresh /></template>
          Atualizar
        </CommonButton>
        <CommonButton
          v-if="podeGerenciar"
          @click="novaMesa"
        >
          <template #icon><IconPlus /></template>
          Nova mesa
        </CommonButton>
      </template>
    </CommonPageHeader>

    <div class="view-mesas__toolbar">
      <CommonTabs
        v-model="filtro"
        :tabs="abas"
      />
      <ul class="view-mesas__legend">
        <li><span class="is-livre" /> Livre</li>
        <li><span class="is-ocupada" /> Ocupada</li>
        <li><span class="is-espera" /> Em espera</li>
      </ul>
    </div>

    <CommonLoader v-if="carregando" />

    <div
      v-else-if="mesasFiltradas.length"
      class="view-mesas__grid"
    >
      <MesaCard
        v-for="mesa in mesasFiltradas"
        :key="mesa.id"
        :mesa="mesa"
        :editavel="podeGerenciar"
        @select="selecionar"
        @edit="editar"
      />
    </div>

    <CommonEmptyState
      v-else
      :icon="IconTable"
      title="Nenhuma mesa nesta situação"
    />

    <MesaAbrirDialog
      v-if="mesaAbrir"
      :mesa="mesaAbrir"
      @aberto="comandaAberta"
      @close="mesaAbrir = null"
    />

    <MesaForm
      v-if="mostrarForm"
      :mesa="mesaEditar"
      @saved="mesaSalva"
      @close="mostrarForm = false"
    />
  </main>
</template>

<style scoped>
.view-mesas__toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
}

.view-mesas__legend {
  display: flex;
  gap: 16px;
  color: var(--color-text-secondary);
  font-size: 0.82rem;
}

.view-mesas__legend li {
  display: flex;
  align-items: center;
  gap: 6px;
}

.view-mesas__legend span {
  width: 12px;
  height: 12px;
  border-radius: 50%;
}

.view-mesas__legend .is-livre {
  background: var(--color-status-livre);
}

.view-mesas__legend .is-ocupada {
  background: var(--color-status-ocupada);
}

.view-mesas__legend .is-espera {
  background: var(--color-status-espera);
}

.view-mesas__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 16px;
}

@media (min-width: 768px) {
  .view-mesas__grid {
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 20px;
  }
}
</style>
