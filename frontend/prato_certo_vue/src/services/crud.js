import { api } from '@/plugins/axios';

/**
 * Cria as operações padrão de cadastro para um recurso da API
 * (listar, buscar, cadastrar, atualizar e inativar).
 * @param {string} recurso Ex: 'produtos'
 */
export function criarCrud(recurso) {
  return {
    listar: (params = {}) => api.get(`/${recurso}`, { params }).then(({ data }) => data),
    buscar: id => api.get(`/${recurso}/${id}`).then(({ data }) => data),
    cadastrar: dados => api.post(`/${recurso}`, dados).then(({ data }) => data),
    atualizar: (id, dados) => api.put(`/${recurso}/${id}`, dados).then(({ data }) => data),
    inativar: id => api.delete(`/${recurso}/${id}`).then(({ data }) => data),
  };
}

export const usuariosService = criarCrud('usuarios');
export const categoriasService = {
  ...criarCrud('categorias'),
  // Nova ordem do arrastar e soltar: a primeira categoria da lista fica na posição 1
  reordenar: ids => api.put('/categorias/ordem', { ids }).then(({ data }) => data),
};
export const produtosService = {
  ...criarCrud('produtos'),

  /**
   * Cadastra ou edita o produto enviando a imagem (multipart/form-data).
   * A edição usa POST porque o PHP só lê arquivos enviados em POST.
   */
  salvarComImagem: (id, formData) =>
    api
      .post(id ? `/produtos/${id}` : '/produtos', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      .then(({ data }) => data),
};
export const clientesService = criarCrud('clientes');
export const mesasService = criarCrud('mesas');
export const cuponsService = criarCrud('cupons');
