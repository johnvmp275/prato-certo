<?php

namespace App\Model\Entity;

class PedidoBalcao extends Pedido{

    /**
     * Tipo do pedido
     * @var string
     */
    public $tipo = 'balcao';

    /**
     * Método responsável por calcular a taxa (balcão não possui taxa)
     * @return float
     */
    public function calcularTaxa(){
        return 0;
    }

    /**
     * Método responsável por retornar a descrição da taxa
     * @return string
     */
    public function getDescricaoTaxa(){
        return 'Sem taxa';
    }

    /**
     * Método responsável por retornar a origem do pedido
     * @return string
     */
    public function getOrigem(){
        return 'Balcão';
    }
}
