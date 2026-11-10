import { defineStore } from 'pinia';

import { authService } from '@/services/painel';

export const useAuth = defineStore('auth', {
  state: () => ({
    usuario: null,
    carregado: false,
  }),
  getters: {
    isLogado: state => Boolean(state.usuario),
    isAdmin: state => state.usuario?.perfil === 'administrador',
  },
  actions: {
    // Verifica se o usuário pode acessar um recurso (mesma regra do backend)
    pode(recurso) {
      if (!recurso) return true;
      const permissoes = this.usuario?.permissoes ?? [];
      return permissoes.includes('*') || permissoes.includes(recurso);
    },
    // Carrega o usuário da sessão PHP (apenas uma vez por carregamento da página)
    async carregar() {
      if (this.carregado) return this.usuario;

      try {
        this.usuario = await authService.me();
      } catch {
        this.usuario = null;
      } finally {
        this.carregado = true;
      }

      return this.usuario;
    },
    async login(email, senha) {
      this.usuario = await authService.login(email, senha);
      this.carregado = true;
      return this.usuario;
    },
    async logout() {
      try {
        await authService.logout();
      } finally {
        this.limpar();
      }
    },
    limpar() {
      this.usuario = null;
      this.carregado = true;
    },
  },
});
