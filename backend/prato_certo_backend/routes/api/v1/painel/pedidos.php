<?php

use \App\Http\Response;
use \App\Controller\Api;

//ROTA DE LISTAGEM DE PEDIDOS
$obRouter->get('/api/v1/pedidos', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:pedidos'
     ],
    function($request){
        return new Response(200, Api\Pedidos::getPedidos($request), 'application/json');
    }
]);

//ROTA DE CONSULTA DE PEDIDO POR ID
$obRouter->get('/api/v1/pedidos/{id}', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:pedidos'
     ],
    function($request, $id){
        return new Response(200, Api\Pedidos::getPedido($request, $id), 'application/json');
    }
]);

//ROTA DE ABERTURA DE PEDIDOS (MESA OU BALCÃO)
$obRouter->post('/api/v1/pedidos', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:pdv'
     ],
    function($request){
        return new Response(201, Api\Pedidos::setNovoPedido($request), 'application/json');
    }
]);

//ROTA DE EDIÇÃO DE PEDIDOS (ITENS, CLIENTE E OBSERVAÇÃO)
$obRouter->put('/api/v1/pedidos/{id}', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:pdv'
     ],
    function($request, $id){
        return new Response(200, Api\Pedidos::setEditPedido($request, $id), 'application/json');
    }
]);

//ROTA PARA COLOCAR O PEDIDO EM ESPERA
$obRouter->post('/api/v1/pedidos/{id}/espera', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:pdv'
     ],
    function($request, $id){
        return new Response(200, Api\Pedidos::setEspera($request, $id), 'application/json');
    }
]);

//ROTA PARA RETOMAR O PEDIDO EM ESPERA
$obRouter->post('/api/v1/pedidos/{id}/retomar', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:pdv'
     ],
    function($request, $id){
        return new Response(200, Api\Pedidos::setRetomar($request, $id), 'application/json');
    }
]);

//ROTA PARA ACEITAR UM PEDIDO ONLINE
$obRouter->post('/api/v1/pedidos/{id}/aceitar', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:pedidos'
     ],
    function($request, $id){
        return new Response(200, Api\Pedidos::setAceitar($request, $id), 'application/json');
    }
]);

//ROTA PARA APLICAR DESCONTO
$obRouter->post('/api/v1/pedidos/{id}/desconto', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:pdv'
     ],
    function($request, $id){
        return new Response(200, Api\Pedidos::setDesconto($request, $id), 'application/json');
    }
]);

//ROTA PARA REMOVER DESCONTO
$obRouter->delete('/api/v1/pedidos/{id}/desconto', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:pdv'
     ],
    function($request, $id){
        return new Response(200, Api\Pedidos::deleteDesconto($request, $id), 'application/json');
    }
]);

//ROTA PARA REGISTRAR PAGAMENTO
$obRouter->post('/api/v1/pedidos/{id}/pagamentos', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:pdv'
     ],
    function($request, $id){
        return new Response(200, Api\Pedidos::setPagamento($request, $id), 'application/json');
    }
]);

//ROTA PARA FINALIZAR O PEDIDO
$obRouter->post('/api/v1/pedidos/{id}/finalizar', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:pdv'
     ],
    function($request, $id){
        return new Response(200, Api\Pedidos::setFinalizar($request, $id), 'application/json');
    }
]);

//ROTA PARA CANCELAR O PEDIDO (SOMENTE ADMINISTRADOR)
$obRouter->post('/api/v1/pedidos/{id}/cancelar', [
    'middlewares' => [
        'require-painel-login',
        'require-permissao:pedidos.cancelar'
     ],
    function($request, $id){
        return new Response(200, Api\Pedidos::setCancelar($request, $id), 'application/json');
    }
]);
