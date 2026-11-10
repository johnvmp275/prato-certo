import { api } from '@/plugins/axios';

/**
 * Autenticação do painel (sessão PHP).
 */
export const authService = {
  login: (email, senha) => api.post('/auth/login', { email, senha }).then(({ data }) => data),
  me: () => api.get('/auth/me').then(({ data }) => data),
  logout: () => api.post('/auth/logout').then(({ data }) => data),
};

/**
 * Resumo do dia e configurações do restaurante.
 */
export const dashboardService = {
  resumo: () => api.get('/dashboard').then(({ data }) => data),
};

export const configuracoesService = {
  buscar: () => api.get('/configuracoes').then(({ data }) => data),
  atualizar: dados => api.put('/configuracoes', dados).then(({ data }) => data),
};

/**
 * Cardápio online (rotas públicas).
 */
export const cardapioService = {
  buscar: () => api.get('/cardapio').then(({ data }) => data),
  enviarPedido: dados => api.post('/cardapio/pedidos', dados).then(({ data }) => data),
  acompanhar: codigo => api.get(`/cardapio/pedidos/${codigo}`).then(({ data }) => data),
};
