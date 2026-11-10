<?php

namespace App\Model\Entity;

use \App\Utils\Validador;
use \App\Exception\DadoInvalidoException;
use \App\Exception\EstadoInvalidoException;

class PedidoOnline extends Pedido{

    /**
     * Tipo do pedido
     * @var string
     */
    public $tipo = 'online';

    /**
     * Pedidos online chegam pendentes até o atendente aceitar
     * @var string
     */
    public $status = self::STATUS_PENDENTE;

    /**
     * Tipo de entrega (retirada | entrega)
     * @var string
     */
    public $tipoEntrega = 'retirada';

    /**
     * Nome de quem fez o pedido
     * @var string
     */
    public $nomeContato;

    /**
     * Telefone de contato (somente dígitos)
     * @var string
     */
    public $telefoneContato;

    /**
     * Endereço de entrega
     * @var string|null
     */
    public $enderecoEntrega;

    /**
     * Taxa de entrega no momento do pedido
     * @var float
     */
    public $taxaEntrega = 0;

    /**
     * Forma de pagamento escolhida pelo cliente no carrinho (dinheiro | cartao | pix)
     * É só a intenção: o pagamento é registrado pelo atendente no caixa
     * @var string|null
     */
    public $formaPagamento;

    /**
     * Valor em dinheiro que o cliente vai entregar (para o atendente separar o troco)
     * @var float|null
     */
    public $trocoPara;

    /**
     * Descrições das formas de pagamento
     * @var array
     */
    const FORMAS_PAGAMENTO = [
        'dinheiro' => 'Dinheiro',
        'cartao'   => 'Cartão',
        'pix'      => 'PIX'
    ];

    /**
     * Método responsável por validar a forma de pagamento escolhida
     * (chamar depois de adicionar os itens, pois o troco é comparado com o total)
     */
    public function validarPagamento(){
        //FORMA OBRIGATÓRIA
        $this->formaPagamento = Validador::opcao(Validador::obrigatorio($this->formaPagamento, 'formaPagamento', 'Forma de pagamento'), array_keys(self::FORMAS_PAGAMENTO), 'formaPagamento');

        //TROCO SÓ FAZ SENTIDO NO DINHEIRO (OPCIONAL: VAZIO = NÃO PRECISA DE TROCO)
        if($this->formaPagamento != 'dinheiro' or !strlen(trim((string)$this->trocoPara))){
            $this->trocoPara = null;
            return;
        }

        //O VALOR ENTREGUE PRECISA COBRIR O TOTAL DO PEDIDO
        $this->calcularTotal();
        $this->trocoPara = round(Validador::numero($this->trocoPara, 'trocoPara', 0, 'Troco para'), 2);
        if($this->trocoPara < $this->total){
            throw new DadoInvalidoException("O troco deve ser para um valor maior que o total (R$ ".number_format($this->total, 2, ',', '.').").", null, 'trocoPara');
        }
    }

    /**
     * Método responsável por validar os dados de contato e entrega (RN11)
     */
    public function validarContato(){
        //DADOS OBRIGATÓRIOS
        $this->nomeContato     = Validador::tamanho(Validador::obrigatorio($this->nomeContato, 'nomeContato', 'Nome'), 'nomeContato', 2, 120);
        $this->telefoneContato = Validador::telefone(Validador::obrigatorio($this->telefoneContato, 'telefoneContato', 'Telefone'), 'telefoneContato');
        $this->tipoEntrega     = Validador::opcao($this->tipoEntrega, ['retirada', 'entrega'], 'tipoEntrega');

        //ENDEREÇO OBRIGATÓRIO PARA ENTREGA
        if($this->tipoEntrega == 'entrega'){
            $this->enderecoEntrega = Validador::tamanho(Validador::obrigatorio($this->enderecoEntrega, 'enderecoEntrega', 'Endereço de entrega'), 'enderecoEntrega', 5, 255);
        }else{
            $this->enderecoEntrega = null;
        }
    }

    /**
     * Método responsável por aceitar o pedido online (pendente -> aberto)
     * @param Usuario $obUsuario
     */
    public function aceitar(Usuario $obUsuario){
        //VALIDA O ESTADO ATUAL
        if($this->status != self::STATUS_PENDENTE){
            throw new EstadoInvalidoException("Este pedido online já foi aceito ou encerrado.");
        }

        $this->status    = self::STATUS_ABERTO;
        $this->usuarioId = $obUsuario->id;
    }

    /**
     * Método responsável por calcular a taxa de entrega (somente para entrega)
     * @return float
     */
    public function calcularTaxa(){
        return $this->tipoEntrega == 'entrega' ? (float)$this->taxaEntrega : 0;
    }

    /**
     * Método responsável por retornar a descrição da taxa
     * @return string
     */
    public function getDescricaoTaxa(){
        return $this->tipoEntrega == 'entrega' ? 'Taxa de entrega' : 'Sem taxa (retirada)';
    }

    /**
     * Método responsável por retornar a origem do pedido
     * @return string
     */
    public function getOrigem(){
        return $this->tipoEntrega == 'entrega' ? 'Online · Entrega' : 'Online · Retirada';
    }

    /**
     * Método responsável por retornar os dados a serem persistidos
     * @return array
     */
    protected function getDadosPersistencia(){
        return array_merge(parent::getDadosPersistencia(), [
            'tipoEntrega'     => $this->tipoEntrega,
            'nomeContato'     => $this->nomeContato,
            'telefoneContato' => $this->telefoneContato,
            'enderecoEntrega' => $this->enderecoEntrega,
            'taxaEntrega'     => (float)$this->taxaEntrega,
            'formaPagamento'  => $this->formaPagamento ?: null,
            'trocoPara'       => strlen((string)$this->trocoPara) ? (float)$this->trocoPara : null
        ]);
    }

    /**
     * Método responsável por retornar os dados específicos do pedido online
     * @return array
     */
    protected function getDadosEspecificos(){
        return [
            'tipoEntrega' => $this->tipoEntrega,
            'contato'     => [
                'nome'     => $this->nomeContato,
                'telefone' => $this->telefoneContato,
                'endereco' => $this->enderecoEntrega
            ],
            'pagamentoPrevisto' => $this->getPagamentoPrevisto()
        ];
    }

    /**
     * Método responsável por retornar a forma de pagamento escolhida pelo cliente
     * (null nos pedidos feitos antes desta opção existir)
     * @return array|null
     */
    public function getPagamentoPrevisto(){
        if(!$this->formaPagamento) return null;

        return [
            'forma'     => $this->formaPagamento,
            'descricao' => self::FORMAS_PAGAMENTO[$this->formaPagamento] ?? $this->formaPagamento,
            'trocoPara' => strlen((string)$this->trocoPara) ? (float)$this->trocoPara : null
        ];
    }
}
