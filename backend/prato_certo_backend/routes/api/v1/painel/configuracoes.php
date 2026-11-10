<?php

use \App\Http\Response;
use \App\Controller\Api;

//ROTA DE CONSULTA DAS CONFIGURAÇÕES
$obRouter->get('/api/v1/configuracoes', [
    'middlewares' => [
        'require-painel-login'
     ],
    function($request){
        return new Response(200, Api\Configuracoes::getConfiguracoes($request), 'application/json');
    }
]);

//ROTA DE ATUALIZAÇÃO DAS CONFIGURAÇÕES
$obRouter->put('/api/v1/configuracoes', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:configuracoes'
     ],
    function($request){
        return new Response(200, Api\Configuracoes::setConfiguracoes($request), 'application/json');
    }
]);
