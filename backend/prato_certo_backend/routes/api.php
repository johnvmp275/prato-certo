<?php

//INCLUI A ROTA PADRÃO DA API
include __DIR__.'/api/v1/default.php';

//INCLUI AS ROTAS DE AUTENTICAÇÃO DO PAINEL
include __DIR__.'/api/v1/painel/auth.php';

//INCLUI AS ROTAS DO DASHBOARD
include __DIR__.'/api/v1/painel/dashboard.php';

//INCLUI AS ROTAS DE USUÁRIOS
include __DIR__.'/api/v1/painel/usuarios.php';

//INCLUI AS ROTAS DE CATEGORIAS
include __DIR__.'/api/v1/painel/categorias.php';

//INCLUI AS ROTAS DE PRODUTOS
include __DIR__.'/api/v1/painel/produtos.php';

//INCLUI AS ROTAS DE CLIENTES
include __DIR__.'/api/v1/painel/clientes.php';

//INCLUI AS ROTAS DE MESAS
include __DIR__.'/api/v1/painel/mesas.php';

//INCLUI AS ROTAS DE CUPONS
include __DIR__.'/api/v1/painel/cupons.php';

//INCLUI AS ROTAS DE PEDIDOS (PROCESSO PRINCIPAL)
include __DIR__.'/api/v1/painel/pedidos.php';

//INCLUI AS ROTAS DE CONFIGURAÇÕES
include __DIR__.'/api/v1/painel/configuracoes.php';

//INCLUI AS ROTAS PÚBLICAS DO CARDÁPIO ONLINE
include __DIR__.'/api/v1/cardapio/cardapio.php';
