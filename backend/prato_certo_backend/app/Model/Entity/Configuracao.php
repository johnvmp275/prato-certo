<?php

namespace App\Model\Entity;

use \App\DatabaseManager\Database;
use \App\Exception\FalhaPersistenciaException;

class Configuracao{

    /**
     * Id do registro (sempre 1)
     * @var integer
     */
    public $id = 1;

    /**
     * Nome do restaurante
     * @var string
     */
    public $nomeRestaurante;

    /**
     * Telefone do restaurante
     * @var string
     */
    public $telefone;

    /**
     * Endereço do restaurante
     * @var string
     */
    public $endereco;

    /**
     * Taxa de serviço em % (pedidos de mesa)
     * @var float
     */
    public $taxaServico = 10;

    /**
     * Taxa de entrega em R$ (pedidos online para entrega)
     * @var float
     */
    public $taxaEntrega = 0;

    /**
     * Indica se o cardápio online está recebendo pedidos
     * @var boolean
     */
    public $cardapioAberto = 1;

    /**
     * Cache da configuração carregada na requisição
     * @var Configuracao|null
     */
    private static $instancia = null;

    /**
     * Método responsável por atualizar os dados do banco com a instância atual
     * @return boolean
     */
    public function atualizar(){
        //ATUALIZA A CONFIGURAÇÃO NO BANCO DE DADOS
        (new Database('configuracoes'))->update('id = 1', [
            'nomeRestaurante' => $this->nomeRestaurante,
            'telefone'        => $this->telefone,
            'endereco'        => $this->endereco,
            'taxaServico'     => $this->taxaServico,
            'taxaEntrega'     => $this->taxaEntrega,
            'cardapioAberto'  => $this->cardapioAberto ? 1 : 0
        ]);

        //ATUALIZA O CACHE
        self::$instancia = $this;
        return true;
    }

    /**
     * Método responsável por retornar os dados da configuração em array
     * @return array
     */
    public function toArray(){
        return [
            'nomeRestaurante' => $this->nomeRestaurante,
            'telefone'        => $this->telefone,
            'endereco'        => $this->endereco,
            'taxaServico'     => (float)$this->taxaServico,
            'taxaEntrega'     => (float)$this->taxaEntrega,
            'cardapioAberto'  => (bool)$this->cardapioAberto
        ];
    }

    /**
     * Método responsável por retornar a configuração do restaurante
     * @return Configuracao
     */
    public static function getConfiguracao(){
        //RETORNA O CACHE QUANDO JÁ CARREGADO
        if(self::$instancia) return self::$instancia;

        //BUSCA NO BANCO
        $obConfiguracao = (new Database('configuracoes'))->select('id = 1')->fetchObject(self::class);
        if(!$obConfiguracao instanceof Configuracao){
            throw new FalhaPersistenciaException("Não foi possível carregar as configurações do restaurante.");
        }

        return self::$instancia = $obConfiguracao;
    }
}
