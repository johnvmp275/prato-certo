<?php

use \App\Http\Response;
use \App\Controller\Api;

//ROTA DO RESUMO DO DIA
$obRouter->get('/api/v1/dashboard', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:dashboard'
     ],
    function($request){
        return new Response(200, Api\Dashboard::getResumo($request), 'application/json');
    }
]);
