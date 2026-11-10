<?php

    require __DIR__.'/../vendor/autoload.php';

    use \App\DotEnv\Environment;
    use \App\DatabaseManager\Database;
    use \App\Http\Middleware\Queue as MiddlewareQueue;

    //DEFINE O FUSO HORÁRIO PADRÃO DO SISTEMA
    date_default_timezone_set('America/Sao_Paulo');

    //CARREGA VARIÁVEIS DE AMBIENTE
    Environment::load(__DIR__.'/../');

    //DEFINE AS CONFIGURAÇÕES DE BANCO DE DADOS
    Database::config(
        getenv('DB_HOST'),
        getenv('DB_NAME'),
        getenv('DB_USER'),
        getenv('DB_PASS'),
        getenv('DB_PORT')
    );

    //DEFINE A CONSTANTE URL DO PROJETO
    define('URL', getenv('URL'));
    define('URL_FRONT', getenv('URL_FRONT'));

    //DEFINE O MAPEAMENTO DE MIDDLEWARES
    MiddlewareQueue::setMap([
        'maintenance'          => \App\Http\Middleware\Maintenance::class,
        'api'                  => \App\Http\Middleware\Api::class,
        'require-painel-login' => \App\Http\Middleware\RequirePainelLogin::class,
        'require-permissao'    => \App\Http\Middleware\RequirePermissao::class,
    ]);

    //DEFINE O MAPEAMENTO DE MIDDLEWARES PADRÕES (EXECUTADOS EM TODAS AS ROTAS)
    MiddlewareQueue::setDefault([
        'maintenance',
        'api'
    ]);
