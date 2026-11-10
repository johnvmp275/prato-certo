<?php

namespace App\Http\Middleware;

use \App\Session\Cardapio\Login as SessionCardapioLogin;
use \App\Exception\OperacaoNaoPermitidaException;

class RequireClienteLogin{

    /**
     * Método responsável por executar o middleware
     * @param  Request $request
     * @param  Closure $next
     * @return Response
     */
    public function handle($request, $next){
        //BUSCA O CLIENTE DA SESSÃO
        $obCliente = SessionCardapioLogin::getClienteLogado();

        //VERIFICA SE O CLIENTE ESTÁ LOGADO E ATIVO
        if(!$obCliente){
            throw new OperacaoNaoPermitidaException("Entre na sua conta para continuar.", 401);
        }

        //DISPONIBILIZA O CLIENTE PARA OS PRÓXIMOS NÍVEIS
        $request->cliente = $obCliente;

        //CONTINUA A EXECUÇÃO
        return $next($request);
    }
}
