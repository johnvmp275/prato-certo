<?php

namespace App\Controller\Api;

use \App\Model\Entity\Produto as EntityProduto;
use \App\Model\Entity\Categoria as EntityCategoria;
use \App\Utils\Validador;
use \App\Exception\DadoInvalidoException;
use \App\Exception\FalhaPersistenciaException;
use \App\Exception\RegistroNaoEncontradoException;

class Produtos extends Api{

    /**
     * Campos da consulta (inclui o nome da categoria)
     * @var string
     */
    const CAMPOS = '*, (SELECT c.nome FROM categorias c WHERE c.id = produtos.categoriaId) AS categoria';

    /**
     * Tipos de imagem aceitos no upload [ mime => extensão ]
     * @var array
     */
    const TIPOS_IMAGEM = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    /**
     * Tamanho máximo da imagem (2 MB)
     * @var integer
     */
    const TAMANHO_MAXIMO = 2 * 1024 * 1024;

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

        //GUARDA A IMAGEM ATUAL (PARA EXCLUIR O ARQUIVO ANTIGO SE FOR TROCADA)
        $imagemAnterior = $obProduto->imagem;

        //ATUALIZA O PRODUTO
        self::preencher($obProduto, $request->getPostVars());
        $obProduto->atualizar();

        //REMOVE O ARQUIVO ANTIGO DA PASTA DE UPLOAD QUANDO A IMAGEM MUDOU
        if($imagemAnterior != $obProduto->imagem){
            self::excluirImagem($imagemAnterior);
        }

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

        //PREENCHE O PRODUTO
        $obProduto->categoriaId     = $obCategoria->id;
        $obProduto->nome            = Validador::tamanho(Validador::obrigatorio($postVars['nome'] ?? '', 'nome', 'Nome'), 'nome', 2, 120);
        $obProduto->descricao       = mb_substr(trim((string)($postVars['descricao'] ?? '')), 0, 255) ?: null;
        $obProduto->preco           = round($preco, 2);
        $obProduto->controlaEstoque = !empty($postVars['controlaEstoque']) ? 1 : 0;
        $obProduto->estoque         = $obProduto->controlaEstoque ? Validador::inteiro($postVars['estoque'] ?? 0, 'estoque', 0, 'Estoque') : 0;
        $obProduto->status          = Validador::opcao($postVars['status'] ?? 'ativo', ['ativo', 'inativo'], 'status');

        //IMAGEM (POR ÚLTIMO, PARA NÃO SALVAR ARQUIVO SE OUTRO CAMPO ESTIVER INVÁLIDO)
        if(isset($_FILES['imagem']) and $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE){
            //ARQUIVO ENVIADO PELO FORMULÁRIO (UPLOAD)
            $obProduto->imagem = self::setUploadImagem($_FILES['imagem']);

        }elseif(!empty($postVars['removerImagem'])){
            //REMOVE A IMAGEM DO PRODUTO
            $obProduto->imagem = null;

        }elseif(isset($postVars['imagem']) and strlen(trim((string)$postVars['imagem']))){
            //URL EXTERNA (MANTIDA PARA COMPATIBILIDADE COM OS DADOS DE TESTE)
            $imagem = trim((string)$postVars['imagem']);
            if(!filter_var($imagem, FILTER_VALIDATE_URL)){
                throw new DadoInvalidoException("Informe uma URL de imagem válida.", null, 'imagem');
            }
            $obProduto->imagem = $imagem;
        }
    }

    /**
     * Método responsável por retornar a pasta onde as imagens dos produtos são salvas
     * @return string
     */
    private static function getPastaUpload(){
        //DEFINE O CAMINHO DA PASTA (backend/upload/produtos)
        $pasta = __DIR__.'/../../../upload/produtos/';

        //CRIA A PASTA SE AINDA NÃO EXISTIR
        if(!is_dir($pasta)) mkdir($pasta, 0775, true);

        return $pasta;
    }

    /**
     * Método responsável por salvar a imagem enviada na pasta de upload
     * (mesmo padrão do iron_forge: nome único com uniqid + move_uploaded_file)
     * @param  array $arquivo Posição do $_FILES
     * @return string Nome do arquivo salvo (gravado no banco)
     */
    private static function setUploadImagem($arquivo){
        //VÁLIDA SE O ARQUIVO FOI ENVIADO COM SUCESSO
        if($arquivo['error'] !== UPLOAD_ERR_OK){
            $tamanhoExcedido = in_array($arquivo['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE]);
            throw new DadoInvalidoException($tamanhoExcedido ? "A imagem deve ter no máximo 2 MB." : "Não foi possível enviar a imagem. Tente novamente.", null, 'imagem');
        }

        //VALIDA O TAMANHO DO ARQUIVO
        if($arquivo['size'] > self::TAMANHO_MAXIMO){
            throw new DadoInvalidoException("A imagem deve ter no máximo 2 MB.", null, 'imagem');
        }

        //VALIDA O TIPO REAL DO ARQUIVO (NÃO CONFIA NA EXTENSÃO ENVIADA)
        $tipo = (new \finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
        if(!isset(self::TIPOS_IMAGEM[$tipo])){
            throw new DadoInvalidoException("Envie uma imagem JPG, PNG ou WEBP.", null, 'imagem');
        }

        //CRIA UM NOME ÚNICO PARA O ARQUIVO
        $nomeArquivo = str_replace('.', '', uniqid('', true)).'.'.self::TIPOS_IMAGEM[$tipo];

        //DEFINE UM CAMINHO DE DESTINO
        $caminhoDestino = self::getPastaUpload().$nomeArquivo;

        //MOVE O ARQUIVO PARA A PASTA
        if(!move_uploaded_file($arquivo['tmp_name'], $caminhoDestino)){
            throw new FalhaPersistenciaException("Não foi possível salvar a imagem. Tente novamente.");
        }

        //RETORNA O NOME DO ARQUIVO PARA SALVAR NO BANCO
        return $nomeArquivo;
    }

    /**
     * Método responsável por excluir uma imagem da pasta de upload
     * @param string|null $nomeArquivo
     */
    private static function excluirImagem($nomeArquivo){
        //IGNORA PRODUTOS SEM IMAGEM E IMAGENS EXTERNAS (URL)
        if(!strlen((string)$nomeArquivo) or preg_match('/^https?:\/\//', $nomeArquivo)) return;

        //EXCLUI O ARQUIVO (basename EVITA SAIR DA PASTA DE UPLOAD)
        $caminho = self::getPastaUpload().basename($nomeArquivo);
        if(is_file($caminho)) unlink($caminho);
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
