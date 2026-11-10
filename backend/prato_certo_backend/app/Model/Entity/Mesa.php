<?php

namespace App\Model\Entity;

use \App\DatabaseManager\Database;
use \App\Exception\DadoInvalidoException;
use \App\Exception\EstadoInvalidoException;

class Mesa{

    /**
     * Id da mesa
     * @var integer
     */
    public $id;

    /**
     * Número da mesa no salão
     * @var integer
     */
    public $numero;

    /**
     * Quantidade máxima de pessoas
     * @var integer
     */
    public $capacidade = 4;

    /**
     * Situação da mesa (livre | ocupada)
     * @var string
     */
    public $situacao = 'livre';

    /**
     * Status do cadastro (ativo | inativo)
     * @var string
     */
    public $status = 'ativo';

    /**
     * Método responsável por verificar se a mesa está livre
     * @return boolean
     */
    public function isLivre(){
        return $this->situacao == 'livre';
    }

    /**
     * Método responsável por validar o número de pessoas para a mesa (RN04)
     * @param integer $quantidade
     */
    public function validarPessoas($quantidade){
        if((int)$quantidade < 1 or (int)$quantidade > (int)$this->capacidade){
            throw new DadoInvalidoException("A mesa ".$this->numero." comporta de 1 a ".$this->capacidade." pessoas.", null, 'numPessoas');
        }
    }

    /**
     * Método responsável por ocupar a mesa (RN03)
     */
    public function ocupar(){
        //VALIDA SE A MESA ESTÁ ATIVA
        if($this->status != 'ativo'){
            throw new EstadoInvalidoException("A mesa ".$this->numero." está inativa.");
        }

        //VALIDA SE A MESA ESTÁ LIVRE
        if(!$this->isLivre()){
            throw new EstadoInvalidoException("A mesa ".$this->numero." já está ocupada.");
        }

        $this->situacao = 'ocupada';
        $this->atualizar();
    }

    /**
     * Método responsável por liberar a mesa (RN03)
     */
    public function liberar(){
        $this->situacao = 'livre';
        $this->atualizar();
    }

    /**
     * Método responsável por cadastrar a instância atual no banco de dados
     * @return boolean
     */
    public function cadastrar(){
        //INSERE A MESA NO BANCO DE DADOS
        $this->id = (new Database('mesas'))->insert([
            'numero'     => $this->numero,
            'capacidade' => $this->capacidade,
            'situacao'   => $this->situacao,
            'status'     => $this->status
        ]);

        //SUCESSO
        return true;
    }

    /**
     * Método responsável por atualizar os dados do banco com a instância atual
     * @return boolean
     */
    public function atualizar(){
        //ATUALIZA A MESA NO BANCO DE DADOS
        return (new Database('mesas'))->update('id = ?', [
            'numero'     => $this->numero,
            'capacidade' => $this->capacidade,
            'situacao'   => $this->situacao,
            'status'     => $this->status
        ], [$this->id]);
    }

    /**
     * Método responsável por inativar a mesa
     * @return boolean
     */
    public function inativar(){
        //NÃO PERMITE INATIVAR UMA MESA OCUPADA
        if(!$this->isLivre()){
            throw new EstadoInvalidoException("Não é possível inativar a mesa ".$this->numero." enquanto ela estiver ocupada.");
        }

        $this->status = 'inativo';
        return $this->atualizar();
    }

    /**
     * Método responsável por retornar o pedido em andamento da mesa
     * @return PedidoMesa|null
     */
    public function getPedidoAberto(){
        $dados = Pedido::getPedidos('mesaId = ? AND status IN ("aberto","em_espera")', 'id DESC', 1, '*', [$this->id])->fetch(\PDO::FETCH_ASSOC);
        return $dados ? Pedido::instanciar($dados) : null;
    }

    /**
     * Método responsável por retornar os dados da mesa em array
     * @return array
     */
    public function toArray(){
        return [
            'id'         => (int)$this->id,
            'numero'     => (int)$this->numero,
            'capacidade' => (int)$this->capacidade,
            'situacao'   => $this->situacao,
            'status'     => $this->status
        ];
    }

    /**
     * Método responsável por retornar uma mesa com base em seu ID
     * @param  integer $id
     * @return Mesa|false
     */
    public static function getMesaById($id){
        return self::getMesas('id = ?', null, null, '*', [(int)$id])->fetchObject(self::class);
    }

    /**
     * Método responsável por retornar mesas
     * @param  string $where
     * @param  string $order
     * @param  string $limit
     * @param  string $fields
     * @param  array  $params
     * @return PDOStatement
     */
    public static function getMesas($where = null, $order = null, $limit = null, $fields = '*', $params = []){
        return (new Database('mesas'))->select($where, $order, $limit, $fields, $params);
    }
}
