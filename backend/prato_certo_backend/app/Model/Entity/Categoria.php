<?php

namespace App\Model\Entity;

use \App\DatabaseManager\Database;

class Categoria{

    /**
     * Id da categoria
     * @var integer
     */
    public $id;

    /**
     * Nome da categoria
     * @var string
     */
    public $nome;

    /**
     * Ordem de exibição no PDV e no cardápio
     * @var integer
     */
    public $ordem = 0;

    /**
     * Status da categoria (ativo | inativo)
     * @var string
     */
    public $status = 'ativo';

    /**
     * Método responsável por cadastrar a instância atual no banco de dados
     * @return boolean
     */
    public function cadastrar(){
        //INSERE A CATEGORIA NO BANCO DE DADOS
        $this->id = (new Database('categorias'))->insert([
            'nome'   => $this->nome,
            'ordem'  => $this->ordem,
            'status' => $this->status
        ]);

        //SUCESSO
        return true;
    }

    /**
     * Método responsável por atualizar os dados do banco com a instância atual
     * @return boolean
     */
    public function atualizar(){
        //ATUALIZA A CATEGORIA NO BANCO DE DADOS
        return (new Database('categorias'))->update('id = ?', [
            'nome'   => $this->nome,
            'ordem'  => $this->ordem,
            'status' => $this->status
        ], [$this->id]);
    }

    /**
     * Método responsável por inativar a categoria (os produtos continuam existindo - agregação)
     * @return boolean
     */
    public function inativar(){
        $this->status = 'inativo';
        return $this->atualizar();
    }

    /**
     * Método responsável por retornar os produtos ativos da categoria
     * @return Produto[]
     */
    public function getProdutos($busca = ''){
        //PRODUTOS
        $produtos = [];

        //FILTROS (BUSCA OPCIONAL PELO NOME OU DESCRIÇÃO)
        $where  = 'categoriaId = ? AND status = "ativo"';
        $params = [$this->id];
        if(strlen($busca)){
            $where   .= ' AND (nome LIKE ? OR descricao LIKE ?)';
            $params[] = '%'.$busca.'%';
            $params[] = '%'.$busca.'%';
        }

        //BUSCA OS PRODUTOS DA CATEGORIA
        $results = Produto::getProdutos($where, 'nome ASC', null, '*', $params);
        while($obProduto = $results->fetchObject(Produto::class)){
            $produtos[] = $obProduto;
        }

        return $produtos;
    }

    /**
     * Método responsável por retornar os dados da categoria em array
     * @return array
     */
    public function toArray(){
        return [
            'id'     => (int)$this->id,
            'nome'   => $this->nome,
            'ordem'  => (int)$this->ordem,
            'status' => $this->status
        ];
    }

    /**
     * Método responsável por retornar uma categoria com base em seu ID
     * @param  integer $id
     * @return Categoria|false
     */
    public static function getCategoriaById($id){
        return self::getCategorias('id = ?', null, null, '*', [(int)$id])->fetchObject(self::class);
    }

    /**
     * Método responsável por retornar categorias
     * @param  string $where
     * @param  string $order
     * @param  string $limit
     * @param  string $fields
     * @param  array  $params
     * @return PDOStatement
     */
    public static function getCategorias($where = null, $order = null, $limit = null, $fields = '*', $params = []){
        return (new Database('categorias'))->select($where, $order, $limit, $fields, $params);
    }
}
