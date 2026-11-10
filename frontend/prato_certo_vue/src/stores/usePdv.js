import { defineStore } from 'pinia';

/**
 * Carrinho do PDV. Os itens ficam no navegador até o atendente salvar,
 * colocar em espera ou ir para o pagamento (aí são enviados para a API).
 */
export const usePdv = defineStore('pdv', {
  state: () => ({
    pedido: null,
    tipo: 'balcao',
    cliente: null,
    observacao: '',
    itens: [],
    alterado: false,
  }),
  getters: {
    quantidadeItens: state => state.itens.reduce((total, item) => total + item.quantidade, 0),
    subtotal: state =>
      Math.round(state.itens.reduce((total, item) => total + item.preco * item.quantidade, 0) * 100) / 100,
    isNovo: state => !state.pedido,
  },
  actions: {
    // Inicia um pedido novo de balcão
    novo() {
      this.$reset();
    },
    // Carrega um pedido existente da API para o carrinho
    carregar(pedido) {
      this.pedido = pedido;
      this.tipo = pedido.tipo;
      this.cliente = pedido.cliente;
      this.observacao = pedido.observacao ?? '';
      this.itens = (pedido.itens ?? []).map(item => ({
        produtoId: item.produtoId,
        nome: item.nomeProduto,
        preco: item.precoUnitario,
        imagem: null,
        quantidade: item.quantidade,
        observacao: item.observacao ?? '',
      }));
      this.alterado = false;
    },
    adicionar(produto) {
      const existente = this.itens.find(item => item.produtoId === produto.id && !item.observacao);
      if (existente) {
        existente.quantidade += 1;
      } else {
        this.itens.push({
          produtoId: produto.id,
          nome: produto.nome,
          preco: produto.preco,
          imagem: produto.imagem,
          quantidade: 1,
          observacao: '',
        });
      }
      this.alterado = true;
    },
    alterarQuantidade(index, quantidade) {
      if (quantidade < 1) {
        this.remover(index);
        return;
      }
      this.itens[index].quantidade = quantidade;
      this.alterado = true;
    },
    alterarObservacao(index, observacao) {
      this.itens[index].observacao = observacao;
      this.alterado = true;
    },
    remover(index) {
      this.itens.splice(index, 1);
      this.alterado = true;
    },
    definirCliente(cliente) {
      this.cliente = cliente ? { id: cliente.id, nome: cliente.nome, telefone: cliente.telefone } : null;
      this.alterado = true;
    },
    // Dados enviados para a API
    payload() {
      return {
        clienteId: this.cliente?.id ?? null,
        observacao: this.observacao,
        itens: this.itens.map(item => ({
          produtoId: item.produtoId,
          quantidade: item.quantidade,
          observacao: item.observacao,
        })),
      };
    },
  },
});
