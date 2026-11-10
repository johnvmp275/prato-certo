<?php

use \App\Http\Response;
use \App\Controller\Api;

//ROTA DE LISTAGEM DE USUÁRIOS
$obRouter->get('/api/v1/usuarios', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:usuarios'
     ],
    function($request){
        return new Response(200, Api\Usuarios::getUsuarios($request), 'application/json');
    }
]);

//ROTA DE CONSULTA DE USUÁRIO POR ID
$obRouter->get('/api/v1/usuarios/{id}', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:usuarios'
     ],
    function($request, $id){
        return new Response(200, Api\Usuarios::getUsuario($request, $id), 'application/json');
    }
]);

//ROTA DE CADASTRO DE USUÁRIOS
$obRouter->post('/api/v1/usuarios', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:usuarios'
     ],
    function($request){
        return new Response(201, Api\Usuarios::setNovoUsuario($request), 'application/json');
    }
]);

//ROTA DE EDIÇÃO DE USUÁRIOS
$obRouter->put('/api/v1/usuarios/{id}', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:usuarios'
     ],
    function($request, $id){
        return new Response(200, Api\Usuarios::setEditUsuario($request, $id), 'application/json');
    }
]);

//ROTA DE INATIVAÇÃO DE USUÁRIOS
$obRouter->delete('/api/v1/usuarios/{id}', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:usuarios'
     ],
    function($request, $id){
        return new Response(200, Api\Usuarios::setDeleteUsuario($request, $id), 'application/json');
    }
]);
