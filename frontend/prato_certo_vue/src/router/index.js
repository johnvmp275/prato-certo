import { createRouter, createWebHistory } from 'vue-router';

import { useAuth } from '@/stores/useAuth';

import AuthLayout from '@/layouts/AuthLayout.vue';
import CardapioLayout from '@/layouts/CardapioLayout.vue';

const router = createRouter({
  history: createWebHistory(),
  scrollBehavior: () => ({ top: 0 }),
  routes: [
    {
      name: 'login',
      path: '/login',
      component: () => import('@/views/ViewLogin.vue'),
      meta: { layout: AuthLayout, publica: true, titulo: 'Entrar' },
    },
    {
      name: 'dashboard',
      path: '/',
      component: () => import('@/views/ViewDashboard.vue'),
      meta: { permissao: 'dashboard', titulo: 'Painel' },
    },
    {
      name: 'pdv',
      path: '/pdv/:id?',
      component: () => import('@/views/ViewPdv.vue'),
      meta: { permissao: 'pdv', titulo: 'PDV' },
    },
    {
      name: 'mesas',
      path: '/mesas',
      component: () => import('@/views/ViewMesas.vue'),
      meta: { permissao: 'mesas', titulo: 'Mesas' },
    },
    {
      name: 'pedidos',
      path: '/pedidos',
      component: () => import('@/views/ViewPedidos.vue'),
      meta: { permissao: 'pedidos', titulo: 'Pedidos' },
    },
    {
      name: 'pagamento',
      path: '/pedidos/:id/pagamento',
      component: () => import('@/views/ViewPagamento.vue'),
      meta: { permissao: 'pdv', titulo: 'Pagamento' },
    },
    {
      name: 'clientes',
      path: '/clientes',
      component: () => import('@/views/ViewClientes.vue'),
      meta: { permissao: 'clientes', titulo: 'Clientes' },
    },
    {
      name: 'produtos',
      path: '/produtos',
      component: () => import('@/views/ViewProdutos.vue'),
      meta: { permissao: 'produtos', titulo: 'Produtos' },
    },
    {
      name: 'categorias',
      path: '/categorias',
      component: () => import('@/views/ViewCategorias.vue'),
      meta: { permissao: 'produtos.gerenciar', titulo: 'Categorias' },
    },
    {
      name: 'cupons',
      path: '/cupons',
      component: () => import('@/views/ViewCupons.vue'),
      meta: { permissao: 'cupons', titulo: 'Cupons' },
    },
    {
      name: 'usuarios',
      path: '/usuarios',
      component: () => import('@/views/ViewUsuarios.vue'),
      meta: { permissao: 'usuarios', titulo: 'Usuários' },
    },
    {
      name: 'configuracoes',
      path: '/configuracoes',
      component: () => import('@/views/ViewConfiguracoes.vue'),
      meta: { permissao: 'configuracoes', titulo: 'Configurações' },
    },
    {
      name: 'cardapio',
      path: '/cardapio',
      component: () => import('@/views/ViewCardapio.vue'),
      meta: { layout: CardapioLayout, publica: true, titulo: 'Cardápio' },
    },
    {
      name: 'cardapio-pedido',
      path: '/cardapio/pedido/:codigo',
      component: () => import('@/views/ViewCardapioPedido.vue'),
      meta: { layout: CardapioLayout, publica: true, titulo: 'Acompanhar pedido' },
    },
    {
      name: 'nao-encontrado',
      path: '/:pathMatch(.*)*',
      component: () => import('@/views/ViewNotFound.vue'),
      meta: { layout: AuthLayout, publica: true, titulo: 'Página não encontrada' },
    },
  ],
});

// Protege as rotas do painel (login + permissão do perfil)
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
