<?php

namespace App\Exception;

class CampoObrigatorioException extends SistemaException{

    /**
     * Código HTTP padrão: entidade não processável
     * @var integer
     */
    protected const CODIGO_PADRAO = 422;

    /**
     * Método responsável por construir a exceção a partir do nome do campo
     * @param string $campo Nome técnico do campo
     * @param string $label Nome exibido para o usuário
     */
    public function __construct($campo, $label = null){
        parent::__construct("O campo '".($label ?? $campo)."' é obrigatório.", null, $campo);
    }
}
