<?php

use \App\Http\Response;
use \App\Controller\Api;

//ROTA DE LISTAGEM DE CUPONS
$obRouter->get('/api/v1/cupons', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:cupons'
     ],
    function($request){
        return new Response(200, Api\Cupons::getCupons($request), 'application/json');
    }
]);

//ROTA DE CADASTRO DE CUPONS
$obRouter->post('/api/v1/cupons', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:cupons'
     ],
    function($request){
        return new Response(201, Api\Cupons::setNovoCupom($request), 'application/json');
    }
]);

//ROTA DE EDIÇÃO DE CUPONS
$obRouter->put('/api/v1/cupons/{id}', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:cupons'
     ],
    function($request, $id){
        return new Response(200, Api\Cupons::setEditCupom($request, $id), 'application/json');
    }
]);

//ROTA DE INATIVAÇÃO DE CUPONS
$obRouter->delete('/api/v1/cupons/{id}', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:cupons'
     ],
    function($request, $id){
        return new Response(200, Api\Cupons::setDeleteCupom($request, $id), 'application/json');
    }
]);
