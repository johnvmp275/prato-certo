<?php

namespace App\Session\Cardapio;

use \App\Model\Entity\Cliente;

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
     * Método responsável por criar o login do cliente no cardápio online
     * (fica separado da sessão do painel: $_SESSION['cardapio'])
     * @param  Cliente $obCliente
     * @return boolean
     */
    public static function login($obCliente){
        //INICIA A SESSÃO
        self::init();

        //RENOVA O ID DA SESSÃO (EVITA SEQUESTRO DE SESSÃO)
        session_regenerate_id(true);

        //DEFINE A SESSÃO DO CLIENTE
        $_SESSION['cardapio']['cliente'] = [
            'id'   => $obCliente->id,
            'nome' => $obCliente->nome,
        ];

        //SUCESSO
        return true;
    }

    /**
     * Método responsável por verificar se o cliente está logado
     * @return boolean
     */
    public static function isLogged(){
        //INICIA A SESSÃO
        self::init();

        //RETORNA A VERIFICAÇÃO
        return isset($_SESSION['cardapio']['cliente']['id']);
    }

    /**
     * Método responsável por retornar o cliente logado (recarregado do banco)
     * @return Cliente|null
     */
    public static function getClienteLogado(){
        //VERIFICA SE ESTÁ LOGADO
        if(!self::isLogged()) return null;

        //BUSCA O CLIENTE ATUALIZADO NO BANCO
        $obCliente = Cliente::getClienteById($_SESSION['cardapio']['cliente']['id']);

        //SE O CLIENTE FOI INATIVADO PELO PAINEL, ENCERRA A SESSÃO
        if(!$obCliente instanceof Cliente or $obCliente->status != 'ativo' or !$obCliente->possuiConta()){
            self::logout();
            return null;
        }

        return $obCliente;
    }

    /**
     * Método responsável por executar o logout do cliente
     * @return boolean
     */
    public static function logout(){
        //INICIA A SESSÃO
        self::init();

        //DESLOGA O CLIENTE
        unset($_SESSION['cardapio']['cliente']);

        //SUCESSO
        return true;
    }

}
