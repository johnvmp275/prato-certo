import { defineStore } from 'pinia';

const CHAVE = 'prato-certo:carrinho';

// Lê o carrinho salvo no navegador (conveniência do cliente; pode não existir)
function lerCarrinho() {
  try {
    return JSON.parse(localStorage.getItem(CHAVE)) ?? [];
  } catch {
    return [];
  }
}

function salvarCarrinho(itens) {
  try {
    localStorage.setItem(CHAVE, JSON.stringify(itens));
  } catch {
    // Navegação privada ou armazenamento bloqueado: segue sem salvar
  }
}

export const useCardapio = defineStore('cardapio', {
  state: () => ({
    itens: lerCarrinho(),
    aberto: false,
  }),
  getters: {
    quantidadeItens: state => state.itens.reduce((total, item) => total + item.quantidade, 0),
    subtotal: state =>
      Math.round(state.itens.reduce((total, item) => total + item.preco * item.quantidade, 0) * 100) / 100,
  },
  actions: {
    adicionar(produto, quantidade = 1, observacao = '') {
      const existente = this.itens.find(
        item => item.produtoId === produto.id && item.observacao === observacao,
      );
      if (existente) {
        existente.quantidade += quantidade;
      } else {
        this.itens.push({
          produtoId: produto.id,
          nome: produto.nome,
          preco: produto.preco,
          imagem: produto.imagem,
          quantidade,
          observacao,
        });
      }
      salvarCarrinho(this.itens);
    },
    alterarQuantidade(index, quantidade) {
      if (quantidade < 1) {
        this.itens.splice(index, 1);
      } else {
        this.itens[index].quantidade = quantidade;
      }
      salvarCarrinho(this.itens);
    },
    limpar() {
      this.itens = [];
      salvarCarrinho(this.itens);
    },
  },
});
