<?php

namespace App\Model\Entity;

use \App\Utils\Validador;

class PagamentoCartao extends Pagamento{

    /**
     * Forma de pagamento
     * @var string
     */
    public $forma = 'cartao';

    /**
     * Bandeira do cartão (Visa, Master, Elo...)
     * @var string|null
     */
    public $bandeira;

    /**
     * Modalidade do cartão (credito | debito)
     * @var string
     */
    public $modalidade;

    /**
     * Método responsável por processar o pagamento no cartão (não pode ultrapassar o restante)
     * @param float $restante
     */
    public function processar($restante){
        //VALIDA O VALOR
        $this->validarValor();

        //VALIDA A MODALIDADE
        Validador::opcao($this->modalidade, ['credito', 'debito'], 'modalidade');

        //CARTÃO NÃO GERA TROCO
        $this->validarLimite($restante);

        $this->bandeira      = $this->bandeira ? mb_substr(trim($this->bandeira), 0, 30) : null;
        $this->valorRecebido = $this->valor;
        $this->troco         = 0;
    }

    /**
     * Método responsável por retornar a descrição da forma de pagamento
     * @return string
     */
    public function getDescricao(){
        return $this->modalidade == 'debito' ? 'Cartão de débito' : 'Cartão de crédito';
    }

    /**
     * Método responsável por retornar os dados específicos do cartão
     * @return array
     */
    protected function getDadosEspecificos(){
        return [
            'bandeira'   => $this->bandeira,
            'modalidade' => $this->modalidade
        ];
    }
}
