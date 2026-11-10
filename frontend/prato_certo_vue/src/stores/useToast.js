import { defineStore } from 'pinia';

let proximoId = 1;

export const useToast = defineStore('toast', {
  state: () => ({
    mensagens: [],
  }),
  actions: {
    mostrar(texto, tipo = 'info', duracao = 4000) {
      const id = proximoId++;
      this.mensagens.push({ id, texto, tipo });
      setTimeout(() => this.fechar(id), duracao);
    },
    success(texto) {
      this.mostrar(texto, 'success');
    },
    error(texto) {
      this.mostrar(texto, 'error', 5500);
    },
    info(texto) {
      this.mostrar(texto, 'info');
    },
    fechar(id) {
      this.mensagens = this.mensagens.filter(mensagem => mensagem.id !== id);
    },
  },
});
