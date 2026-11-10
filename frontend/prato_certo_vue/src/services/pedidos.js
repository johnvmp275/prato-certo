import { api } from '@/plugins/axios';

/**
 * Operações do processo principal: abrir comanda, itens, espera, desconto, pagamento.
 */
export const pedidosService = {
  listar: (params = {}) => api.get('/pedidos', { params }).then(({ data }) => data),
  buscar: id => api.get(`/pedidos/${id}`).then(({ data }) => data),
  abrir: dados => api.post('/pedidos', dados).then(({ data }) => data),
  atualizar: (id, dados) => api.put(`/pedidos/${id}`, dados).then(({ data }) => data),
  colocarEmEspera: id => api.post(`/pedidos/${id}/espera`).then(({ data }) => data),
  retomar: id => api.post(`/pedidos/${id}/retomar`).then(({ data }) => data),
  aceitar: id => api.post(`/pedidos/${id}/aceitar`).then(({ data }) => data),
  aplicarDesconto: (id, dados) => api.post(`/pedidos/${id}/desconto`, dados).then(({ data }) => data),
  removerDesconto: id => api.delete(`/pedidos/${id}/desconto`).then(({ data }) => data),
  pagar: (id, dados) => api.post(`/pedidos/${id}/pagamentos`, dados).then(({ data }) => data),
  finalizar: id => api.post(`/pedidos/${id}/finalizar`).then(({ data }) => data),
  cancelar: id => api.post(`/pedidos/${id}/cancelar`).then(({ data }) => data),
};
