<?php

namespace App\Exception;

use \Exception;

class SistemaException extends Exception{

    /**
     * Código HTTP padrão da exceção (sobrescrito pelas classes filhas)
     * @var integer
     */
    protected const CODIGO_PADRAO = 400;

    /**
     * Campo do formulário relacionado ao erro (quando houver)
     * @var string|null
     */
    protected $campo = null;

    /**
     * Método responsável por construir a exceção
     * (SOBRECARGA: código e campo são opcionais; sem código, usa o padrão da classe filha)
     * @param string       $mensagem
     * @param integer|null $codigo
     * @param string|null  $campo
     */
    public function __construct($mensagem = 'Não foi possível concluir a operação.', $codigo = null, $campo = null){
        parent::__construct($mensagem, $codigo ?? static::CODIGO_PADRAO);
        $this->campo = $campo;
    }

    /**
     * Método responsável por retornar o campo relacionado ao erro
     * @return string|null
     */
    public function getCampo(){
        return $this->campo;
    }
}
