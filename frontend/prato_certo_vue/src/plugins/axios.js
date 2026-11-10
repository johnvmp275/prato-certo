import axios from 'axios';

import { useAuth } from '@/stores/useAuth';
import { useToast } from '@/stores/useToast';

/**
 * Instância única do Axios para a API do Prato Certo.
 * Em desenvolvimento o Vite faz proxy de /api para o PHP (mesmo domínio = cookie de sessão).
 */
export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
  },
});

/**
 * Converte qualquer erro do Axios em uma mensagem amigável.
 * A API sempre responde { error, campo } nos erros tratados.
 */
export function getMensagemErro(error) {
  if (error?.response?.data?.error) {
    return error.response.data.error;
  }

  if (error?.code === 'ERR_NETWORK') {
    return 'Não foi possível conectar ao servidor. Verifique sua conexão.';
  }

  return 'Ocorreu um erro inesperado. Tente novamente.';
}

/**
 * Registra os interceptadores (mensagens de erro e sessão expirada).
 */
export function setupAxios(router) {
  api.interceptors.response.use(
    response => response,
    error => {
      const status = error?.response?.status;

      // Anexa a mensagem e o campo do erro para os formulários
      error.mensagem = getMensagemErro(error);
      error.campo = error?.response?.data?.campo ?? null;

      // Sessão expirada: volta para o login (exceto nas rotas públicas e na
      // verificação inicial da sessão, que o guard do router já trata)
      const rotaAtual = router.currentRoute.value;
      const verificacaoSessao = error?.config?.url === '/auth/me';
      if (status === 401 && !rotaAtual.meta.publica && !verificacaoSessao) {
        const auth = useAuth();
        auth.limpar();

        if (rotaAtual.name !== 'login') {
          useToast().error(error.mensagem);
          router.push({ name: 'login', query: { redirect: rotaAtual.fullPath } });
        }
      }

      return Promise.reject(error);
    },
  );
}
