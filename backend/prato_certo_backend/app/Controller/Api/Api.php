<?php

namespace App\Controller\Api;

use \Throwable;
use \App\DatabaseManager\Database;
use \App\DatabaseManager\Pagination;
use \App\Model\Entity\Cliente as EntityCliente;
use \App\Exception\DadoInvalidoException;
use \App\Exception\RegistroNaoEncontradoException;

class Api{

   /**
    * Método responsável por retornar os detalhes da API
    * @param Request $request
    * @return array
    */
    public static function getDetails($request){
        return [
            'nome'   => 'API - Prato Certo (PDV e Cardápio Online)',
            'versao' => 'v1.0.0',
            'autor'  => 'João Vitor',
        ];
    }

   /**
    * Método responsável por criar a paginação a partir dos parâmetros da URL (?page=1&ipp=20)
    * @param  Request $request
    * @param  integer $quantidadeTotal
    * @param  integer $itensPorPagina Quantidade padrão por página
    * @return Pagination
    */
    protected static function getPaginacao($request, $quantidadeTotal, $itensPorPagina = 20){
        //QUERY PARAMS
        $queryParams = $request->getQueryParams();

        //INSTANCIA DE PAGINAÇÃO
        return new Pagination($quantidadeTotal, $queryParams['page'] ?? 1, $queryParams['ipp'] ?? $itensPorPagina);
    }

   /**
    * Método responsável por retornar os detalhes da paginação
    * @param Request $request
    * @param Pagination $obPagination
    * @return array
    */
    protected static function getPagination($request, $obPagination){
        return $obPagination->getInfo();
    }

   /**
    * Método responsável por retornar o termo de busca da URL (?busca=...)
    * @param  Request $request
    * @return string
    */
    protected static function getBusca($request){
        return mb_substr(trim((string)($request->getQueryParams()['busca'] ?? '')), 0, 100);
    }

   /**
    * Método responsável por executar uma operação dentro de uma transação
    * (se qualquer etapa falhar, nada é gravado no banco)
    * @param  callable $operacao
    * @return mixed
    */
    protected static function transacao($operacao){
        //INICIA A TRANSAÇÃO
        Database::beginTransaction();

        try{
            //EXECUTA A OPERAÇÃO
            $retorno = $operacao();

            //CONFIRMA AS ALTERAÇÕES
            Database::commit();
            return $retorno;

        }catch(Throwable $e){
            //DESFAZ AS ALTERAÇÕES E REPASSA O ERRO
            Database::rollBack();
            throw $e;
        }
    }

   /**
    * Método responsável por adicionar a lista de itens recebida da requisição ao pedido
    * @param Pedido $obPedido
    * @param mixed  $itens [ ['produtoId' => 1, 'quantidade' => 2, 'observacao' => ''] ]
    */
    protected static function preencherItens($obPedido, $itens){
        //VALIDA O FORMATO DA LISTA
        if(!is_array($itens)){
            throw new DadoInvalidoException("Os itens do pedido são inválidos.", null, 'itens');
        }

        //ADICIONA CADA ITEM (SOBRECARGA: adicionarItem(array))
        foreach($itens as $item){
            if(!is_array($item)){
                throw new DadoInvalidoException("Os itens do pedido são inválidos.", null, 'itens');
            }
            $obPedido->adicionarItem($item);
        }
    }

   /**
    * Método responsável por vincular (ou desvincular) o cliente do pedido
    * @param Pedido $obPedido
    * @param mixed  $clienteId
    */
    protected static function definirCliente($obPedido, $clienteId){
        //SEM CLIENTE
        if(empty($clienteId)){
            $obPedido->clienteId = null;
            return;
        }

        //VALIDA O CLIENTE
        $obCliente = EntityCliente::getClienteById($clienteId);
        if(!$obCliente instanceof EntityCliente or $obCliente->status != 'ativo'){
            throw new RegistroNaoEncontradoException("Cliente não encontrado.", null, 'clienteId');
        }

        $obPedido->clienteId = $obCliente->id;
    }
}
