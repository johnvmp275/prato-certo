<?php

use \App\Http\Response;
use \App\Controller\Api;

//ROTA PÚBLICA DE CADASTRO DO CLIENTE
$obRouter->post('/api/v1/cardapio/conta/cadastro', [
    function($request){
        return new Response(201, Api\Conta::setCadastro($request), 'application/json');
    }
]);

//ROTA PÚBLICA DE LOGIN DO CLIENTE
$obRouter->post('/api/v1/cardapio/conta/login', [
    function($request){
        return new Response(200, Api\Conta::setLogin($request), 'application/json');
    }
]);

//ROTA DE LOGOUT DO CLIENTE
$obRouter->post('/api/v1/cardapio/conta/logout', [
    function($request){
        return new Response(200, Api\Conta::setLogout($request), 'application/json');
    }
]);

//ROTA DE CONSULTA DO CLIENTE LOGADO
$obRouter->get('/api/v1/cardapio/conta', [
    'middlewares' => [
        'require-cliente-login'
     ],
    function($request){
        return new Response(200, Api\Conta::getConta($request), 'application/json');
    }
]);

//ROTA DE EDIÇÃO DOS DADOS DO CLIENTE LOGADO
$obRouter->put('/api/v1/cardapio/conta', [
    'middlewares' => [
        'require-cliente-login'
     ],
    function($request){
        return new Response(200, Api\Conta::setEditConta($request), 'application/json');
    }
]);

//ROTA DOS PEDIDOS DO CLIENTE LOGADO
$obRouter->get('/api/v1/cardapio/conta/pedidos', [
    'middlewares' => [
        'require-cliente-login'
     ],
    function($request){
        return new Response(200, Api\Conta::getPedidos($request), 'application/json');
    }
]);
