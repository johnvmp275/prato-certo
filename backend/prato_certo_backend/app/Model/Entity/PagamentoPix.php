<?php

namespace App\Model\Entity;

class PagamentoPix extends Pagamento{

    /**
     * Forma de pagamento
     * @var string
     */
    public $forma = 'pix';

    /**
     * Código da transação PIX
     * @var string
     */
    public $codigoTransacao;

    /**
     * Método responsável por processar o pagamento via PIX (não pode ultrapassar o restante)
     * @param float $restante
     */
    public function processar($restante){
        //VALIDA O VALOR
        $this->validarValor();

        //PIX NÃO GERA TROCO
        $this->validarLimite($restante);

        //GERA O CÓDIGO DA TRANSAÇÃO
        $this->codigoTransacao = 'PIX'.strtoupper(bin2hex(random_bytes(8)));
        $this->valorRecebido   = $this->valor;
        $this->troco           = 0;
    }

    /**
     * Método responsável por retornar a descrição da forma de pagamento
     * @return string
     */
    public function getDescricao(){
        return 'PIX';
    }

    /**
     * Método responsável por retornar os dados específicos do PIX
     * @return array
     */
    protected function getDadosEspecificos(){
        return [
            'codigoTransacao' => $this->codigoTransacao
        ];
    }
}
