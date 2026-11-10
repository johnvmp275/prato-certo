<?php

namespace App\Model\Entity;

use \App\DatabaseManager\Database;
use \App\Exception\DadoInvalidoException;

class ItemPedido{

    /**
     * Id do item
     * @var integer
     */
    public $id;

    /**
     * Id do pedido ao qual o item pertence (composição)
     * @var integer
     */
    public $pedidoId;

    /**
     * Id do produto vendido
     * @var integer
     */
    public $produtoId;

    /**
     * Nome do produto no momento da venda (preserva o histórico)
     * @var string
     */
    public $nomeProduto;

    /**
     * Quantidade vendida
     * @var integer
     */
    public $quantidade = 1;

    /**
     * Preço unitário no momento da venda
     * @var float
     */
    public $precoUnitario = 0;

    /**
     * Observação do item (ex: sem cebola)
     * @var string|null
     */
    public $observacao;

    /**
     * Método responsável por calcular o subtotal do item
     * @return float
     */
    public function calcularSubtotal(){
        return round((int)$this->quantidade * (float)$this->precoUnitario, 2);
    }

    /**
     * Método responsável por alterar a quantidade do item
     * @param integer $quantidade
     */
    public function alterarQuantidade($quantidade){
        //VALIDA A QUANTIDADE (INTEIRA E MAIOR QUE ZERO)
        if(filter_var($quantidade, FILTER_VALIDATE_INT) === false or (int)$quantidade < 1){
            throw new DadoInvalidoException("A quantidade de '".$this->nomeProduto."' deve ser um número inteiro maior que zero.", null, 'quantidade');
        }

        //LIMITE DE SEGURANÇA POR ITEM
        if((int)$quantidade > 999){
            throw new DadoInvalidoException("A quantidade máxima por item é 999.", null, 'quantidade');
        }

        $this->quantidade = (int)$quantidade;
    }

    /**
     * Método responsável por cadastrar a instância atual no banco de dados
     * @return boolean
     */
    public function cadastrar(){
        //INSERE O ITEM NO BANCO DE DADOS
        $this->id = (new Database('itens_pedido'))->insert([
            'pedidoId'      => $this->pedidoId,
            'produtoId'     => $this->produtoId,
            'nomeProduto'   => $this->nomeProduto,
            'quantidade'    => $this->quantidade,
            'precoUnitario' => $this->precoUnitario,
            'observacao'    => $this->observacao
        ]);

        //SUCESSO
        return true;
    }

    /**
     * Método responsável por retornar os dados do item em array
     * @return array
     */
    public function toArray(){
        return [
            'id'            => (int)$this->id,
            'produtoId'     => (int)$this->produtoId,
            'nomeProduto'   => $this->nomeProduto,
            'quantidade'    => (int)$this->quantidade,
            'precoUnitario' => (float)$this->precoUnitario,
            'subtotal'      => $this->calcularSubtotal(),
            'observacao'    => $this->observacao
        ];
    }

    /**
     * Método responsável por retornar os itens de um pedido
     * @param  integer $pedidoId
     * @return ItemPedido[]
     */
    public static function getItensByPedido($pedidoId){
        //ITENS
        $itens = [];

        //BUSCA OS ITENS NO BANCO
        $results = (new Database('itens_pedido'))->select('pedidoId = ?', 'id ASC', null, '*', [(int)$pedidoId]);
        while($obItem = $results->fetchObject(self::class)){
            $itens[] = $obItem;
        }

        return $itens;
    }

    /**
     * Método responsável por excluir todos os itens de um pedido
     * @param  integer $pedidoId
     * @return boolean
     */
    public static function excluirByPedido($pedidoId){
        return (new Database('itens_pedido'))->delete('pedidoId = ?', [(int)$pedidoId]);
    }
}
