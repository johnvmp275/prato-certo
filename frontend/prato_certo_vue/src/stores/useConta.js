import { defineStore } from 'pinia';

import { contaService } from '@/services/painel';

/**
 * Conta do cliente no cardápio online (separada do login do painel).
 */
export const useConta = defineStore('conta', {
  state: () => ({
    cliente: null,
    carregado: false,
    // Janela da conta (entrar / cadastrar / meus pedidos / meus dados)
    aberta: false,
  }),
  getters: {
    isLogado: state => Boolean(state.cliente),
    primeiroNome: state => state.cliente?.nome?.split(' ')[0] ?? '',
  },
  actions: {
    // Carrega o cliente da sessão PHP (apenas uma vez por carregamento da página)
    async carregar() {
      if (this.carregado) return this.cliente;

      try {
        this.cliente = await contaService.me();
      } catch {
        this.cliente = null;
      } finally {
        this.carregado = true;
      }

      return this.cliente;
    },
    async login(telefone, senha) {
      this.cliente = await contaService.login(telefone, senha);
      return this.cliente;
    },
    async cadastrar(dados) {
      this.cliente = await contaService.cadastrar(dados);
      return this.cliente;
    },
    async atualizar(dados) {
      this.cliente = await contaService.atualizar(dados);
      return this.cliente;
    },
    async logout() {
      try {
        await contaService.logout();
      } finally {
        this.cliente = null;
      }
    },
  },
});
