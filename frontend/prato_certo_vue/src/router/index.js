import { createRouter, createWebHistory } from 'vue-router';

import { useAuth } from '@/stores/useAuth';

import AuthLayout from '@/layouts/AuthLayout.vue';
import CardapioLayout from '@/layouts/CardapioLayout.vue';

const router = createRouter({
  history: createWebHistory(),
  scrollBehavior: () => ({ top: 0 }),
  routes: [
    /* ===========================
     * CARDÁPIO ONLINE (PÚBLICO) - abre direto em "/", sem login
     * =========================== */
    {
      name: 'cardapio',
      path: '/',
      component: () => import('@/views/ViewCardapio.vue'),
      meta: { layout: CardapioLayout, publica: true, titulo: 'Cardápio' },
    },
    // O acompanhamento agora abre em janela no próprio cardápio; links antigos continuam funcionando
    {
      name: 'cardapio-pedido',
      path: '/pedido/:codigo',
      redirect: to => ({ path: '/', query: { pedido: to.params.codigo } }),
    },

    /* ===========================
     * PAINEL / PDV - todas as rotas em /painel/...
     * =========================== */
    {
      name: 'login',
      path: '/painel/login',
      component: () => import('@/views/ViewLogin.vue'),
      meta: { layout: AuthLayout, publica: true, titulo: 'Entrar' },
    },
    {
      name: 'dashboard',
      path: '/painel',
      component: () => import('@/views/ViewDashboard.vue'),
      meta: { permissao: 'dashboard', titulo: 'Painel' },
    },
    {
      name: 'pdv',
      path: '/painel/pdv/:id?',
      component: () => import('@/views/ViewPdv.vue'),
      meta: { permissao: 'pdv', titulo: 'PDV' },
    },
    {
      name: 'mesas',
      path: '/painel/mesas',
      component: () => import('@/views/ViewMesas.vue'),
      meta: { permissao: 'mesas', titulo: 'Mesas' },
    },
    {
      name: 'pedidos',
      path: '/painel/pedidos',
      component: () => import('@/views/ViewPedidos.vue'),
      meta: { permissao: 'pedidos', titulo: 'Pedidos' },
    },
    {
      name: 'pagamento',
      path: '/painel/pedidos/:id/pagamento',
      component: () => import('@/views/ViewPagamento.vue'),
      meta: { permissao: 'pdv', titulo: 'Pagamento' },
    },
    {
      name: 'clientes',
      path: '/painel/clientes',
      component: () => import('@/views/ViewClientes.vue'),
      meta: { permissao: 'clientes', titulo: 'Clientes' },
    },
    {
      name: 'produtos',
      path: '/painel/produtos',
      component: () => import('@/views/ViewProdutos.vue'),
      meta: { permissao: 'produtos', titulo: 'Produtos' },
    },
    {
      name: 'categorias',
      path: '/painel/categorias',
      component: () => import('@/views/ViewCategorias.vue'),
      meta: { permissao: 'produtos.gerenciar', titulo: 'Categorias' },
    },
    {
      name: 'cupons',
      path: '/painel/cupons',
      component: () => import('@/views/ViewCupons.vue'),
      meta: { permissao: 'cupons', titulo: 'Cupons' },
    },
    {
      name: 'usuarios',
      path: '/painel/usuarios',
      component: () => import('@/views/ViewUsuarios.vue'),
      meta: { permissao: 'usuarios', titulo: 'Usuários' },
    },
    {
      name: 'configuracoes',
      path: '/painel/configuracoes',
      component: () => import('@/views/ViewConfiguracoes.vue'),
      meta: { permissao: 'configuracoes', titulo: 'Configurações' },
    },
    {
      name: 'nao-encontrado',
      path: '/:pathMatch(.*)*',
      component: () => import('@/views/ViewNotFound.vue'),
      meta: { layout: AuthLayout, publica: true, titulo: 'Página não encontrada' },
    },
  ],
});

// Protege somente as rotas do painel (login + permissão do perfil); o cardápio é público
router.beforeEach(async to => {
  document.title = to.meta.titulo ? `${to.meta.titulo} · Prato Certo` : 'Prato Certo';

  if (to.meta.publica) return true;

  const auth = useAuth();
  await auth.carregar();

  if (!auth.isLogado) {
    return { name: 'login', query: { redirect: to.fullPath } };
  }

  if (!auth.pode(to.meta.permissao)) {
    return auth.pode('pdv') && to.name !== 'pdv' ? { name: 'pdv' } : { name: 'dashboard' };
  }

  return true;
});

export default router;
