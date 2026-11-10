<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

import { useRouter } from 'vue-router';

import IconHome from '@/components/Icons/IconHome.vue';
import IconGrid from '@/components/Icons/IconGrid.vue';
import IconTable from '@/components/Icons/IconTable.vue';
import IconReceipt from '@/components/Icons/IconReceipt.vue';
import IconUsers from '@/components/Icons/IconUsers.vue';
import IconBox from '@/components/Icons/IconBox.vue';
import IconTag from '@/components/Icons/IconTag.vue';
import IconTicket from '@/components/Icons/IconTicket.vue';
import IconUserCog from '@/components/Icons/IconUserCog.vue';
import IconSettings from '@/components/Icons/IconSettings.vue';
import IconStore from '@/components/Icons/IconStore.vue';

import NavigationHeader from '../NavigationHeader/NavigationHeader.vue';
import NavigationMenu from '../NavigationMenu/NavigationMenu.vue';
import NavigationProfile from '../NavigationProfile/NavigationProfile.vue';

import { useAuth } from '@/stores/useAuth';
import { pedidosService } from '@/services/pedidos';

defineOptions({
  name: 'NavigationInner',
});

defineProps({
  compact: {
    type: Boolean,
    default: false,
  },
});

const auth = useAuth();
const router = useRouter();

// Quantidade de pedidos online aguardando aceite (atualizada a cada 30s)
const pendentesOnline = ref(0);
let intervalo = null;

async function atualizarPendentes() {
  try {
    const { paginacao } = await pedidosService.listar({ status: 'pendente', ipp: 1 });
    pendentesOnline.value = paginacao.total;
  } catch {
    // Silencioso: o badge é apenas informativo
  }
}

onMounted(() => {
  atualizarPendentes();
  intervalo = setInterval(atualizarPendentes, 30000);
});

onBeforeUnmount(() => clearInterval(intervalo));

// Itens do menu filtrados pela permissão do perfil logado
const menuItems = computed(() =>
  [
    { key: 'dashboard', icon: IconHome, label: 'Painel', link: '/painel', permissao: 'dashboard' },
    { key: 'pdv', icon: IconGrid, label: 'PDV', link: '/painel/pdv', permissao: 'pdv' },
    { key: 'mesas', icon: IconTable, label: 'Mesas', link: '/painel/mesas', permissao: 'mesas' },
    {
      key: 'pedidos',
      icon: IconReceipt,
      label: 'Pedidos',
      link: '/painel/pedidos',
      permissao: 'pedidos',
      badge: pendentesOnline.value || null,
    },
    { key: 'clientes', icon: IconUsers, label: 'Clientes', link: '/painel/clientes', permissao: 'clientes' },
    { key: 'produtos', icon: IconBox, label: 'Produtos', link: '/painel/produtos', permissao: 'produtos' },
    { key: 'categorias', icon: IconTag, label: 'Categorias', link: '/painel/categorias', permissao: 'produtos.gerenciar' },
    { key: 'cupons', icon: IconTicket, label: 'Cupons', link: '/painel/cupons', permissao: 'cupons' },
    { key: 'usuarios', icon: IconUserCog, label: 'Usuários', link: '/painel/usuarios', permissao: 'usuarios' },
    { key: 'configuracoes', icon: IconSettings, label: 'Ajustes', link: '/painel/configuracoes', permissao: 'configuracoes' },
    { key: 'cardapio', icon: IconStore, label: 'Cardápio', link: '/', externo: true },
  ].filter(item => !item.permissao || auth.pode(item.permissao)),
);

async function sair() {
  await auth.logout();
  router.push({ name: 'login' });
}
</script>

<template>
  <div
    class="navigation-inner"
    :class="{ 'is-compact': compact }"
  >
    <NavigationHeader :compact="compact" />

    <NavigationMenu
      :items="menuItems"
      :compact="compact"
    />

    <NavigationProfile
      :name="auth.usuario?.nome ?? ''"
      :role="auth.usuario?.perfilDescricao ?? ''"
      :compact="compact"
      @logout="sair"
    />
  </div>
</template>

<style scoped>
.navigation-inner {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.navigation-inner:not(.is-compact) :deep(.navigation-header) {
  padding: 20px 20px 16px;
}
</style>
