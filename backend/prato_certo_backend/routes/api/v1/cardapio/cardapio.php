<?php

use \App\Http\Response;
use \App\Controller\Api;

//ROTA PÚBLICA DO CARDÁPIO ONLINE
$obRouter->get('/api/v1/cardapio', [
    function($request){
        return new Response(200, Api\Cardapio::getCardapio($request), 'application/json');
    }
]);

//ROTA PÚBLICA DE ENVIO DE PEDIDO ONLINE
$obRouter->post('/api/v1/cardapio/pedidos', [
    function($request){
        return new Response(201, Api\Cardapio::setPedido($request), 'application/json');
    }
]);

//ROTA PÚBLICA DE VALIDAÇÃO DE CUPOM NO CARRINHO
$obRouter->post('/api/v1/cardapio/cupom', [
    function($request){
        return new Response(200, Api\Cardapio::setValidarCupom($request), 'application/json');
    }
]);

//ROTA PÚBLICA DO PIX DO PEDIDO ONLINE (CHAVE, COPIA E COLA E QR CODE)
$obRouter->get('/api/v1/cardapio/pedidos/{codigo}/pix', [
    function($request, $codigo){
        return new Response(200, Api\Cardapio::getPix($request, $codigo), 'application/json');
    }
]);

//ROTA PÚBLICA DE ACOMPANHAMENTO DO PEDIDO ONLINE
$obRouter->get('/api/v1/cardapio/pedidos/{codigo}', [
    function($request, $codigo){
        return new Response(200, Api\Cardapio::getPedido($request, $codigo), 'application/json');
    }
]);
