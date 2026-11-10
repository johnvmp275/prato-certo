<?php

namespace App\Model\Entity;

class PagamentoDinheiro extends Pagamento{

    /**
     * Forma de pagamento
     * @var string
     */
    public $forma = 'dinheiro';

    /**
     * Método responsável por processar o pagamento em dinheiro (aceita valor maior e calcula o troco)
     * @param float $restante
     */
    public function processar($restante){
        //VALIDA O VALOR ENTREGUE PELO CLIENTE
        $this->validarValor();

        //VALOR ENTREGUE
        $this->valorRecebido = $this->valor;

        //ABATE NO PEDIDO NO MÁXIMO O VALOR RESTANTE
        $this->valor = round(min($this->valorRecebido, $restante), 2);

        //CALCULA O TROCO (RN05: SOMENTE DINHEIRO GERA TROCO)
        $this->troco = round(max(0, $this->valorRecebido - $restante), 2);
    }

    /**
     * Método responsável por retornar a descrição da forma de pagamento
     * @return string
     */
    public function getDescricao(){
        return 'Dinheiro';
    }
}
