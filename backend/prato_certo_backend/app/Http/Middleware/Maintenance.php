<?php

namespace App\Http\Middleware;

use \App\Exception\SistemaException;

class Maintenance{

    /**
     * Método responsável por executar o middleware
     * @param  Request $request
     * @param  Closure $next
     * @return Response
     */
    public function handle($request, $next){
        //VERIFICA O ESTADO DE MANUTENÇÃO DA PÁGINA
        if(getenv('MAINTENANCE') == 'true'){
            throw new SistemaException("Sistema em manutenção. Tente novamente mais tarde.", 503);
        }

        //EXECUTA O PRÓXIMO NÍVEL DE MIDDLEWARE
        return $next($request);
    }
}
