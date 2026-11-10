<?php

namespace App\Session\Painel;

use \App\Model\Entity\Usuario;

class Login{

    /**
     * Método responsável por iniciar uma sessão
     */
    private static function init(){
        //VERIFICA SE A SESSÃO NÃO ESTÁ ATIVA
        if(session_status() != PHP_SESSION_ACTIVE){
            session_start();
        }
    }

    /**
     * Método responsável por criar o login do usuário
     * @param  Usuario $obUsuario
     * @return boolean
     */
    public static function login($obUsuario){
        //INICIA A SESSÃO
        self::init();

        //RENOVA O ID DA SESSÃO (EVITA SEQUESTRO DE SESSÃO)
        session_regenerate_id(true);

        //DEFINE A SESSÃO DO USUÁRIO
        $_SESSION['painel']['usuario'] = [
            'id'     => $obUsuario->id,
            'nome'   => $obUsuario->nome,
            'email'  => $obUsuario->email,
            'perfil' => $obUsuario->perfil,
        ];

        //SUCESSO
        return true;
    }

    /**
     * Método responsável por verificar se o usuário está logado
     * @return boolean
     */
    public static function isLogged(){
        //INICIA A SESSÃO
        self::init();

        //RETORNA A VERIFICAÇÃO
        return isset($_SESSION['painel']['usuario']['id']);
    }

    /**
     * Método responsável por retornar o usuário logado (recarregado do banco)
     * @return Usuario|null
     */
    public static function getUsuarioLogado(){
        //VERIFICA SE ESTÁ LOGADO
        if(!self::isLogged()) return null;

        //BUSCA O USUÁRIO ATUALIZADO NO BANCO
        $obUsuario = Usuario::getUsuarioById($_SESSION['painel']['usuario']['id']);

        //SE O USUÁRIO FOI INATIVADO, ENCERRA A SESSÃO
        if(!$obUsuario instanceof Usuario or $obUsuario->status != 'ativo'){
            self::logout();
            return null;
        }

        return $obUsuario;
    }

    /**
     * Método responsável por executar o logout do usuário
     * @return boolean
     */
    public static function logout(){
        //INICIA A SESSÃO
        self::init();

        //DESLOGA O USUÁRIO
        unset($_SESSION['painel']['usuario']);

        //SUCESSO
        return true;
    }

}
