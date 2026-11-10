<?php

namespace App\Controller\Api;

use \App\Model\Entity\Categoria as EntityCategoria;
use \App\Model\Entity\Configuracao as EntityConfiguracao;
use \App\Model\Entity\Pedido as EntityPedido;
use \App\Model\Entity\PedidoOnline as EntityPedidoOnline;
use \App\Model\Entity\Pix as EntityPix;
use \App\Model\Entity\Cupom as EntityCupom;
use \App\Utils\Validador;
use \App\Session\Cardapio\Login as SessionCardapioLogin;
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

        //FILTROS DA URL (?categoria=2&busca=pizza)
        $queryParams = $request->getQueryParams();
        $busca       = parent::getBusca($request);
        $categoriaId = filter_var($queryParams['categoria'] ?? '', FILTER_VALIDATE_INT) ?: null;

        //ABAS: TODAS AS CATEGORIAS ATIVAS QUE TÊM PRODUTOS ATIVOS (NÃO MUDAM COM OS FILTROS)
        $abas    = [];
        $results = EntityCategoria::getCategorias(
            'status = "ativo" AND EXISTS (SELECT 1 FROM produtos p WHERE p.categoriaId = categorias.id AND p.status = "ativo")',
            'ordem ASC, nome ASC', null, 'id, nome'
        );
        while($dados = $results->fetch(\PDO::FETCH_ASSOC)){
            $abas[] = ['id' => (int)$dados['id'], 'nome' => $dados['nome']];
        }

        //CATEGORIAS FILTRADAS (CATEGORIA ESCOLHIDA E/OU BUSCA NO NOME E NA DESCRIÇÃO)
        $categorias = [];
        $where      = 'status = "ativo"'.($categoriaId ? ' AND id = ?' : '');
        $results    = EntityCategoria::getCategorias($where, 'ordem ASC, nome ASC', null, '*', $categoriaId ? [$categoriaId] : []);
        while($obCategoria = $results->fetchObject(EntityCategoria::class)){
            //PRODUTOS DA CATEGORIA
            $produtos = array_map(function($obProduto){
                return [
                    'id'         => (int)$obProduto->id,
                    'nome'       => $obProduto->nome,
                    'descricao'  => $obProduto->descricao,
                    'preco'      => (float)$obProduto->preco,
                    'imagem'     => $obProduto->getImagemUrl(),
                    'disponivel' => $obProduto->isDisponivel()
                ];
            }, $obCategoria->getProdutos($busca));

            //IGNORA CATEGORIAS VAZIAS (OU SEM RESULTADO NA BUSCA)
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
                'taxaEntrega' => (float)$obConfiguracao->taxaEntrega,
                'pixDisponivel' => (bool)$obConfiguracao->isPixConfigurado()
            ],
            'abas'       => $abas,
            'filtros'    => [
                'categoria' => $categoriaId,
                'busca'     => $busca
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

            //VINCULA O PEDIDO À CONTA DO CLIENTE LOGADO (O LOGIN É OPCIONAL)
            $obCliente = SessionCardapioLogin::getClienteLogado();
            if($obCliente) $obPedido->clienteId = $obCliente->id;

            //VALIDA OS DADOS DE CONTATO E ENTREGA
            $obPedido->validarContato();

            //ITENS
            self::preencherItens($obPedido, $postVars['itens']);

            //CUPOM DE DESCONTO (OPCIONAL, MESMA REGRA DO CAIXA)
            if(strlen(trim((string)($postVars['cupom'] ?? '')))){
                $obPedido->aplicarDesconto(self::getCupomValido($postVars['cupom']));
            }

            //FORMA DE PAGAMENTO ESCOLHIDA NO CARRINHO (VALIDADA DEPOIS DOS ITENS E DO CUPOM POR CAUSA DO TROCO)
            $obPedido->formaPagamento = $postVars['formaPagamento'] ?? '';
            $obPedido->trocoPara      = $postVars['trocoPara'] ?? null;
            $obPedido->validarPagamento();

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
    * Método responsável por validar um cupom no carrinho e calcular o desconto sobre o subtotal
    * Body: { "codigo": "BEMVINDO10", "subtotal": 59.80 }
    * @param  Request $request
    * @return array
    */
    public static function setValidarCupom($request){
        //POST VARS
        $postVars = $request->getPostVars();
        $obCupom  = self::getCupomValido($postVars['codigo'] ?? '');
        $subtotal = Validador::numero($postVars['subtotal'] ?? 0, 'subtotal', 0, 'Subtotal');

        return [
            'codigo'    => $obCupom->codigo,
            'tipo'      => $obCupom->tipo,
            'valor'     => (float)$obCupom->valor,
            'descricao' => $obCupom->tipo == 'percentual' ? number_format($obCupom->valor, 0, ',', '.').'% de desconto' : 'R$ '.number_format($obCupom->valor, 2, ',', '.').' de desconto',
            'desconto'  => $obCupom->calcularDesconto($subtotal)
        ];
    }

   /**
    * Método responsável por buscar um cupom pelo código e validar se ele pode ser usado
    * @param  string $codigo
    * @return EntityCupom
    */
    private static function getCupomValido($codigo){
        //BUSCA O CUPOM
        $obCupom = EntityCupom::getCupomByCodigo(Validador::obrigatorio($codigo, 'cupom', 'Cupom'));
        if(!$obCupom instanceof EntityCupom){
            throw new DadoInvalidoException("Cupom não encontrado. Confira o código.", null, 'cupom');
        }

        //VALIDA STATUS E VALIDADE
        if(!$obCupom->isValido()){
            throw new DadoInvalidoException("O cupom '".$obCupom->codigo."' está expirado ou inativo.", null, 'cupom');
        }

        return $obCupom;
    }

   /**
    * Método responsável por retornar o PIX (chave, copia e cola e QR Code) de um pedido online
    * Só existe para pedidos que o cliente escolheu pagar com PIX e que ainda não foram encerrados
    * @param  Request $request
    * @param  string  $codigo
    * @return array
    */
    public static function getPix($request, $codigo){
        //BUSCA O PEDIDO PELO CÓDIGO
        $obPedido = preg_match('/^\d{7}$/', $codigo) ? EntityPedido::getPedidoByCodigo($codigo) : null;
        if(!$obPedido instanceof EntityPedidoOnline){
            throw new RegistroNaoEncontradoException("Pedido não encontrado. Confira o código informado.");
        }

        //VALIDA A FORMA DE PAGAMENTO E O STATUS DO PEDIDO
        if($obPedido->formaPagamento != 'pix'){
            throw new EstadoInvalidoException("Este pedido não será pago com PIX.");
        }
        if(in_array($obPedido->status, [EntityPedido::STATUS_FINALIZADO, EntityPedido::STATUS_CANCELADO])){
            throw new EstadoInvalidoException("Este pedido já foi ".mb_strtolower(EntityPedido::STATUS_DESCRICAO[$obPedido->status]).".");
        }

        //VALIDA SE O RESTAURANTE CADASTROU O PIX
        $obConfiguracao = EntityConfiguracao::getConfiguracao();
        if(!$obConfiguracao->isPixConfigurado()){
            throw new EstadoInvalidoException("O restaurante ainda não cadastrou a chave PIX. Pague com PIX na entrega ou retirada.");
        }

        //MONTA O PAYLOAD (O VALOR É O TOTAL ATUAL DO PEDIDO)
        $obPix = (new EntityPix)
            ->setPixKey($obConfiguracao->pixChave)
            ->setDescription('Pedido '.$obPedido->codigo)
            ->setMerchantName($obConfiguracao->pixTitular)
            ->setMerchantCity($obConfiguracao->pixCidade)
            ->setAmount($obPedido->total)
            ->setTxId('PEDIDO'.$obPedido->codigo);

        return [
            'chave'      => $obConfiguracao->pixChave,
            'titular'    => $obConfiguracao->pixTitular,
            'valor'      => (float)$obPedido->total,
            'copiaECola' => $obPix->getPayLoad(),
            'qrCode'     => $obPix->getQrCodeImagem()
        ];
    }

   /**
    * Método responsável por montar o resumo público do pedido (sem dados sensíveis)
    * @param  EntityPedidoOnline $obPedido
    * @return array
    */
    public static function getResumoPublico($obPedido){
        return [
            'codigo'          => $obPedido->codigo,
            'status'          => $obPedido->status,
            'statusDescricao' => EntityPedido::STATUS_DESCRICAO[$obPedido->status],
            'origem'          => $obPedido->getOrigem(),
            'tipoEntrega'     => $obPedido->tipoEntrega,
            'nomeContato'     => $obPedido->nomeContato,
            'pagamentoPrevisto' => $obPedido->getPagamentoPrevisto(),
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
