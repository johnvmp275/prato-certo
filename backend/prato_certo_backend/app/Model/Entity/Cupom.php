<?php

namespace App\Model\Entity;

use \App\DatabaseManager\Database;

class Cupom{

    /**
     * Id do cupom
     * @var integer
     */
    public $id;

    /**
     * Código digitado pelo cliente (ex: BEMVINDO10)
     * @var string
     */
    public $codigo;

    /**
     * Tipo do desconto (percentual | fixo)
     * @var string
     */
    public $tipo = 'percentual';

    /**
     * Valor do desconto (% ou R$)
     * @var float
     */
    public $valor = 0;

    /**
     * Data de validade (AAAA-MM-DD) ou nulo para sem validade
     * @var string|null
     */
    public $validade;

    /**
     * Status do cupom (ativo | inativo)
     * @var string
     */
    public $status = 'ativo';

    /**
     * Método responsável por verificar se o cupom pode ser usado (RN07)
     * @return boolean
     */
    public function isValido(){
        //VERIFICA O STATUS
        if($this->status != 'ativo') return false;

        //VERIFICA A VALIDADE
        return !$this->validade or $this->validade >= date('Y-m-d');
    }

    /**
     * Método responsável por calcular o valor do desconto sobre o subtotal
     * @param  float $subtotal
     * @return float
     */
    public function calcularDesconto($subtotal){
        //CALCULA PELO TIPO
        $desconto = $this->tipo == 'percentual' ? $subtotal * ($this->valor / 100) : (float)$this->valor;

        //O DESCONTO NUNCA PASSA DO SUBTOTAL
        return round(min($desconto, $subtotal), 2);
    }

    /**
     * Método responsável por cadastrar a instância atual no banco de dados
     * @return boolean
     */
    public function cadastrar(){
        //INSERE O CUPOM NO BANCO DE DADOS
        $this->id = (new Database('cupons'))->insert([
            'codigo'   => $this->codigo,
            'tipo'     => $this->tipo,
            'valor'    => $this->valor,
            'validade' => $this->validade ?: null,
            'status'   => $this->status
        ]);

        //SUCESSO
        return true;
    }

    /**
     * Método responsável por atualizar os dados do banco com a instância atual
     * @return boolean
     */
    public function atualizar(){
        //ATUALIZA O CUPOM NO BANCO DE DADOS
        return (new Database('cupons'))->update('id = ?', [
            'codigo'   => $this->codigo,
            'tipo'     => $this->tipo,
            'valor'    => $this->valor,
            'validade' => $this->validade ?: null,
            'status'   => $this->status
        ], [$this->id]);
    }

    /**
     * Método responsável por inativar o cupom
     * @return boolean
     */
    public function inativar(){
        $this->status = 'inativo';
        return $this->atualizar();
    }

    /**
     * Método responsável por retornar os dados do cupom em array
     * @return array
     */
    public function toArray(){
        return [
            'id'       => (int)$this->id,
            'codigo'   => $this->codigo,
            'tipo'     => $this->tipo,
            'valor'    => (float)$this->valor,
            'validade' => $this->validade,
            'status'   => $this->status,
            'valido'   => $this->isValido()
        ];
    }

    /**
     * Método responsável por retornar um cupom com base em seu ID
     * @param  integer $id
     * @return Cupom|false
     */
    public static function getCupomById($id){
        return self::getCupons('id = ?', null, null, '*', [(int)$id])->fetchObject(self::class);
    }

    /**
     * Método responsável por retornar um cupom com base em seu código
     * @param  string $codigo
     * @return Cupom|false
     */
    public static function getCupomByCodigo($codigo){
        return self::getCupons('codigo = ?', null, null, '*', [mb_strtoupper(trim((string)$codigo))])->fetchObject(self::class);
    }

    /**
     * Método responsável por retornar cupons
     * @param  string $where
     * @param  string $order
     * @param  string $limit
     * @param  string $fields
     * @param  array  $params
     * @return PDOStatement
     */
    public static function getCupons($where = null, $order = null, $limit = null, $fields = '*', $params = []){
        return (new Database('cupons'))->select($where, $order, $limit, $fields, $params);
    }
}
