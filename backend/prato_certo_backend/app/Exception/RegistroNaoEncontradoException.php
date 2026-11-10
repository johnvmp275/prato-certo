<?php

namespace App\Exception;

class RegistroNaoEncontradoException extends SistemaException{

    /**
     * Código HTTP padrão: não encontrado
     * @var integer
     */
    protected const CODIGO_PADRAO = 404;
}
