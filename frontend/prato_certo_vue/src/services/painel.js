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
  // Filtros opcionais: { categoria, busca } (viram ?categoria=&busca= na API)
  buscar: (params = {}) => api.get('/cardapio', { params }).then(({ data }) => data),
  enviarPedido: dados => api.post('/cardapio/pedidos', dados).then(({ data }) => data),
  acompanhar: codigo => api.get(`/cardapio/pedidos/${codigo}`).then(({ data }) => data),
  pix: codigo => api.get(`/cardapio/pedidos/${codigo}/pix`).then(({ data }) => data),
  validarCupom: (codigo, subtotal) => api.post('/cardapio/cupom', { codigo, subtotal }).then(({ data }) => data),
};

/**
 * Conta do cliente no cardápio online (login opcional por telefone + senha).
 */
export const contaService = {
  cadastrar: dados => api.post('/cardapio/conta/cadastro', dados).then(({ data }) => data),
  login: (telefone, senha) => api.post('/cardapio/conta/login', { telefone, senha }).then(({ data }) => data),
  me: () => api.get('/cardapio/conta').then(({ data }) => data),
  atualizar: dados => api.put('/cardapio/conta', dados).then(({ data }) => data),
  pedidos: (params = {}) => api.get('/cardapio/conta/pedidos', { params }).then(({ data }) => data),
  logout: () => api.post('/cardapio/conta/logout').then(({ data }) => data),
};
