<?php

use \App\Http\Response;
use \App\Controller\Api;

//ROTA DE LISTAGEM DE CATEGORIAS
$obRouter->get('/api/v1/categorias', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:produtos'
     ],
    function($request){
        return new Response(200, Api\Categorias::getCategorias($request), 'application/json');
    }
]);

//ROTA DE CADASTRO DE CATEGORIAS
$obRouter->post('/api/v1/categorias', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:produtos.gerenciar'
     ],
    function($request){
        return new Response(201, Api\Categorias::setNovaCategoria($request), 'application/json');
    }
]);

//ROTA DE ORDENAÇÃO DAS CATEGORIAS (ARRASTAR E SOLTAR)
//PRECISA VIR ANTES DE /categorias/{id}: O ROUTER USA A PRIMEIRA ROTA QUE COMBINA COM A URL
$obRouter->put('/api/v1/categorias/ordem', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:produtos.gerenciar'
     ],
    function($request){
        return new Response(200, Api\Categorias::setOrdem($request), 'application/json');
    }
]);

//ROTA DE EDIÇÃO DE CATEGORIAS
$obRouter->put('/api/v1/categorias/{id}', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:produtos.gerenciar'
     ],
    function($request, $id){
        return new Response(200, Api\Categorias::setEditCategoria($request, $id), 'application/json');
    }
]);

//ROTA DE INATIVAÇÃO DE CATEGORIAS
$obRouter->delete('/api/v1/categorias/{id}', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:produtos.gerenciar'
     ],
    function($request, $id){
        return new Response(200, Api\Categorias::setDeleteCategoria($request, $id), 'application/json');
    }
]);
