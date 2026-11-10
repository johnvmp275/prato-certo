<?php

//SERVIDOR EMBUTIDO DO PHP (php -S): ENTREGA AS IMAGENS DA PASTA upload DIRETAMENTE
//(NO APACHE O .htaccess JÁ FAZ ISSO; SOMENTE IMAGENS DE upload/ SÃO LIBERADAS)
if (php_sapi_name() === 'cli-server') {
    $arquivo = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (preg_match('/^\/upload\/[a-z]+\/[A-Za-z0-9]+\.(jpg|png|webp)$/', $arquivo) && is_file(__DIR__.$arquivo)) {
        return false;
    }
}

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit;
}

require __DIR__.'/incluides/app.php';

use \App\Http\Router;

//INICIA O ROUTER
$obRouter =  new Router(URL);

//INCLUI AS ROTAS DA API
include __DIR__.'/routes/api.php';

//IMPRIME O RESPONSE NA PÁGINA
$obRouter->run()
         ->sendResponse();
