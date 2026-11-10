<?php

namespace App\Model\Entity;

use \App\DatabaseManager\Database;
use \App\Utils\Validador;
use \App\Exception\DadoInvalidoException;

class Cliente{

    /**
     * Id do cliente
     * @var integer
     */
    public $id;

    /**
     * Nome do cliente
     * @var string
     */
    public $nome;

    /**
     * Telefone do cliente (somente dígitos)
     * @var string
     */
    public $telefone;

    /**
     * E-mail do cliente
     * @var string
     */
    public $email;

    /**
     * CPF do cliente (somente dígitos)
     * @var string
     */
    public $documento;

    /**
     * Hash da senha da conta do cardápio online (null = cadastrado só pelo painel)
     * @var string|null
     */
    protected $senha;

    /**
     * Endereço do cliente
     * @var string
     */
    public $endereco;

    /**
     * Status do cliente (ativo | inativo)
     * @var string
     */
    public $status = 'ativo';

    /**
     * Data de cadastro
     * @var string
     */
    public $criadoEm;

    /**
     * Método responsável por validar o CPF do cliente (quando informado)
     * @return boolean
     */
    public function validarDocumento(){
        //CPF É OPCIONAL
        if(!strlen((string)$this->documento)){
            $this->documento = null;
            return true;
        }

        //VALIDA OS DÍGITOS VERIFICADORES
        if(!Validador::isCpfValido($this->documento)){
            throw new DadoInvalidoException("Informe um CPF válido.", null, 'documento');
        }

        //MANTÉM APENAS OS NÚMEROS
        $this->documento = preg_replace('/\D/', '', $this->documento);
        return true;
    }

    /**
     * Método responsável por definir a senha da conta do cliente (gera o hash)
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
        return $this->possuiConta() and password_verify((string)$senha, (string)$this->senha);
    }

    /**
     * Método responsável por verificar se o cliente já criou a conta do cardápio online
     * @return boolean
     */
    public function possuiConta(){
        return strlen((string)$this->senha) > 0;
    }

    /**
     * Método responsável por consultar o histórico de pedidos do cliente
     * @param  integer $limite
     * @return array
     */
    public function consultarHistorico($limite = 20){
        //HISTÓRICO
        $historico = [];

        //BUSCA OS PEDIDOS DO CLIENTE
        $results = Pedido::getPedidos('clienteId = ?', 'id DESC', (int)$limite, '*', [$this->id]);
        while($dados = $results->fetch(\PDO::FETCH_ASSOC)){
            $historico[] = Pedido::instanciar($dados)->toArray(false);
        }

        return $historico;
    }

    /**
     * Método responsável por cadastrar a instância atual no banco de dados
     * @return boolean
     */
    public function cadastrar(){
        //VALIDA O DOCUMENTO
        $this->validarDocumento();

        //DATA DE CADASTRO
        $this->criadoEm = date('Y-m-d H:i:s');

        //INSERE O CLIENTE NO BANCO DE DADOS
        $this->id = (new Database('clientes'))->insert([
            'nome'      => $this->nome,
            'telefone'  => $this->telefone,
            'email'     => $this->email,
            'documento' => $this->documento,
            'senha'     => $this->senha,
            'endereco'  => $this->endereco,
            'status'    => $this->status,
            'criadoEm'  => $this->criadoEm
        ]);

        //SUCESSO
        return true;
    }

    /**
     * Método responsável por atualizar os dados do banco com a instância atual
     * @return boolean
     */
    public function atualizar(){
        //VALIDA O DOCUMENTO
        $this->validarDocumento();

        //ATUALIZA O CLIENTE NO BANCO DE DADOS
        return (new Database('clientes'))->update('id = ?', [
            'nome'      => $this->nome,
            'telefone'  => $this->telefone,
            'email'     => $this->email,
            'documento' => $this->documento,
            'senha'     => $this->senha,
            'endereco'  => $this->endereco,
            'status'    => $this->status
        ], [$this->id]);
    }

    /**
     * Método responsável por inativar o cliente (mantém o histórico de pedidos)
     * @return boolean
     */
    public function inativar(){
        $this->status = 'inativo';
        return $this->atualizar();
    }

    /**
     * Método responsável por retornar os dados do cliente em array
     * @return array
     */
    public function toArray(){
        return [
            'id'        => (int)$this->id,
            'nome'      => $this->nome,
            'telefone'  => $this->telefone,
            'email'     => $this->email,
            'documento' => $this->documento,
            'endereco'  => $this->endereco,
            'status'    => $this->status,
            'possuiConta' => $this->possuiConta(),
            'criadoEm'  => $this->criadoEm
        ];
    }

    /**
     * Método responsável por retornar os dados que o próprio cliente vê na conta do cardápio
     * (sem CPF e sem status: são dados internos do restaurante)
     * @return array
     */
    public function toArrayConta(){
        return [
            'id'       => (int)$this->id,
            'nome'     => $this->nome,
            'telefone' => $this->telefone,
            'email'    => $this->email,
            'endereco' => $this->endereco
        ];
    }

    /**
     * Método responsável por retornar o cliente que já possui conta com o telefone informado
     * @param  string $telefone Somente dígitos
     * @return Cliente|false
     */
    public static function getContaByTelefone($telefone){
        return self::getClientes('telefone = ? AND senha IS NOT NULL', 'id ASC', 1, '*', [$telefone])->fetchObject(self::class);
    }

    /**
     * Método responsável por retornar o cliente ativo cadastrado pelo painel (sem conta) com o telefone informado
     * @param  string $telefone Somente dígitos
     * @return Cliente|false
     */
    public static function getClienteSemContaByTelefone($telefone){
        return self::getClientes('telefone = ? AND senha IS NULL AND status = "ativo"', 'id DESC', 1, '*', [$telefone])->fetchObject(self::class);
    }

    /**
     * Método responsável por montar um cliente a partir de uma linha do banco
     * (feito aqui dentro porque a senha é protegida e não pode ser preenchida de fora da classe)
     * @param  array $dados
     * @return Cliente
     */
    public static function instanciar($dados){
        $obCliente = new self;

        //PREENCHE OS ATRIBUTOS (COLUNAS EXTRAS DA CONSULTA SÃO IGNORADAS)
        foreach($dados as $atributo => $valor){
            if(property_exists($obCliente, $atributo)) $obCliente->$atributo = $valor;
        }

        return $obCliente;
    }

    /**
     * Método responsável por retornar um cliente com base em seu ID
     * @param  integer $id
     * @return Cliente|false
     */
    public static function getClienteById($id){
        return self::getClientes('id = ?', null, null, '*', [(int)$id])->fetchObject(self::class);
    }

    /**
     * Método responsável por retornar clientes
     * @param  string $where
     * @param  string $order
     * @param  string $limit
     * @param  string $fields
     * @param  array  $params
     * @return PDOStatement
     */
    public static function getClientes($where = null, $order = null, $limit = null, $fields = '*', $params = []){
        return (new Database('clientes'))->select($where, $order, $limit, $fields, $params);
    }
}
