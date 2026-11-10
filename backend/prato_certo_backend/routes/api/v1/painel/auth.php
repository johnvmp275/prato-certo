<?php

use \App\Http\Response;
use \App\Controller\Api;

//ROTA DE LOGIN DO PAINEL
$obRouter->post('/api/v1/auth/login', [
    function($request){
        return new Response(200, Api\Auth::setLogin($request), 'application/json');
    }
]);

//ROTA DE CONSULTA DO USUÁRIO LOGADO
$obRouter->get('/api/v1/auth/me', [
    'middlewares' => [
        'require-painel-login'
     ],
    function($request){
        return new Response(200, Api\Auth::getUsuarioLogado($request), 'application/json');
    }
]);

//ROTA DE LOGOUT DO PAINEL
$obRouter->post('/api/v1/auth/logout', [
    function($request){
        return new Response(200, Api\Auth::setLogout($request), 'application/json');
    }
]);
