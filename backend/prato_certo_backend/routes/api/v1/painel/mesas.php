<?php

use \App\Http\Response;
use \App\Controller\Api;

//ROTA DO MAPA DE MESAS
$obRouter->get('/api/v1/mesas', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:mesas'
     ],
    function($request){
        return new Response(200, Api\Mesas::getMesas($request), 'application/json');
    }
]);

//ROTA DE CADASTRO DE MESAS
$obRouter->post('/api/v1/mesas', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:mesas.gerenciar'
     ],
    function($request){
        return new Response(201, Api\Mesas::setNovaMesa($request), 'application/json');
    }
]);

//ROTA DE EDIÇÃO DE MESAS
$obRouter->put('/api/v1/mesas/{id}', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:mesas.gerenciar'
     ],
    function($request, $id){
        return new Response(200, Api\Mesas::setEditMesa($request, $id), 'application/json');
    }
]);

//ROTA DE INATIVAÇÃO DE MESAS
$obRouter->delete('/api/v1/mesas/{id}', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:mesas.gerenciar'
     ],
    function($request, $id){
        return new Response(200, Api\Mesas::setDeleteMesa($request, $id), 'application/json');
    }
]);
