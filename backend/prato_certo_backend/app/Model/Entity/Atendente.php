<?php

namespace App\Model\Entity;

class Atendente extends Usuario{

    /**
     * Perfil do usuário
     * @var string
     */
    public $perfil = 'atendente';

    /**
     * Método responsável por retornar as permissões do atendente (operação do PDV)
     * @return array
     */
    public function getPermissoes(){
        return [
            'dashboard',
            'pdv',
            'pedidos',
            'mesas',
            'clientes',
            'produtos'
        ];
    }

    /**
     * Método responsável por retornar o nome do perfil para exibição
     * @return string
     */
    public function getPerfilDescricao(){
        return 'Atendente';
    }
}
