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
        $obCategoria->ordem  = Validador::inteiro($postVars['ordem'] ?? 0, 'ordem', 0, 'Ordem');
        $obCategoria->status = Validador::opcao($postVars['status'] ?? 'ativo', ['ativo', 'inativo'], 'status');
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
