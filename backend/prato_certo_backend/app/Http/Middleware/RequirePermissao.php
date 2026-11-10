<?php

namespace App\Http\Middleware;

use \App\Exception\OperacaoNaoPermitidaException;

class RequirePermissao{

    /**
     * Método responsável por executar o middleware
     * (deve ser usado sempre depois do require-painel-login)
     * @param  Request $request
     * @param  Closure $next
     * @param  string  $recurso Recurso exigido pela rota (ex: produtos.gerenciar)
     * @return Response
     */
    public function handle($request, $next, $recurso = null){
        //VERIFICA SE O USUÁRIO FOI CARREGADO
        if(!$request->usuario){
            throw new OperacaoNaoPermitidaException("Sua sessão expirou. Faça login novamente.", 401);
        }

        //VERIFICA A PERMISSÃO (POLIMORFISMO: CADA PERFIL RESPONDE COM SUAS PRÓPRIAS PERMISSÕES)
        if($recurso and !$request->usuario->podeAcessar($recurso)){
            throw new OperacaoNaoPermitidaException("Você não tem permissão para realizar esta ação.");
        }

        //CONTINUA A EXECUÇÃO
        return $next($request);
    }
}
