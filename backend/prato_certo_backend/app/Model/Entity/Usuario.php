<?php

namespace App\Model\Entity;

use \App\DatabaseManager\Database;
use \App\Exception\DadoInvalidoException;

abstract class Usuario{

    /**
     * Id do usuário
     * @var integer
     */
    public $id;

    /**
     * Nome do usuário
     * @var string
     */
    public $nome;

    /**
     * E-mail do usuário (login)
     * @var string
     */
    public $email;

    /**
     * Senha do usuário (hash bcrypt)
     * @var string
     */
    protected $senha;

    /**
     * Perfil do usuário (administrador | atendente)
     * @var string
     */
    public $perfil;

    /**
     * Status do usuário (ativo | inativo)
     * @var string
     */
    public $status = 'ativo';

    /**
     * Data de cadastro do usuário
     * @var string
     */
    public $criadoEm;

    /**
     * Método responsável por retornar as permissões do perfil
     * (POLIMORFISMO: cada perfil implementa as próprias permissões)
     * @return array
     */
    abstract public function getPermissoes();

    /**
     * Método responsável por retornar o nome do perfil para exibição
     * @return string
     */
    abstract public function getPerfilDescricao();

    /**
     * Método responsável por verificar se o usuário pode acessar um recurso
     * @param  string $recurso
     * @return boolean
     */
    public function podeAcessar($recurso){
        //PERMISSÕES DO PERFIL
        $permissoes = $this->getPermissoes();

        //ACESSO TOTAL
        if(in_array('*', $permissoes)) return true;

        //VERIFICA O RECURSO
        return in_array($recurso, $permissoes);
    }

    /**
     * Método responsável por definir a senha do usuário (gera o hash)
     * @param string $senha
     */
    public function definirSenha($senha){
        //VALIDA O TAMANHO MÍNIMO DA SENHA
        if(mb_strlen((string)$senha) < 6){
            throw new DadoInvalidoException("A senha deve ter no mínimo 6 caracteres.", null, 'senha');
        }

        //GERA O HASH DA SENHA
        $this->senha = password_hash($senha, PASSWORD_DEFAULT);
    }

    /**
     * Método responsável por verificar se a senha informada confere
     * @param  string $senha
     * @return boolean
     */
    public function verificarSenha($senha){
        return password_verify((string)$senha, (string)$this->senha);
    }

    /**
     * Método responsável por cadastrar a instância atual no banco de dados
     * @return boolean
     */
    public function cadastrar(){
        //DATA DE CADASTRO
        $this->criadoEm = date('Y-m-d H:i:s');

        //INSERE O USUÁRIO NO BANCO DE DADOS
        $this->id = (new Database('usuarios'))->insert([
            'nome'     => $this->nome,
            'email'    => $this->email,
            'senha'    => $this->senha,
            'perfil'   => $this->perfil,
            'status'   => $this->status,
            'criadoEm' => $this->criadoEm
        ]);

        //SUCESSO
        return true;
    }

    /**
     * Método responsável por atualizar os dados do banco com a instância atual
     * @return boolean
     */
    public function atualizar(){
        //ATUALIZA O USUÁRIO NO BANCO DE DADOS
        return (new Database('usuarios'))->update('id = ?', [
            'nome'   => $this->nome,
            'email'  => $this->email,
            'senha'  => $this->senha,
            'perfil' => $this->perfil,
            'status' => $this->status
        ], [$this->id]);
    }

    /**
     * Método responsável por inativar o usuário (não exclui para manter o histórico)
     * @return boolean
     */
    public function inativar(){
        $this->status = 'inativo';
        return $this->atualizar();
    }

    /**
     * Método responsável por retornar os dados públicos do usuário (sem a senha)
     * @return array
     */
    public function toArray(){
        return [
            'id'               => (int)$this->id,
            'nome'             => $this->nome,
            'email'            => $this->email,
            'perfil'           => $this->perfil,
            'perfilDescricao'  => $this->getPerfilDescricao(),
            'status'           => $this->status,
            'permissoes'       => $this->getPermissoes(),
            'criadoEm'         => $this->criadoEm
        ];
    }

    /**
     * Método responsável por instanciar a subclasse correta a partir do perfil (FÁBRICA)
     * @param  array $dados
     * @return Usuario
     */
    public static function instanciar($dados){
        //ESCOLHE A CLASSE PELO PERFIL
        $obUsuario = ($dados['perfil'] ?? '') == 'administrador' ? new Administrador : new Atendente;

        //PREENCHE OS ATRIBUTOS
        foreach($dados as $atributo => $valor){
            if(property_exists($obUsuario, $atributo)) $obUsuario->$atributo = $valor;
        }

        return $obUsuario;
    }

    /**
     * Método responsável por retornar um usuário com base em seu ID
     * @param  integer $id
     * @return Usuario|null
     */
    public static function getUsuarioById($id){
        $dados = self::getUsuarios('id = ?', null, null, '*', [(int)$id])->fetch(\PDO::FETCH_ASSOC);
        return $dados ? self::instanciar($dados) : null;
    }

    /**
     * Método responsável por retornar um usuário com base em seu e-mail
     * @param  string $email
     * @return Usuario|null
     */
    public static function getUsuarioByEmail($email){
        $dados = self::getUsuarios('email = ?', null, null, '*', [$email])->fetch(\PDO::FETCH_ASSOC);
        return $dados ? self::instanciar($dados) : null;
    }

    /**
     * Método responsável por retornar usuários
     * @param  string $where
     * @param  string $order
     * @param  string $limit
     * @param  string $fields
     * @param  array  $params
     * @return PDOStatement
     */
    public static function getUsuarios($where = null, $order = null, $limit = null, $fields = '*', $params = []){
        return (new Database('usuarios'))->select($where, $order, $limit, $fields, $params);
    }
}
