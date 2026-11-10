<?php

namespace App\Exception;

class FalhaPersistenciaException extends SistemaException{

    /**
     * Código HTTP padrão: erro interno
     * @var integer
     */
    protected const CODIGO_PADRAO = 500;
}
