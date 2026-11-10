<?php

namespace App\DotEnv;

class Environment{

  /**
   * Método responsável por carregar as variáveis de ambiente do projeto
   * @param  string $dir Caminho absoluto da pasta onde encontra-se o arquivo .env
   */
  public static function load($dir){
    //VERIFICA SE O ARQUIVO .ENV EXISTE
    if(!file_exists($dir.'/.env')){
      return false;
    }

    //DEFINE AS VARIÁVEIS DE AMBIENTE
    $lines = file($dir.'/.env');
    foreach($lines as $line){
      //IGNORA LINHAS VAZIAS E COMENTÁRIOS
      $line = trim($line);
      if(!strlen($line) or str_starts_with($line, '#')) continue;

      //NÃO SOBRESCREVE VARIÁVEIS JÁ DEFINIDAS (EX: DOCKER)
      $key = explode('=', $line, 2)[0];
      if(getenv($key) !== false) continue;

      putenv($line);
    }
  }

}
