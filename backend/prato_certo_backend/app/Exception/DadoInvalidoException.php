<?php

namespace App\Exception;

class DadoInvalidoException extends SistemaException{

    /**
     * Código HTTP padrão: entidade não processável
     * @var integer
     */
    protected const CODIGO_PADRAO = 422;
}
