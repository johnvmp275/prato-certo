<?php

namespace App\Controller\Api;

use \App\Model\Entity\Categoria as EntityCategoria;
use \App\Utils\Validador;
use \App\Exception\DadoInvalidoException;
use \App\Exception\RegistroNaoEncontradoException;

class Categorias extends Api{

   /**
    * Método responsável por retornar as categorias cadastradas
    * @param  Request $request
    * @return array
    */
    public static function getCategorias($request){
        //CATEGORIAS
        $itens = [];

        //FILTROS (WHERE)
        $filtros = [];
        $params  = [];

        //BUSCA POR NOME
        if($busca = self::getBusca($request)){
            $filtros[] = 'nome LIKE ?';
            $params[]  = '%'.$busca.'%';
        }

        //FILTRO DE STATUS
        $status = $request->getQueryParams()['status'] ?? '';
        if(in_array($status, ['ativo', 'inativo'])){
            $filtros[] = 'status = ?';
            $params[]  = $status;
        }

        //QUANTIDADE DE PRODUTOS ATIVOS EM CADA CATEGORIA
        $fields = '*, (SELECT COUNT(*) FROM produtos p WHERE p.categoriaId = categorias.id AND p.status = "ativo") AS quantidadeProdutos';

        //RESULTADOS
        $results = EntityCategoria::getCategorias(implode(' AND ', $filtros), 'ordem ASC, nome ASC', null, $fields, $params);
        while($dados = $results->fetch(\PDO::FETCH_ASSOC)){
            $obCategoria = new EntityCategoria;
            foreach(['id', 'nome', 'ordem', 'status'] as $campo) $obCategoria->$campo = $dados[$campo];

            $itens[] = array_merge($obCategoria->toArray(), [
                'quantidadeProdutos' => (int)$dados['quantidadeProdutos']
            ]);
        }

        //RETORNA AS CATEGORIAS
        return [
            'categorias' => $itens
        ];
    }

   /**
    * Método responsável por cadastrar uma nova categoria
    * @param  Request $request
    * @return array
    */
    public static function setNovaCategoria($request){
        //POST VARS
        $postVars = $request->getPostVars();

        //NOVA CATEGORIA
        $obCategoria = new EntityCategoria;
        self::preencher($obCategoria, $postVars);
        $obCategoria->cadastrar();

        //RETORNA OS DETALHES DA CATEGORIA
        return $obCategoria->toArray();
    }

   /**
    * Método responsável por atualizar uma categoria
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function setEditCategoria($request, $id){
        //BUSCA A CATEGORIA
        $obCategoria = self::getCategoriaValida($id);

        //ATUALIZA A CATEGORIA
        self::preencher($obCategoria, $request->getPostVars());
        $obCategoria->atualizar();

        return $obCategoria->toArray();
    }

   /**
    * Método responsável por inativar uma categoria
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function setDeleteCategoria($request, $id){
        //INATIVA A CATEGORIA (OS PRODUTOS CONTINUAM EXISTINDO - AGREGAÇÃO)
        self::getCategoriaValida($id)->inativar();

        return [
            'sucesso' => true
        ];
    }

   /**
    * Método responsável por validar e preencher os dados da categoria
    * @param EntityCategoria $obCategoria
    * @param array           $postVars
    */
    private static function preencher($obCategoria, $postVars){
        //VALIDA OS CAMPOS
        $nome = Validador::tamanho(Validador::obrigatorio($postVars['nome'] ?? '', 'nome', 'Nome'), 'nome', 2, 60);

        //VALIDA A DUPLICAÇÃO DO NOME
        $obExistente = EntityCategoria::getCategorias('nome = ?', null, 1, '*', [$nome])->fetchObject(EntityCategoria::class);
        if($obExistente and $obExistente->id != $obCategoria->id){
            throw new DadoInvalidoException("Já existe uma categoria com o nome '".$nome."'.", null, 'nome');
        }

        $obCategoria->nome   = $nome;
        $obCategoria->status = Validador::opcao($postVars['status'] ?? 'ativo', ['ativo', 'inativo'], 'status');

        //POSIÇÃO: DEFINIDA PELO ARRASTAR E SOLTAR DA LISTAGEM (setOrdem)
        //NOVA CATEGORIA SEM ORDEM INFORMADA ENTRA NO FIM; NA EDIÇÃO A POSIÇÃO ATUAL É MANTIDA
        if(isset($postVars['ordem']) and $postVars['ordem'] !== ''){
            $obCategoria->ordem = Validador::inteiro($postVars['ordem'], 'ordem', 0, 'Ordem');
        }elseif(!$obCategoria->id){
            $obCategoria->ordem = (int)EntityCategoria::getCategorias(null, null, null, 'COALESCE(MAX(ordem), 0) + 1 AS proxima')->fetchObject()->proxima;
        }
    }

   /**
    * Método responsável por salvar a nova ordem das categorias (arrastar e soltar)
    * Body: { "ids": [3, 1, 2] } -> a primeira da lista fica com ordem 1, a segunda com 2...
    * @param  Request $request
    * @return array
    */
    public static function setOrdem($request){
        //VALIDA A LISTA DE IDS
        $ids = $request->getPostVars()['ids'] ?? null;
        if(!is_array($ids) or !count($ids)){
            throw new DadoInvalidoException("Informe a nova ordem das categorias.", null, 'ids');
        }
        $ids = array_map(fn($id) => Validador::inteiro($id, 'ids', 1, 'Categoria'), $ids);
        if(count($ids) != count(array_unique($ids))){
            throw new DadoInvalidoException("A lista de categorias tem itens repetidos.", null, 'ids');
        }

        //SALVA TUDO OU NADA (SE UMA CATEGORIA NÃO EXISTIR, NENHUMA POSIÇÃO MUDA)
        self::transacao(function() use ($ids){
            foreach($ids as $posicao => $id){
                $obCategoria = self::getCategoriaValida($id);
                $obCategoria->ordem = $posicao + 1;
                $obCategoria->atualizar();
            }
        });

        return [
            'sucesso' => true
        ];
    }

   /**
    * Método responsável por buscar a categoria e validar se existe
    * @param  integer $id
    * @return EntityCategoria
    */
    private static function getCategoriaValida($id){
        $obCategoria = EntityCategoria::getCategoriaById($id);
        if(!$obCategoria instanceof EntityCategoria){
            throw new RegistroNaoEncontradoException("Categoria não encontrada.");
        }
        return $obCategoria;
    }
}
