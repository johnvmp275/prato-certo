<?php

namespace App\Model\Entity;

class PedidoMesa extends Pedido{

    /**
     * Tipo do pedido
     * @var string
     */
    public $tipo = 'mesa';

    /**
     * Id da mesa ocupada pelo pedido
     * @var integer
     */
    public $mesaId;

    /**
     * Número de pessoas na mesa
     * @var integer
     */
    public $numPessoas = 1;

    /**
     * Indica se cobra a taxa de serviço (RN06)
     * @var boolean
     */
    public $cobrarServico = 1;

    /**
     * Cache da mesa do pedido
     * @var Mesa|null
     */
    private $obMesa = null;

    /**
     * Método responsável por abrir a comanda em uma mesa (RN03, RN04)
     * @param Mesa    $obMesa
     * @param integer $numPessoas
     */
    public function abrir(Mesa $obMesa, $numPessoas){
        //VALIDA O NÚMERO DE PESSOAS
        $obMesa->validarPessoas($numPessoas);

        //OCUPA A MESA (FALHA SE JÁ ESTIVER OCUPADA)
        $obMesa->ocupar();

        $this->obMesa     = $obMesa;
        $this->mesaId     = $obMesa->id;
        $this->numPessoas = (int)$numPessoas;
    }

    /**
     * Método responsável por retornar a mesa do pedido
     * @return Mesa|null
     */
    public function getMesa(){
        if(!$this->obMesa and $this->mesaId){
            $this->obMesa = Mesa::getMesaById($this->mesaId) ?: null;
        }
        return $this->obMesa;
    }

    /**
     * Método responsável por calcular a taxa de serviço sobre o subtotal com desconto
     * @return float
     */
    public function calcularTaxa(){
        //TAXA DE SERVIÇO OPCIONAL
        if(!$this->cobrarServico) return 0;

        //PERCENTUAL CONFIGURADO PELO RESTAURANTE
        $percentual = (float)Configuracao::getConfiguracao()->taxaServico;

        return round(($this->subtotal - $this->desconto) * ($percentual / 100), 2);
    }

    /**
     * Método responsável por retornar a descrição da taxa
     * @return string
     */
    public function getDescricaoTaxa(){
        if(!$this->cobrarServico) return 'Taxa de serviço (isenta)';
        return 'Taxa de serviço ('.(float)Configuracao::getConfiguracao()->taxaServico.'%)';
    }

    /**
     * Método responsável por retornar a origem do pedido
     * @return string
     */
    public function getOrigem(){
        $obMesa = $this->getMesa();
        return 'Mesa '.str_pad($obMesa ? $obMesa->numero : '?', 2, '0', STR_PAD_LEFT);
    }

    /**
     * Método responsável por finalizar o pedido e liberar a mesa (sobrescrita)
     */
    public function finalizar(){
        parent::finalizar();
        $this->liberarMesa();
    }

    /**
     * Método responsável por cancelar o pedido e liberar a mesa (sobrescrita)
     */
    public function cancelar(){
        parent::cancelar();
        $this->liberarMesa();
    }

    /**
     * Método responsável por liberar a mesa do pedido
     */
    private function liberarMesa(){
        $obMesa = $this->getMesa();
        if($obMesa and !$obMesa->isLivre()) $obMesa->liberar();
    }

    /**
     * Método responsável por retornar os dados a serem persistidos
     * @return array
     */
    protected function getDadosPersistencia(){
        return array_merge(parent::getDadosPersistencia(), [
            'mesaId'        => $this->mesaId,
            'numPessoas'    => (int)$this->numPessoas,
            'cobrarServico' => $this->cobrarServico ? 1 : 0
        ]);
    }

    /**
     * Método responsável por retornar os dados específicos da mesa
     * @return array
     */
    protected function getDadosEspecificos(){
        $obMesa = $this->getMesa();
        return [
            'mesa'          => $obMesa ? ['id' => (int)$obMesa->id, 'numero' => (int)$obMesa->numero, 'capacidade' => (int)$obMesa->capacidade] : null,
            'numPessoas'    => (int)$this->numPessoas,
            'cobrarServico' => (bool)$this->cobrarServico
        ];
    }
}
