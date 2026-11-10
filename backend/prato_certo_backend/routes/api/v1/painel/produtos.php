<?php

use \App\Http\Response;
use \App\Controller\Api;

//ROTA DE LISTAGEM DE PRODUTOS
$obRouter->get('/api/v1/produtos', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:produtos'
     ],
    function($request){
        return new Response(200, Api\Produtos::getProdutos($request), 'application/json');
    }
]);

//ROTA DE CONSULTA DE PRODUTO POR ID
$obRouter->get('/api/v1/produtos/{id}', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:produtos'
     ],
    function($request, $id){
        return new Response(200, Api\Produtos::getProduto($request, $id), 'application/json');
    }
]);

//ROTA DE CADASTRO DE PRODUTOS
$obRouter->post('/api/v1/produtos', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:produtos.gerenciar'
     ],
    function($request){
        return new Response(201, Api\Produtos::setNovoProduto($request), 'application/json');
    }
]);

//ROTA DE EDIÇÃO DE PRODUTOS
$obRouter->put('/api/v1/produtos/{id}', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:produtos.gerenciar'
     ],
    function($request, $id){
        return new Response(200, Api\Produtos::setEditProduto($request, $id), 'application/json');
    }
]);

//ROTA DE INATIVAÇÃO DE PRODUTOS
$obRouter->delete('/api/v1/produtos/{id}', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:produtos.gerenciar'
     ],
    function($request, $id){
        return new Response(200, Api\Produtos::setDeleteProduto($request, $id), 'application/json');
    }
]);
