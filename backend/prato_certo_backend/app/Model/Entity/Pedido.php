<?php

namespace App\Model\Entity;

use \App\DatabaseManager\Database;
use \App\Utils\Validador;
use \App\Exception\DadoInvalidoException;
use \App\Exception\EstadoInvalidoException;
use \App\Exception\RegistroNaoEncontradoException;

abstract class Pedido{

    /**
     * Status possíveis do pedido
     * @var string
     */
    const STATUS_PENDENTE   = 'pendente';
    const STATUS_ABERTO     = 'aberto';
    const STATUS_EM_ESPERA  = 'em_espera';
    const STATUS_FINALIZADO = 'finalizado';
    const STATUS_CANCELADO  = 'cancelado';

    /**
     * Descrição dos status para exibição
     * @var array
     */
    const STATUS_DESCRICAO = [
        'pendente'   => 'Aguardando aceite',
        'aberto'     => 'Aberto',
        'em_espera'  => 'Em espera',
        'finalizado' => 'Finalizado',
        'cancelado'  => 'Cancelado'
    ];

    /**
     * Id do pedido
     * @var integer
     */
    public $id;

    /**
     * Código público do pedido (ex: 0542145)
     * @var string
     */
    public $codigo;

    /**
     * Tipo do pedido (mesa | balcao | online) - definido pela subclasse
     * @var string
     */
    public $tipo;

    /**
     * Status do pedido
     * @var string
     */
    public $status = self::STATUS_ABERTO;

    /**
     * Id do cliente (opcional)
     * @var integer|null
     */
    public $clienteId;

    /**
     * Id do usuário que abriu/aceitou o pedido
     * @var integer|null
     */
    public $usuarioId;

    /**
     * Id do cupom aplicado
     * @var integer|null
     */
    public $cupomId;

    /**
     * Tipo do desconto aplicado (valor | percentual | cupom)
     * @var string|null
     */
    public $descontoTipo;

    /**
     * Valor informado do desconto (R$ ou %)
     * @var float
     */
    public $descontoValor = 0;

    /**
     * Valor calculado do desconto em R$
     * @var float
     */
    public $desconto = 0;

    /**
     * Soma dos itens
     * @var float
     */
    public $subtotal = 0;

    /**
     * Taxa calculada pela subclasse (serviço ou entrega)
     * @var float
     */
    public $taxa = 0;

    /**
     * Valor total do pedido
     * @var float
     */
    public $total = 0;

    /**
     * Observação geral do pedido
     * @var string|null
     */
    public $observacao;

    /**
     * Datas de controle
     * @var string
     */
    public $criadoEm;
    public $atualizadoEm;
    public $finalizadoEm;

    /**
     * Itens do pedido (composição - carregados sob demanda)
     * @var ItemPedido[]|null
     */
    protected $itens = null;

    /**
     * Pagamentos do pedido (composição - carregados sob demanda)
     * @var Pagamento[]|null
     */
    protected $pagamentos = null;

    /**
     * Método responsável por calcular a taxa do pedido
     * (POLIMORFISMO: mesa = serviço, online = entrega, balcão = zero)
     * @return float
     */
    abstract public function calcularTaxa();

    /**
     * Método responsável por retornar a descrição da taxa para exibição
     * @return string
     */
    abstract public function getDescricaoTaxa();

    /**
     * Método responsável por retornar a origem do pedido para exibição (ex: Mesa 05)
     * @return string
     */
    abstract public function getOrigem();

    /**
     * Método responsável por retornar os itens do pedido
     * @return ItemPedido[]
     */
    public function getItens(){
        //CARREGA OS ITENS DO BANCO SOB DEMANDA
        if($this->itens === null){
            $this->itens = $this->id ? ItemPedido::getItensByPedido($this->id) : [];
        }

        return $this->itens;
    }

    /**
     * Método responsável por retornar os pagamentos do pedido
     * @return Pagamento[]
     */
    public function getPagamentos(){
        //CARREGA OS PAGAMENTOS DO BANCO SOB DEMANDA
        if($this->pagamentos === null){
            $this->pagamentos = $this->id ? Pagamento::getPagamentosByPedido($this->id) : [];
        }

        return $this->pagamentos;
    }

    /**
     * Método responsável por adicionar um item ao pedido
     * SOBRECARGA (simulada com parâmetros variádicos):
     *   adicionarItem(Produto $produto)
     *   adicionarItem(Produto $produto, int $quantidade)
     *   adicionarItem(Produto $produto, int $quantidade, string $observacao)
     *   adicionarItem(array ['produtoId' => 1, 'quantidade' => 2, 'observacao' => '...'])
     * @param mixed ...$args
     * @return ItemPedido
     */
    public function adicionarItem(...$args){
        //VALIDA SE O PEDIDO PODE SER ALTERADO (RN08)
        $this->validarAlteravel();

        //IDENTIFICA A FORMA DE CHAMADA
        if(isset($args[0]) and $args[0] instanceof Produto){
            $obProduto  = $args[0];
            $quantidade = $args[1] ?? 1;
            $observacao = $args[2] ?? null;

        }elseif(isset($args[0]) and is_array($args[0])){
            $obProduto  = Produto::getProdutoById($args[0]['produtoId'] ?? 0);
            $quantidade = $args[0]['quantidade'] ?? 1;
            $observacao = $args[0]['observacao'] ?? null;

            //VALIDA SE O PRODUTO EXISTE
            if(!$obProduto instanceof Produto){
                throw new RegistroNaoEncontradoException("Produto não encontrado.");
            }

        }else{
            throw new DadoInvalidoException("Informe o produto a ser adicionado.");
        }

        //VALIDA SE O PRODUTO ESTÁ ATIVO (RN10)
        if($obProduto->status != 'ativo'){
            throw new DadoInvalidoException("O produto '".$obProduto->nome."' não está disponível.");
        }

        //NORMALIZA A OBSERVAÇÃO
        $observacao = strlen(trim((string)$observacao)) ? mb_substr(trim($observacao), 0, 150) : null;

        //VALIDA O ESTOQUE CONSIDERANDO O QUE JÁ ESTÁ NO PEDIDO (RN02)
        $quantidadeTotal = $this->getQuantidadeProduto($obProduto->id) + (int)$quantidade;
        if(!$obProduto->possuiEstoque($quantidadeTotal)){
            throw new DadoInvalidoException("Estoque insuficiente para o produto '".$obProduto->nome."'. Disponível: ".(int)$obProduto->estoque.".");
        }

        //SE JÁ EXISTE O MESMO PRODUTO COM A MESMA OBSERVAÇÃO, SOMA A QUANTIDADE
        foreach($this->getItens() as $obItem){
            if($obItem->produtoId == $obProduto->id and $obItem->observacao == $observacao){
                $obItem->alterarQuantidade($obItem->quantidade + (int)$quantidade);
                return $obItem;
            }
        }

        //NOVO ITEM (O PREÇO É CONGELADO NO MOMENTO DA VENDA)
        $obItem = new ItemPedido;
        $obItem->produtoId     = $obProduto->id;
        $obItem->nomeProduto   = $obProduto->nome;
        $obItem->precoUnitario = (float)$obProduto->preco;
        $obItem->observacao    = $observacao;
        $obItem->alterarQuantidade($quantidade);

        //ADICIONA À COMPOSIÇÃO
        $this->itens[] = $obItem;
        return $obItem;
    }

    /**
     * Método responsável por retornar a quantidade de um produto já incluída no pedido
     * @param  integer $produtoId
     * @return integer
     */
    protected function getQuantidadeProduto($produtoId){
        $quantidade = 0;
        foreach($this->getItens() as $obItem){
            if($obItem->produtoId == $produtoId) $quantidade += (int)$obItem->quantidade;
        }
        return $quantidade;
    }

    /**
     * Método responsável por remover um produto do pedido
     * @param integer $produtoId
     */
    public function removerItem($produtoId){
        //VALIDA SE O PEDIDO PODE SER ALTERADO
        $this->validarAlteravel();

        //REMOVE OS ITENS DO PRODUTO
        $this->itens = array_values(array_filter($this->getItens(), function($obItem) use ($produtoId){
            return $obItem->produtoId != $produtoId;
        }));
    }

    /**
     * Método responsável por remover todos os itens do pedido
     */
    public function limparItens(){
        //VALIDA SE O PEDIDO PODE SER ALTERADO
        $this->validarAlteravel();

        $this->itens = [];
    }

    /**
     * Método responsável por calcular o subtotal (soma dos itens)
     * @return float
     */
    public function calcularSubtotal(){
        $subtotal = 0;
        foreach($this->getItens() as $obItem){
            $subtotal += $obItem->calcularSubtotal();
        }
        return round($subtotal, 2);
    }

    /**
     * Método responsável por calcular o valor do desconto em R$ sobre o subtotal atual
     * @return float
     */
    public function calcularDesconto(){
        //SUBTOTAL ATUAL
        $subtotal = $this->calcularSubtotal();

        //CALCULA DE ACORDO COM O TIPO DO DESCONTO
        switch($this->descontoTipo){
            case 'valor':
                $desconto = (float)$this->descontoValor;
                break;
            case 'percentual':
                $desconto = $subtotal * ((float)$this->descontoValor / 100);
                break;
            case 'cupom':
                $obCupom  = Cupom::getCupomById($this->cupomId);
                $desconto = $obCupom instanceof Cupom ? $obCupom->calcularDesconto($subtotal) : 0;
                break;
            default:
                $desconto = 0;
        }

        //O DESCONTO NUNCA É NEGATIVO NEM MAIOR QUE O SUBTOTAL (RN07)
        return round(max(0, min($desconto, $subtotal)), 2);
    }

    /**
     * Método responsável por recalcular e retornar o total do pedido
     * @return float
     */
    public function calcularTotal(){
        $this->subtotal = $this->calcularSubtotal();
        $this->desconto = $this->calcularDesconto();
        $this->taxa     = round($this->calcularTaxa(), 2);
        $this->total    = round($this->subtotal - $this->desconto + $this->taxa, 2);

        return $this->total;
    }

    /**
     * Método responsável por aplicar um desconto ao pedido (substitui o desconto anterior - RN07)
     * SOBRECARGA (simulada com parâmetros variádicos):
     *   aplicarDesconto(Cupom $cupom)
     *   aplicarDesconto(float $valor)                -> desconto em R$
     *   aplicarDesconto(float $valor, 'percentual')  -> desconto em %
     * @param  mixed ...$args
     * @return float Valor do desconto em R$
     */
    public function aplicarDesconto(...$args){
        //VALIDA SE O PEDIDO PODE SER ALTERADO
        $this->validarAlteravel();

        //NÃO HÁ DESCONTO SEM ITENS
        if(!count($this->getItens())){
            throw new EstadoInvalidoException("Adicione itens ao pedido antes de aplicar um desconto.");
        }

        //DESCONTO POR CUPOM
        if(isset($args[0]) and $args[0] instanceof Cupom){
            $obCupom = $args[0];

            //VALIDA O CUPOM
            if(!$obCupom->isValido()){
                throw new DadoInvalidoException("O cupom '".$obCupom->codigo."' está expirado ou inativo.", null, 'codigo');
            }

            $this->descontoTipo  = 'cupom';
            $this->descontoValor = (float)$obCupom->valor;
            $this->cupomId       = $obCupom->id;

        //DESCONTO EM VALOR OU PERCENTUAL
        }elseif(isset($args[0]) and is_numeric($args[0])){
            $valor = (float)$args[0];
            $tipo  = Validador::opcao($args[1] ?? 'valor', ['valor', 'percentual'], 'tipo');

            //VALIDA O VALOR DO DESCONTO
            if($valor <= 0){
                throw new DadoInvalidoException("O desconto deve ser maior que zero.", null, 'valor');
            }
            if($tipo == 'percentual' and $valor > 100){
                throw new DadoInvalidoException("O desconto percentual não pode passar de 100%.", null, 'valor');
            }
            if($tipo == 'valor' and $valor > $this->calcularSubtotal()){
                throw new DadoInvalidoException("O desconto não pode ser maior que o subtotal (R$ ".number_format($this->calcularSubtotal(), 2, ',', '.').").", null, 'valor');
            }

            $this->descontoTipo  = $tipo;
            $this->descontoValor = $valor;
            $this->cupomId       = null;

        }else{
            throw new DadoInvalidoException("Desconto inválido.");
        }

        //RECALCULA OS VALORES
        $this->calcularTotal();

        //NÃO PERMITE DESCONTO MENOR QUE O JÁ PAGO
        if($this->total < $this->getValorPago()){
            throw new EstadoInvalidoException("O total com desconto ficaria menor que o valor já pago.");
        }

        return $this->desconto;
    }

    /**
     * Método responsável por remover o desconto do pedido
     */
    public function removerDesconto(){
        //VALIDA SE O PEDIDO PODE SER ALTERADO
        $this->validarAlteravel();

        $this->descontoTipo  = null;
        $this->descontoValor = 0;
        $this->cupomId       = null;
        $this->calcularTotal();
    }

    /**
     * Método responsável por registrar um pagamento no pedido (RN05)
     * @param Pagamento $obPagamento
     * @return Pagamento
     */
    public function registrarPagamento(Pagamento $obPagamento){
        //VALIDA SE O PEDIDO PODE SER ALTERADO
        $this->validarAlteravel();

        //PEDIDO ONLINE PRECISA SER ACEITO ANTES (RN14)
        if($this->status == self::STATUS_PENDENTE){
            throw new EstadoInvalidoException("Aceite o pedido online antes de registrar o pagamento.");
        }

        //NÃO HÁ PAGAMENTO SEM ITENS (RN01)
        if(!count($this->getItens())){
            throw new EstadoInvalidoException("Não é possível pagar um pedido sem itens.");
        }

        //RECALCULA OS VALORES E OBTÉM O RESTANTE
        $this->calcularTotal();
        $restante = $this->getValorRestante();
        if($restante <= 0){
            throw new EstadoInvalidoException("Este pedido já está totalmente pago.");
        }

        //PROCESSA O PAGAMENTO (POLIMORFISMO: DINHEIRO, CARTÃO OU PIX)
        $obPagamento->pedidoId = $this->id;
        $obPagamento->processar($restante);
        $obPagamento->cadastrar();

        //ADICIONA À COMPOSIÇÃO
        $this->getPagamentos();
        $this->pagamentos[] = $obPagamento;

        return $obPagamento;
    }

    /**
     * Método responsável por retornar o valor já pago
     * @return float
     */
    public function getValorPago(){
        $pago = 0;
        foreach($this->getPagamentos() as $obPagamento){
            $pago += (float)$obPagamento->valor;
        }
        return round($pago, 2);
    }

    /**
     * Método responsável por retornar o valor que falta pagar
     * @return float
     */
    public function getValorRestante(){
        return round(max(0, (float)$this->total - $this->getValorPago()), 2);
    }

    /**
     * Método responsável por colocar o pedido em espera (hold)
     */
    public function colocarEmEspera(){
        //VALIDA O ESTADO ATUAL
        $this->validarAlteravel();
        if($this->status != self::STATUS_ABERTO){
            throw new EstadoInvalidoException("Somente pedidos abertos podem ser colocados em espera.");
        }

        $this->status = self::STATUS_EM_ESPERA;
    }

    /**
     * Método responsável por retomar um pedido em espera
     */
    public function retomar(){
        //VALIDA O ESTADO ATUAL
        if($this->status != self::STATUS_EM_ESPERA){
            throw new EstadoInvalidoException("Somente pedidos em espera podem ser retomados.");
        }

        $this->status = self::STATUS_ABERTO;
    }

    /**
     * Método responsável por finalizar o pedido (RN01, RN02, RN05)
     */
    public function finalizar(){
        //VALIDA SE O PEDIDO PODE SER ALTERADO
        $this->validarAlteravel();

        //PEDIDO ONLINE PRECISA SER ACEITO ANTES
        if($this->status == self::STATUS_PENDENTE){
            throw new EstadoInvalidoException("Aceite o pedido online antes de finalizá-lo.");
        }

        //VALIDA SE POSSUI ITENS (RN01)
        if(!count($this->getItens())){
            throw new EstadoInvalidoException("Não é possível finalizar um pedido sem itens.");
        }

        //VALIDA SE O VALOR FOI QUITADO (RN05)
        $this->calcularTotal();
        $restante = $this->getValorRestante();
        if($restante > 0){
            throw new EstadoInvalidoException("Ainda faltam R$ ".number_format($restante, 2, ',', '.')." para finalizar o pedido.");
        }

        //DÁ BAIXA NO ESTOQUE DOS PRODUTOS (RN02)
        foreach($this->getItens() as $obItem){
            $obProduto = Produto::getProdutoById($obItem->produtoId);
            if($obProduto instanceof Produto){
                $obProduto->baixarEstoque($obItem->quantidade);
            }
        }

        //ALTERA O STATUS
        $this->status       = self::STATUS_FINALIZADO;
        $this->finalizadoEm = date('Y-m-d H:i:s');
        $this->salvar(false);
    }

    /**
     * Método responsável por cancelar o pedido (RN09 - permissão validada no controller/middleware)
     */
    public function cancelar(){
        //VALIDA O ESTADO ATUAL
        if(in_array($this->status, [self::STATUS_FINALIZADO, self::STATUS_CANCELADO])){
            throw new EstadoInvalidoException("Este pedido já está ".self::STATUS_DESCRICAO[$this->status]." e não pode ser cancelado.");
        }

        //ALTERA O STATUS
        $this->status       = self::STATUS_CANCELADO;
        $this->finalizadoEm = date('Y-m-d H:i:s');
        $this->salvar(false);
    }

    /**
     * Método responsável por validar se o pedido ainda pode ser alterado (RN08)
     */
    protected function validarAlteravel(){
        if(in_array($this->status, [self::STATUS_FINALIZADO, self::STATUS_CANCELADO])){
            throw new EstadoInvalidoException("Este pedido está ".mb_strtolower(self::STATUS_DESCRICAO[$this->status])." e não pode ser alterado.");
        }
    }

    /**
     * Método responsável por retornar os dados a serem persistidos
     * (as subclasses acrescentam os próprios campos)
     * @return array
     */
    protected function getDadosPersistencia(){
        return [
            'status'        => $this->status,
            'clienteId'     => $this->clienteId ?: null,
            'usuarioId'     => $this->usuarioId ?: null,
            'cupomId'       => $this->cupomId ?: null,
            'descontoTipo'  => $this->descontoTipo ?: null,
            'descontoValor' => (float)$this->descontoValor,
            'desconto'      => (float)$this->desconto,
            'subtotal'      => (float)$this->subtotal,
            'taxa'          => (float)$this->taxa,
            'total'         => (float)$this->total,
            'observacao'    => $this->observacao,
            'atualizadoEm'  => $this->atualizadoEm,
            'finalizadoEm'  => $this->finalizadoEm
        ];
    }

    /**
     * Método responsável por retornar os dados específicos do tipo de pedido para exibição
     * @return array
     */
    protected function getDadosEspecificos(){
        return [];
    }

    /**
     * Método responsável por salvar (cadastrar ou atualizar) o pedido e seus itens
     * @param  boolean $salvarItens
     * @return boolean
     */
    public function salvar($salvarItens = true){
        //RECALCULA OS VALORES ENQUANTO O PEDIDO PODE SER ALTERADO
        if(!in_array($this->status, [self::STATUS_FINALIZADO, self::STATUS_CANCELADO])){
            $this->calcularTotal();
        }

        //DATA DE ATUALIZAÇÃO
        $this->atualizadoEm = date('Y-m-d H:i:s');
        $dados = $this->getDadosPersistencia();

        //CADASTRA OU ATUALIZA
        if(!$this->id){
            $this->criadoEm = date('Y-m-d H:i:s');
            $this->codigo   = self::gerarCodigo();
            $this->id = (new Database('pedidos'))->insert(array_merge($dados, [
                'codigo'   => $this->codigo,
                'tipo'     => $this->tipo,
                'criadoEm' => $this->criadoEm
            ]));
        }else{
            (new Database('pedidos'))->update('id = ?', $dados, [$this->id]);
        }

        //REGRAVA OS ITENS (COMPOSIÇÃO: OS ITENS PERTENCEM AO PEDIDO)
        if($salvarItens and $this->itens !== null){
            ItemPedido::excluirByPedido($this->id);
            foreach($this->itens as $obItem){
                $obItem->pedidoId = $this->id;
                $obItem->cadastrar();
            }
        }

        return true;
    }

    /**
     * Método responsável por retornar os dados do pedido em array
     * @param  boolean $completo Inclui itens e pagamentos
     * @return array
     */
    public function toArray($completo = true){
        //CLIENTE
        $obCliente = $this->clienteId ? Cliente::getClienteById($this->clienteId) : null;

        //DADOS COMUNS A TODOS OS PEDIDOS
        $dados = [
            'id'              => (int)$this->id,
            'codigo'          => $this->codigo,
            'tipo'            => $this->tipo,
            'origem'          => $this->getOrigem(),
            'status'          => $this->status,
            'statusDescricao' => self::STATUS_DESCRICAO[$this->status] ?? $this->status,
            'cliente'         => $obCliente instanceof Cliente ? ['id' => (int)$obCliente->id, 'nome' => $obCliente->nome, 'telefone' => $obCliente->telefone] : null,
            'usuarioId'       => $this->usuarioId ? (int)$this->usuarioId : null,
            'observacao'      => $this->observacao,
            'quantidadeItens' => array_sum(array_map(fn($obItem) => (int)$obItem->quantidade, $this->getItens())),
            'valores'         => [
                'subtotal'      => (float)$this->subtotal,
                'descontoTipo'  => $this->descontoTipo,
                'descontoValor' => (float)$this->descontoValor,
                'desconto'      => (float)$this->desconto,
                'cupom'         => $this->cupomId ? (Cupom::getCupomById($this->cupomId)->codigo ?? null) : null,
                'taxa'          => (float)$this->taxa,
                'taxaDescricao' => $this->getDescricaoTaxa(),
                'total'         => (float)$this->total,
                'pago'          => $this->getValorPago(),
                'restante'      => $this->getValorRestante()
            ],
            'criadoEm'        => $this->criadoEm,
            'finalizadoEm'    => $this->finalizadoEm
        ];

        //ITENS E PAGAMENTOS
        if($completo){
            $dados['itens']      = array_map(fn($obItem) => $obItem->toArray(), $this->getItens());
            $dados['pagamentos'] = array_map(fn($obPagamento) => $obPagamento->toArray(), $this->getPagamentos());
        }

        //DADOS ESPECÍFICOS DO TIPO (POLIMORFISMO)
        return array_merge($dados, $this->getDadosEspecificos());
    }

    /**
     * Método responsável por gerar um código público único para o pedido
     * @return string
     */
    protected static function gerarCodigo(){
        do{
            $codigo = (string)random_int(1000000, 9999999);
        }while(self::getPedidoByCodigo($codigo));

        return $codigo;
    }

    /**
     * Método responsável por instanciar a subclasse correta a partir do tipo (FÁBRICA)
     * @param  array $dados
     * @return Pedido
     */
    public static function instanciar($dados){
        //ESCOLHE A CLASSE PELO TIPO DO PEDIDO
        switch($dados['tipo'] ?? ''){
            case 'mesa':   $obPedido = new PedidoMesa;   break;
            case 'balcao': $obPedido = new PedidoBalcao; break;
            case 'online': $obPedido = new PedidoOnline; break;
            default:
                throw new DadoInvalidoException("Tipo de pedido inválido. Opções: mesa, balcao, online.", null, 'tipo');
        }

        //PREENCHE OS ATRIBUTOS
        foreach($dados as $atributo => $valor){
            if(property_exists($obPedido, $atributo) and !in_array($atributo, ['itens', 'pagamentos'])){
                $obPedido->$atributo = $valor;
            }
        }

        return $obPedido;
    }

    /**
     * Método responsável por retornar um pedido com base em seu ID
     * @param  integer $id
     * @return Pedido|null
     */
    public static function getPedidoById($id){
        $dados = self::getPedidos('id = ?', null, null, '*', [(int)$id])->fetch(\PDO::FETCH_ASSOC);
        return $dados ? self::instanciar($dados) : null;
    }

    /**
     * Método responsável por retornar um pedido com base em seu código
     * @param  string $codigo
     * @return Pedido|null
     */
    public static function getPedidoByCodigo($codigo){
        $dados = self::getPedidos('codigo = ?', null, null, '*', [(string)$codigo])->fetch(\PDO::FETCH_ASSOC);
        return $dados ? self::instanciar($dados) : null;
    }

    /**
     * Método responsável por retornar pedidos
     * @param  string $where
     * @param  string $order
     * @param  string $limit
     * @param  string $fields
     * @param  array  $params
     * @return PDOStatement
     */
    public static function getPedidos($where = null, $order = null, $limit = null, $fields = '*', $params = []){
        return (new Database('pedidos'))->select($where, $order, $limit, $fields, $params);
    }
}
