<?php

namespace App\Controller\Api;

use \App\Model\Entity\Mesa as EntityMesa;
use \App\Utils\Validador;
use \App\Exception\DadoInvalidoException;
use \App\Exception\RegistroNaoEncontradoException;

class Mesas extends Api{

   /**
    * Método responsável por retornar o mapa de mesas com o pedido em andamento de cada uma
    * @param  Request $request
    * @return array
    */
    public static function getMesas($request){
        //MESAS
        $itens = [];

        //POR PADRÃO LISTA SOMENTE AS MESAS ATIVAS (?todas=1 LISTA TODAS)
        $todas = !empty($request->getQueryParams()['todas']);
        $where = $todas ? null : 'status = "ativo"';

        //RESULTADOS
        $results = EntityMesa::getMesas($where, 'numero ASC');
        while($obMesa = $results->fetchObject(EntityMesa::class)){
            //PEDIDO EM ANDAMENTO
            $obPedido = $obMesa->getPedidoAberto();

            //ESTADO VISUAL DA MESA (LIVRE, OCUPADA OU EM ESPERA)
            $estado = !$obPedido ? 'livre' : ($obPedido->status == 'em_espera' ? 'em_espera' : 'ocupada');

            $itens[] = array_merge($obMesa->toArray(), [
                'estado' => $estado,
                'pedido' => $obPedido ? [
                    'id'              => (int)$obPedido->id,
                    'codigo'          => $obPedido->codigo,
                    'status'          => $obPedido->status,
                    'numPessoas'      => (int)$obPedido->numPessoas,
                    'total'           => (float)$obPedido->total,
                    'quantidadeItens' => array_sum(array_map(fn($obItem) => (int)$obItem->quantidade, $obPedido->getItens())),
                    'criadoEm'        => $obPedido->criadoEm
                ] : null
            ]);
        }

        //RESUMO
        $estados = array_count_values(array_column($itens, 'estado'));

        //RETORNA AS MESAS
        return [
            'mesas'  => $itens,
            'resumo' => [
                'livres'   => $estados['livre'] ?? 0,
                'ocupadas' => $estados['ocupada'] ?? 0,
                'emEspera' => $estados['em_espera'] ?? 0
            ]
        ];
    }

   /**
    * Método responsável por cadastrar uma nova mesa
    * @param  Request $request
    * @return array
    */
    public static function setNovaMesa($request){
        //NOVA MESA
        $obMesa = new EntityMesa;
        self::preencher($obMesa, $request->getPostVars());
        $obMesa->cadastrar();

        return $obMesa->toArray();
    }

   /**
    * Método responsável por atualizar uma mesa
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function setEditMesa($request, $id){
        //BUSCA A MESA
        $obMesa = self::getMesaValida($id);

        //ATUALIZA A MESA
        self::preencher($obMesa, $request->getPostVars());
        if($obMesa->status == 'inativo' and !$obMesa->isLivre()){
            throw new DadoInvalidoException("Não é possível inativar uma mesa ocupada.", null, 'status');
        }
        $obMesa->atualizar();

        return $obMesa->toArray();
    }

   /**
    * Método responsável por inativar uma mesa
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function setDeleteMesa($request, $id){
        //INATIVA A MESA (VALIDA SE ESTÁ LIVRE)
        self::getMesaValida($id)->inativar();

        return [
            'sucesso' => true
        ];
    }

   /**
    * Método responsável por validar e preencher os dados da mesa
    * @param EntityMesa $obMesa
    * @param array      $postVars
    */
    private static function preencher($obMesa, $postVars){
        //VALIDA OS CAMPOS
        $numero     = Validador::inteiro(Validador::obrigatorio($postVars['numero'] ?? '', 'numero', 'Número'), 'numero', 1, 'Número');
        $capacidade = Validador::inteiro(Validador::obrigatorio($postVars['capacidade'] ?? '', 'capacidade', 'Capacidade'), 'capacidade', 1, 'Capacidade');

        //VALIDA A CAPACIDADE MÁXIMA
        if($capacidade > 30){
            throw new DadoInvalidoException("A capacidade máxima de uma mesa é 30 pessoas.", null, 'capacidade');
        }

        //VALIDA A DUPLICAÇÃO DO NÚMERO (RN13)
        $obExistente = EntityMesa::getMesas('numero = ?', null, 1, '*', [$numero])->fetchObject(EntityMesa::class);
        if($obExistente and $obExistente->id != $obMesa->id){
            throw new DadoInvalidoException("Já existe uma mesa com o número ".$numero.".", null, 'numero');
        }

        $obMesa->numero     = $numero;
        $obMesa->capacidade = $capacidade;
        $obMesa->status     = Validador::opcao($postVars['status'] ?? 'ativo', ['ativo', 'inativo'], 'status');
    }

   /**
    * Método responsável por buscar a mesa e validar se existe
    * @param  integer $id
    * @return EntityMesa
    */
    private static function getMesaValida($id){
        $obMesa = EntityMesa::getMesaById($id);
        if(!$obMesa instanceof EntityMesa){
            throw new RegistroNaoEncontradoException("Mesa não encontrada.");
        }
        return $obMesa;
    }
}
