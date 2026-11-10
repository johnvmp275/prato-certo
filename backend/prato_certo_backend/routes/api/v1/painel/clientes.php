<?php

use \App\Http\Response;
use \App\Controller\Api;

//ROTA DE LISTAGEM DE CLIENTES
$obRouter->get('/api/v1/clientes', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:clientes'
     ],
    function($request){
        return new Response(200, Api\Clientes::getClientes($request), 'application/json');
    }
]);

//ROTA DE CONSULTA DE CLIENTE POR ID (COM HISTÓRICO)
$obRouter->get('/api/v1/clientes/{id}', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:clientes'
     ],
    function($request, $id){
        return new Response(200, Api\Clientes::getCliente($request, $id), 'application/json');
    }
]);

//ROTA DE CADASTRO DE CLIENTES
$obRouter->post('/api/v1/clientes', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:clientes'
     ],
    function($request){
        return new Response(201, Api\Clientes::setNovoCliente($request), 'application/json');
    }
]);

//ROTA DE EDIÇÃO DE CLIENTES
$obRouter->put('/api/v1/clientes/{id}', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:clientes'
     ],
    function($request, $id){
        return new Response(200, Api\Clientes::setEditCliente($request, $id), 'application/json');
    }
]);

//ROTA DE INATIVAÇÃO DE CLIENTES
$obRouter->delete('/api/v1/clientes/{id}', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:clientes'
     ],
    function($request, $id){
        return new Response(200, Api\Clientes::setDeleteCliente($request, $id), 'application/json');
    }
]);
