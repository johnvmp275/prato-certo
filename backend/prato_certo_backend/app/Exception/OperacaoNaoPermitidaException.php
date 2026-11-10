<?php

namespace App\Exception;

class OperacaoNaoPermitidaException extends SistemaException{

    /**
     * Código HTTP padrão: acesso proibido
     * @var integer
     */
    protected const CODIGO_PADRAO = 403;
}
