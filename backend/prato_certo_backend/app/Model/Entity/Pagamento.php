<?php

namespace App\Model\Entity;

use \App\DatabaseManager\Database;
use \App\Exception\DadoInvalidoException;

abstract class Pagamento{

    /**
     * Id do pagamento
     * @var integer
     */
    public $id;

    /**
     * Id do pedido pago (composição)
     * @var integer
     */
    public $pedidoId;

    /**
     * Forma de pagamento (dinheiro | cartao | pix)
     * @var string
     */
    public $forma;

    /**
     * Valor abatido do pedido
     * @var float
     */
    public $valor = 0;

    /**
     * Valor entregue pelo cliente
     * @var float
     */
    public $valorRecebido = 0;

    /**
     * Troco devolvido ao cliente
     * @var float
     */
    public $troco = 0;

    /**
     * Data do pagamento
     * @var string
     */
    public $criadoEm;

    /**
     * Método responsável por validar e processar o pagamento sobre o valor restante do pedido
     * (POLIMORFISMO: cada forma de pagamento processa de um jeito)
     * @param float $restante
     */
    abstract public function processar($restante);

    /**
     * Método responsável por retornar a descrição da forma de pagamento
     * @return string
     */
    abstract public function getDescricao();

    /**
     * Método responsável por validar se o valor informado é maior que zero
     */
    protected function validarValor(){
        //ACEITA VÍRGULA COMO SEPARADOR DECIMAL
        $valor = is_string($this->valor) ? str_replace(',', '.', $this->valor) : $this->valor;

        if(!is_numeric($valor) or $valor <= 0){
            throw new DadoInvalidoException("Informe um valor de pagamento maior que zero.", null, 'valor');
        }

        $this->valor = round((float)$valor, 2);
    }

    /**
     * Método responsável por impedir que o valor ultrapasse o restante (formas sem troco)
     * @param float $restante
     */
    protected function validarLimite($restante){
        if($this->valor > round($restante, 2)){
            throw new DadoInvalidoException("O valor no ".$this->getDescricao()." não pode ser maior que o restante (R$ ".number_format($restante, 2, ',', '.').").", null, 'valor');
        }
    }

    /**
     * Método responsável por retornar os dados específicos da forma de pagamento para persistência
     * @return array
     */
    protected function getDadosEspecificos(){
        return [];
    }

    /**
     * Método responsável por cadastrar a instância atual no banco de dados
     * @return boolean
     */
    public function cadastrar(){
        //DATA DO PAGAMENTO
        $this->criadoEm = date('Y-m-d H:i:s');

        //INSERE O PAGAMENTO NO BANCO DE DADOS
        $this->id = (new Database('pagamentos'))->insert(array_merge([
            'pedidoId'      => $this->pedidoId,
            'forma'         => $this->forma,
            'valor'         => $this->valor,
            'valorRecebido' => $this->valorRecebido,
            'troco'         => $this->troco,
            'criadoEm'      => $this->criadoEm
        ], $this->getDadosEspecificos()));

        //SUCESSO
        return true;
    }

    /**
     * Método responsável por retornar os dados do pagamento em array
     * @return array
     */
    public function toArray(){
        return array_merge([
            'id'            => (int)$this->id,
            'forma'         => $this->forma,
            'descricao'     => $this->getDescricao(),
            'valor'         => (float)$this->valor,
            'valorRecebido' => (float)$this->valorRecebido,
            'troco'         => (float)$this->troco,
            'criadoEm'      => $this->criadoEm
        ], $this->getDadosEspecificos());
    }

    /**
     * Método responsável por instanciar a subclasse correta a partir da forma (FÁBRICA)
     * @param  array $dados
     * @return Pagamento
     */
    public static function instanciar($dados){
        //ESCOLHE A CLASSE PELA FORMA DE PAGAMENTO
        switch($dados['forma'] ?? ''){
            case 'dinheiro': $obPagamento = new PagamentoDinheiro; break;
            case 'cartao':   $obPagamento = new PagamentoCartao;   break;
            case 'pix':      $obPagamento = new PagamentoPix;      break;
            default:
                throw new DadoInvalidoException("Forma de pagamento inválida. Opções: dinheiro, cartao, pix.", null, 'forma');
        }

        //PREENCHE OS ATRIBUTOS
        foreach($dados as $atributo => $valor){
            if(property_exists($obPagamento, $atributo)) $obPagamento->$atributo = $valor;
        }

        return $obPagamento;
    }

    /**
     * Método responsável por retornar os pagamentos de um pedido
     * @param  integer $pedidoId
     * @return Pagamento[]
     */
    public static function getPagamentosByPedido($pedidoId){
        //PAGAMENTOS
        $pagamentos = [];

        //BUSCA OS PAGAMENTOS NO BANCO
        $results = (new Database('pagamentos'))->select('pedidoId = ?', 'id ASC', null, '*', [(int)$pedidoId]);
        while($dados = $results->fetch(\PDO::FETCH_ASSOC)){
            $pagamentos[] = self::instanciar($dados);
        }

        return $pagamentos;
    }
}
