<?php

namespace App\Controller\Api;

use \App\Model\Entity\Usuario as EntityUsuario;
use \App\Session\Painel\Login as SessionPainelLogin;
use \App\Utils\Validador;
use \App\Exception\DadoInvalidoException;
use \App\Exception\OperacaoNaoPermitidaException;

class Auth extends Api{

   /**
    * Método responsável por realizar o login do usuário no painel
    * @param  Request $request
    * @return array
    */
    public static function setLogin($request){
        //POST VARS
        $postVars = $request->getPostVars();
        $email    = Validador::obrigatorio($postVars['email'] ?? '', 'email', 'E-mail');
        $senha    = Validador::obrigatorio($postVars['senha'] ?? '', 'senha', 'Senha');

        //BUSCA O USUÁRIO PELO E-MAIL
        $obUsuario = EntityUsuario::getUsuarioByEmail(mb_strtolower(trim($email)));

        //VALIDA A INSTÂNCIA E A SENHA (MESMA MENSAGEM PARA NÃO REVELAR QUAL DADO ESTÁ ERRADO)
        if(!$obUsuario instanceof EntityUsuario or !$obUsuario->verificarSenha($senha)){
            throw new DadoInvalidoException("E-mail ou senha inválidos.", 401);
        }

        //VALIDA SE O USUÁRIO ESTÁ ATIVO
        if($obUsuario->status != 'ativo'){
            throw new OperacaoNaoPermitidaException("Este usuário está inativo. Procure o administrador.");
        }

        //CRIA A SESSÃO DE LOGIN
        SessionPainelLogin::login($obUsuario);

        //RETORNA OS DADOS DO USUÁRIO LOGADO
        return $obUsuario->toArray();
    }

   /**
    * Método responsável por retornar o usuário logado
    * @param  Request $request
    * @return array
    */
    public static function getUsuarioLogado($request){
        return $request->usuario->toArray();
    }

   /**
    * Método responsável por realizar o logout do usuário
    * @param  Request $request
    * @return array
    */
    public static function setLogout($request){
        //DESLOGA O USUÁRIO
        SessionPainelLogin::logout();

        return [
            'sucesso' => true
        ];
    }
}
