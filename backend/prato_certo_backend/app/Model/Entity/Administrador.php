<?php

namespace App\Model\Entity;

class Administrador extends Usuario{

    /**
     * Perfil do usuário
     * @var string
     */
    public $perfil = 'administrador';

    /**
     * Método responsável por retornar as permissões do administrador (acesso total)
     * @return array
     */
    public function getPermissoes(){
        return ['*'];
    }

    /**
     * Método responsável por retornar o nome do perfil para exibição
     * @return string
     */
    public function getPerfilDescricao(){
        return 'Administrador';
    }
}
