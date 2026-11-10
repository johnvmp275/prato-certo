import { defineStore } from 'pinia';

const CHAVE = 'prato-certo:pedidos';
const LIMITE = 20;

// Códigos dos pedidos feitos neste aparelho (para quem pede sem conta)
function lerCodigos() {
  try {
    const codigos = JSON.parse(localStorage.getItem(CHAVE));
    return Array.isArray(codigos) ? codigos.filter(codigo => /^\d{7}$/.test(codigo)) : [];
  } catch {
    return [];
  }
}

function salvarCodigos(codigos) {
  try {
    localStorage.setItem(CHAVE, JSON.stringify(codigos));
  } catch {
    // Navegação privada ou armazenamento bloqueado: segue sem salvar
  }
}

/**
 * Janela "Meus pedidos" do cardápio: lista e acompanhamento de cada pedido.
 */
export const usePedidosCardapio = defineStore('pedidosCardapio', {
  state: () => ({
    codigos: lerCodigos(),
    aberto: false,
    // Código do pedido em acompanhamento (null = lista de pedidos)
    codigo: null,
  }),
  actions: {
    registrar(codigo) {
      this.codigos = [codigo, ...this.codigos.filter(item => item !== codigo)].slice(0, LIMITE);
      salvarCodigos(this.codigos);
    },
    abrirLista() {
      this.codigo = null;
      this.aberto = true;
    },
    abrirPedido(codigo) {
      this.codigo = codigo;
      this.aberto = true;
    },
    fechar() {
      this.aberto = false;
      this.codigo = null;
    },
  },
});
