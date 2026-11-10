<?php

namespace App\Model\Entity;

use \App\Utils\Validador;
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
            'taxaEntrega'     => (float)$this->taxaEntrega
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
            ]
        ];
    }
}
