<?php

namespace App\Exception;

class EstadoInvalidoException extends SistemaException{

    /**
     * Código HTTP padrão: conflito com o estado atual do registro
     * @var integer
     */
    protected const CODIGO_PADRAO = 409;
}
