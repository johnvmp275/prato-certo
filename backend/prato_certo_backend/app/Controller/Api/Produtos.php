<?php

namespace App\Controller\Api;

use \App\Model\Entity\Produto as EntityProduto;
use \App\Model\Entity\Categoria as EntityCategoria;
use \App\Utils\Validador;
use \App\Exception\DadoInvalidoException;
use \App\Exception\RegistroNaoEncontradoException;

class Produtos extends Api{

    /**
     * Campos da consulta (inclui o nome da categoria)
     * @var string
     */
    const CAMPOS = '*, (SELECT c.nome FROM categorias c WHERE c.id = produtos.categoriaId) AS categoria';

   /**
    * Método responsável por retornar os produtos cadastrados
    * @param  Request $request
    * @return array
    */
    public static function getProdutos($request){
        //PRODUTOS
        $itens = [];

        //OBTÉM OS PARÂMETROS DA URL
        $queryParams = $request->getQueryParams();

        //FILTROS (WHERE)
        $filtros = [];
        $params  = [];

        //BUSCA POR NOME OU DESCRIÇÃO
        if($busca = self::getBusca($request)){
            $filtros[] = '(nome LIKE ? OR descricao LIKE ?)';
            array_push($params, '%'.$busca.'%', '%'.$busca.'%');
        }

        //FILTRO DE CATEGORIA
        if(!empty($queryParams['categoriaId']) and is_numeric($queryParams['categoriaId'])){
            $filtros[] = 'categoriaId = ?';
            $params[]  = (int)$queryParams['categoriaId'];
        }

        //FILTRO DE STATUS
        $status = $queryParams['status'] ?? '';
        if(in_array($status, ['ativo', 'inativo'])){
            $filtros[] = 'status = ?';
            $params[]  = $status;
        }

        //CONSTRUTOR WHERE
        $where = implode(' AND ', $filtros);

        //QUANTIDADE TOTAL DE REGISTROS
        $quantidadeTotal = EntityProduto::getProdutos($where, null, null, 'COUNT(*) as qtd', $params)->fetchObject()->qtd;

        //INSTANCIA DE PAGINAÇÃO
        $obPagination = self::getPaginacao($request, $quantidadeTotal, 20);

        //RESULTADOS DA PÁGINA
        $results = EntityProduto::getProdutos($where, 'nome ASC', $obPagination->getLimit(), self::CAMPOS, $params);
        while($obProduto = $results->fetchObject(EntityProduto::class)){
            $itens[] = $obProduto->toArray();
        }

        //RETORNA OS PRODUTOS
        return [
            'produtos'  => $itens,
            'paginacao' => parent::getPagination($request, $obPagination)
        ];
    }

   /**
    * Método responsável por retornar um produto pelo ID
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function getProduto($request, $id){
        return self::getProdutoValido($id)->toArray();
    }

   /**
    * Método responsável por cadastrar um novo produto
    * @param  Request $request
    * @return array
    */
    public static function setNovoProduto($request){
        //NOVO PRODUTO
        $obProduto = new EntityProduto;
        self::preencher($obProduto, $request->getPostVars());
        $obProduto->cadastrar();

        //RETORNA OS DETALHES DO PRODUTO CADASTRADO
        return self::getProdutoValido($obProduto->id)->toArray();
    }

   /**
    * Método responsável por atualizar um produto
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function setEditProduto($request, $id){
        //BUSCA O PRODUTO
        $obProduto = self::getProdutoValido($id);

        //ATUALIZA O PRODUTO
        self::preencher($obProduto, $request->getPostVars());
        $obProduto->atualizar();

        return self::getProdutoValido($obProduto->id)->toArray();
    }

   /**
    * Método responsável por inativar um produto
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function setDeleteProduto($request, $id){
        //INATIVA O PRODUTO (RN12: MANTÉM O HISTÓRICO DOS PEDIDOS)
        self::getProdutoValido($id)->inativar();

        return [
            'sucesso' => true
        ];
    }

   /**
    * Método responsável por validar e preencher os dados do produto
    * @param EntityProduto $obProduto
    * @param array         $postVars
    */
    private static function preencher($obProduto, $postVars){
        //VALIDA A CATEGORIA
        $categoriaId = Validador::obrigatorio($postVars['categoriaId'] ?? '', 'categoriaId', 'Categoria');
        $obCategoria = EntityCategoria::getCategoriaById($categoriaId);
        if(!$obCategoria instanceof EntityCategoria or $obCategoria->status != 'ativo'){
            throw new DadoInvalidoException("Selecione uma categoria ativa.", null, 'categoriaId');
        }

        //VALIDA O PREÇO
        $preco = Validador::numero(Validador::obrigatorio($postVars['preco'] ?? '', 'preco', 'Preço'), 'preco', 0.01, 'Preço');

        //VALIDA A IMAGEM (URL OPCIONAL)
        $imagem = trim((string)($postVars['imagem'] ?? ''));
        if(strlen($imagem) and !filter_var($imagem, FILTER_VALIDATE_URL)){
            throw new DadoInvalidoException("Informe uma URL de imagem válida.", null, 'imagem');
        }

        //PREENCHE O PRODUTO
        $obProduto->categoriaId     = $obCategoria->id;
        $obProduto->nome            = Validador::tamanho(Validador::obrigatorio($postVars['nome'] ?? '', 'nome', 'Nome'), 'nome', 2, 120);
        $obProduto->descricao       = mb_substr(trim((string)($postVars['descricao'] ?? '')), 0, 255) ?: null;
        $obProduto->preco           = round($preco, 2);
        $obProduto->imagem          = $imagem ?: null;
        $obProduto->controlaEstoque = !empty($postVars['controlaEstoque']) ? 1 : 0;
        $obProduto->estoque         = $obProduto->controlaEstoque ? Validador::inteiro($postVars['estoque'] ?? 0, 'estoque', 0, 'Estoque') : 0;
        $obProduto->status          = Validador::opcao($postVars['status'] ?? 'ativo', ['ativo', 'inativo'], 'status');
    }

   /**
    * Método responsável por buscar o produto e validar se existe
    * @param  integer $id
    * @return EntityProduto
    */
    private static function getProdutoValido($id){
        $obProduto = EntityProduto::getProdutos('id = ?', null, null, self::CAMPOS, [(int)$id])->fetchObject(EntityProduto::class);
        if(!$obProduto instanceof EntityProduto){
            throw new RegistroNaoEncontradoException("Produto não encontrado.");
        }
        return $obProduto;
    }
}
