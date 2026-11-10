<?php

namespace App\Controller\Api;

use \App\Model\Entity\Categoria as EntityCategoria;
use \App\Model\Entity\Configuracao as EntityConfiguracao;
use \App\Model\Entity\Pedido as EntityPedido;
use \App\Model\Entity\PedidoOnline as EntityPedidoOnline;
use \App\Exception\DadoInvalidoException;
use \App\Exception\EstadoInvalidoException;
use \App\Exception\RegistroNaoEncontradoException;

class Cardapio extends Api{

   /**
    * Método responsável por retornar o cardápio público (somente itens ativos - RN10)
    * @param  Request $request
    * @return array
    */
    public static function getCardapio($request){
        //CONFIGURAÇÃO DO RESTAURANTE
        $obConfiguracao = EntityConfiguracao::getConfiguracao();

        //CATEGORIAS
        $categorias = [];

        //BUSCA AS CATEGORIAS ATIVAS COM SEUS PRODUTOS ATIVOS
        $results = EntityCategoria::getCategorias('status = "ativo"', 'ordem ASC, nome ASC');
        while($obCategoria = $results->fetchObject(EntityCategoria::class)){
            //PRODUTOS DA CATEGORIA
            $produtos = array_map(function($obProduto){
                return [
                    'id'         => (int)$obProduto->id,
                    'nome'       => $obProduto->nome,
                    'descricao'  => $obProduto->descricao,
                    'preco'      => (float)$obProduto->preco,
                    'imagem'     => $obProduto->imagem,
                    'disponivel' => $obProduto->isDisponivel()
                ];
            }, $obCategoria->getProdutos());

            //IGNORA CATEGORIAS VAZIAS
            if(!count($produtos)) continue;

            $categorias[] = [
                'id'       => (int)$obCategoria->id,
                'nome'     => $obCategoria->nome,
                'produtos' => $produtos
            ];
        }

        //RETORNA O CARDÁPIO
        return [
            'restaurante' => [
                'nome'        => $obConfiguracao->nomeRestaurante,
                'telefone'    => $obConfiguracao->telefone,
                'endereco'    => $obConfiguracao->endereco,
                'aberto'      => (bool)$obConfiguracao->cardapioAberto,
                'taxaEntrega' => (float)$obConfiguracao->taxaEntrega
            ],
            'categorias' => $categorias
        ];
    }

   /**
    * Método responsável por receber um pedido do cardápio online (RN11)
    * @param  Request $request
    * @return array
    */
    public static function setPedido($request){
        //POST VARS
        $postVars       = $request->getPostVars();
        $obConfiguracao = EntityConfiguracao::getConfiguracao();

        //VALIDA SE O CARDÁPIO ESTÁ ABERTO
        if(!$obConfiguracao->cardapioAberto){
            throw new EstadoInvalidoException("O restaurante não está recebendo pedidos online no momento.");
        }

        //VALIDA SE HÁ ITENS NO CARRINHO
        if(empty($postVars['itens']) or !is_array($postVars['itens'])){
            throw new DadoInvalidoException("Adicione pelo menos um item ao carrinho.", null, 'itens');
        }

        $obPedido = self::transacao(function() use ($postVars, $obConfiguracao){
            //NOVO PEDIDO ONLINE (CHEGA COMO PENDENTE)
            $obPedido = new EntityPedidoOnline;
            $obPedido->nomeContato     = $postVars['nomeContato'] ?? '';
            $obPedido->telefoneContato = $postVars['telefoneContato'] ?? '';
            $obPedido->tipoEntrega     = $postVars['tipoEntrega'] ?? 'retirada';
            $obPedido->enderecoEntrega = $postVars['enderecoEntrega'] ?? '';
            $obPedido->taxaEntrega     = (float)$obConfiguracao->taxaEntrega;
            $obPedido->observacao      = mb_substr(trim((string)($postVars['observacao'] ?? '')), 0, 255) ?: null;

            //VALIDA OS DADOS DE CONTATO E ENTREGA
            $obPedido->validarContato();

            //ITENS
            self::preencherItens($obPedido, $postVars['itens']);

            //SALVA O PEDIDO
            $obPedido->salvar();
            return $obPedido;
        });

        //RETORNA O RESUMO PÚBLICO DO PEDIDO
        return self::getResumoPublico($obPedido);
    }

   /**
    * Método responsável por retornar o acompanhamento de um pedido online pelo código
    * @param  Request $request
    * @param  string  $codigo
    * @return array
    */
    public static function getPedido($request, $codigo){
        //BUSCA O PEDIDO PELO CÓDIGO
        $obPedido = preg_match('/^\d{7}$/', $codigo) ? EntityPedido::getPedidoByCodigo($codigo) : null;

        //SOMENTE PEDIDOS ONLINE PODEM SER CONSULTADOS PUBLICAMENTE
        if(!$obPedido instanceof EntityPedidoOnline){
            throw new RegistroNaoEncontradoException("Pedido não encontrado. Confira o código informado.");
        }

        return self::getResumoPublico($obPedido);
    }

   /**
    * Método responsável por montar o resumo público do pedido (sem dados sensíveis)
    * @param  EntityPedidoOnline $obPedido
    * @return array
    */
    private static function getResumoPublico($obPedido){
        return [
            'codigo'          => $obPedido->codigo,
            'status'          => $obPedido->status,
            'statusDescricao' => EntityPedido::STATUS_DESCRICAO[$obPedido->status],
            'origem'          => $obPedido->getOrigem(),
            'tipoEntrega'     => $obPedido->tipoEntrega,
            'nomeContato'     => $obPedido->nomeContato,
            'itens'           => array_map(fn($obItem) => [
                'nomeProduto' => $obItem->nomeProduto,
                'quantidade'  => (int)$obItem->quantidade,
                'observacao'  => $obItem->observacao,
                'subtotal'    => $obItem->calcularSubtotal()
            ], $obPedido->getItens()),
            'valores'         => [
                'subtotal'      => (float)$obPedido->subtotal,
                'desconto'      => (float)$obPedido->desconto,
                'taxa'          => (float)$obPedido->taxa,
                'taxaDescricao' => $obPedido->getDescricaoTaxa(),
                'total'         => (float)$obPedido->total
            ],
            'criadoEm'        => $obPedido->criadoEm
        ];
    }
}
