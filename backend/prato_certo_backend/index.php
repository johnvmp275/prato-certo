<?php 

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
