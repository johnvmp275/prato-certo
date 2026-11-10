<?php

namespace App\Model\Entity;

use \App\DatabaseManager\Database;
use \App\Exception\DadoInvalidoException;

class Produto{

    /**
     * Id do produto
     * @var integer
     */
    public $id;

    /**
     * Id da categoria do produto
     * @var integer
     */
    public $categoriaId;

    /**
     * Nome do produto
     * @var string
     */
    public $nome;

    /**
     * Descrição do produto
     * @var string
     */
    public $descricao;

    /**
     * Preço de venda do produto
     * @var float
     */
    public $preco = 0;

    /**
     * URL da imagem do produto
     * @var string
     */
    public $imagem;

    /**
     * Indica se o produto controla estoque
     * @var boolean
     */
    public $controlaEstoque = 0;

    /**
     * Quantidade em estoque
     * @var integer
     */
    public $estoque = 0;

    /**
     * Status do produto (ativo | inativo)
     * @var string
     */
    public $status = 'ativo';

    /**
     * Data de cadastro
     * @var string
     */
    public $criadoEm;

    /**
     * Método responsável por verificar se o produto pode ser vendido
     * @return boolean
     */
    public function isDisponivel(){
        return $this->status == 'ativo' and $this->possuiEstoque(1);
    }

    /**
     * Método responsável por verificar se há estoque para a quantidade informada
     * @param  integer $quantidade
     * @return boolean
     */
    public function possuiEstoque($quantidade){
        //PRODUTOS SEM CONTROLE DE ESTOQUE SEMPRE ESTÃO DISPONÍVEIS
        if(!$this->controlaEstoque) return true;

        return (int)$this->estoque >= (int)$quantidade;
    }

    /**
     * Método responsável por dar baixa no estoque do produto
     * @param integer $quantidade
     */
    public function baixarEstoque($quantidade){
        //PRODUTOS SEM CONTROLE DE ESTOQUE NÃO SÃO MOVIMENTADOS
        if(!$this->controlaEstoque) return;

        //VALIDA A QUANTIDADE DISPONÍVEL
        if(!$this->possuiEstoque($quantidade)){
            throw new DadoInvalidoException("Estoque insuficiente para o produto '".$this->nome."'. Disponível: ".(int)$this->estoque.".");
        }

        //ATUALIZA O ESTOQUE DE FORMA ATÔMICA NO BANCO
        (new Database('produtos'))->execute('UPDATE produtos SET estoque = estoque - ? WHERE id = ?', [(int)$quantidade, $this->id]);
        $this->estoque -= (int)$quantidade;
    }

    /**
     * Método responsável por cadastrar a instância atual no banco de dados
     * @return boolean
     */
    public function cadastrar(){
        //DATA DE CADASTRO
        $this->criadoEm = date('Y-m-d H:i:s');

        //INSERE O PRODUTO NO BANCO DE DADOS
        $this->id = (new Database('produtos'))->insert([
            'categoriaId'     => $this->categoriaId,
            'nome'            => $this->nome,
            'descricao'       => $this->descricao,
            'preco'           => $this->preco,
            'imagem'          => $this->imagem,
            'controlaEstoque' => $this->controlaEstoque ? 1 : 0,
            'estoque'         => (int)$this->estoque,
            'status'          => $this->status,
            'criadoEm'        => $this->criadoEm
        ]);

        //SUCESSO
        return true;
    }

    /**
     * Método responsável por atualizar os dados do banco com a instância atual
     * @return boolean
     */
    public function atualizar(){
        //ATUALIZA O PRODUTO NO BANCO DE DADOS
        return (new Database('produtos'))->update('id = ?', [
            'categoriaId'     => $this->categoriaId,
            'nome'            => $this->nome,
            'descricao'       => $this->descricao,
            'preco'           => $this->preco,
            'imagem'          => $this->imagem,
            'controlaEstoque' => $this->controlaEstoque ? 1 : 0,
            'estoque'         => (int)$this->estoque,
            'status'          => $this->status
        ], [$this->id]);
    }

    /**
     * Método responsável por inativar o produto (mantém o histórico dos pedidos)
     * @return boolean
     */
    public function inativar(){
        $this->status = 'inativo';
        return $this->atualizar();
    }

    /**
     * Método responsável por retornar os dados do produto em array
     * @return array
     */
    public function toArray(){
        return [
            'id'              => (int)$this->id,
            'categoriaId'     => (int)$this->categoriaId,
            'categoria'       => $this->categoria ?? null,
            'nome'            => $this->nome,
            'descricao'       => $this->descricao,
            'preco'           => (float)$this->preco,
            'imagem'          => $this->imagem,
            'controlaEstoque' => (bool)$this->controlaEstoque,
            'estoque'         => (int)$this->estoque,
            'disponivel'      => $this->isDisponivel(),
            'status'          => $this->status
        ];
    }

    /**
     * Nome da categoria (preenchido quando a consulta faz JOIN)
     * @var string|null
     */
    public $categoria = null;

    /**
     * Método responsável por retornar um produto com base em seu ID
     * @param  integer $id
     * @return Produto|false
     */
    public static function getProdutoById($id){
        return self::getProdutos('id = ?', null, null, '*', [(int)$id])->fetchObject(self::class);
    }

    /**
     * Método responsável por retornar produtos
     * @param  string $where
     * @param  string $order
     * @param  string $limit
     * @param  string $fields
     * @param  array  $params
     * @return PDOStatement
     */
    public static function getProdutos($where = null, $order = null, $limit = null, $fields = '*', $params = []){
        return (new Database('produtos'))->select($where, $order, $limit, $fields, $params);
    }
}
