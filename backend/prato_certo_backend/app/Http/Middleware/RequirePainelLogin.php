<?php

namespace App\Http\Middleware;

use \App\Session\Painel\Login as SessionPainelLogin;
use \App\Exception\OperacaoNaoPermitidaException;

class RequirePainelLogin{

    /**
     * Método responsável por executar o middleware
     * @param  Request $request
     * @param  Closure $next
     * @return Response
     */
    public function handle($request, $next){
        //BUSCA O USUÁRIO DA SESSÃO
        $obUsuario = SessionPainelLogin::getUsuarioLogado();

        //VERIFICA SE O USUÁRIO ESTÁ LOGADO E ATIVO
        if(!$obUsuario){
            throw new OperacaoNaoPermitidaException("Sua sessão expirou. Faça login novamente.", 401);
        }

        //DISPONIBILIZA O USUÁRIO PARA OS PRÓXIMOS NÍVEIS
        $request->usuario = $obUsuario;

        //CONTINUA A EXECUÇÃO
        return $next($request);
    }
}
