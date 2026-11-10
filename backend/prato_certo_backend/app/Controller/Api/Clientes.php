<?php

namespace App\Controller\Api;

use \App\Model\Entity\Cliente as EntityCliente;
use \App\Utils\Validador;
use \App\Exception\DadoInvalidoException;
use \App\Exception\RegistroNaoEncontradoException;

class Clientes extends Api{

   /**
    * Método responsável por retornar os clientes cadastrados
    * @param  Request $request
    * @return array
    */
    public static function getClientes($request){
        //CLIENTES
        $itens = [];

        //FILTROS (WHERE)
        $filtros = [];
        $params  = [];

        //BUSCA POR NOME, TELEFONE, E-MAIL OU CPF
        if($busca = self::getBusca($request)){
            $numeros   = preg_replace('/\D/', '', $busca);
            $filtros[] = '(nome LIKE ? OR email LIKE ?'.(strlen($numeros) ? ' OR telefone LIKE ? OR documento LIKE ?' : '').')';
            array_push($params, '%'.$busca.'%', '%'.$busca.'%');
            if(strlen($numeros)) array_push($params, '%'.$numeros.'%', '%'.$numeros.'%');
        }

        //FILTRO DE STATUS
        $status = $request->getQueryParams()['status'] ?? '';
        if(in_array($status, ['ativo', 'inativo'])){
            $filtros[] = 'status = ?';
            $params[]  = $status;
        }

        //CONSTRUTOR WHERE
        $where = implode(' AND ', $filtros);

        //QUANTIDADE TOTAL DE REGISTROS
        $quantidadeTotal = EntityCliente::getClientes($where, null, null, 'COUNT(*) as qtd', $params)->fetchObject()->qtd;

        //INSTANCIA DE PAGINAÇÃO
        $obPagination = self::getPaginacao($request, $quantidadeTotal);

        //TOTAL GASTO PELO CLIENTE (PEDIDOS FINALIZADOS)
        $fields = '*, (SELECT COALESCE(SUM(p.total), 0) FROM pedidos p WHERE p.clienteId = clientes.id AND p.status = "finalizado") AS totalGasto,
                      (SELECT COUNT(*) FROM pedidos p WHERE p.clienteId = clientes.id AND p.status = "finalizado") AS totalPedidos';

        //RESULTADOS DA PÁGINA
        $results = EntityCliente::getClientes($where, 'nome ASC', $obPagination->getLimit(), $fields, $params);
        while($dados = $results->fetch(\PDO::FETCH_ASSOC)){
            $obCliente = new EntityCliente;
            foreach($dados as $campo => $valor) if(property_exists($obCliente, $campo)) $obCliente->$campo = $valor;

            $itens[] = array_merge($obCliente->toArray(), [
                'totalGasto'   => (float)$dados['totalGasto'],
                'totalPedidos' => (int)$dados['totalPedidos']
            ]);
        }

        //RETORNA OS CLIENTES
        return [
            'clientes'  => $itens,
            'paginacao' => parent::getPagination($request, $obPagination)
        ];
    }

   /**
    * Método responsável por retornar um cliente com seu histórico de pedidos
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function getCliente($request, $id){
        //BUSCA O CLIENTE
        $obCliente = self::getClienteValido($id);

        return array_merge($obCliente->toArray(), [
            'historico' => $obCliente->consultarHistorico()
        ]);
    }

   /**
    * Método responsável por cadastrar um novo cliente
    * @param  Request $request
    * @return array
    */
    public static function setNovoCliente($request){
        //NOVO CLIENTE
        $obCliente = new EntityCliente;
        self::preencher($obCliente, $request->getPostVars());
        $obCliente->cadastrar();

        return $obCliente->toArray();
    }

   /**
    * Método responsável por atualizar um cliente
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function setEditCliente($request, $id){
        //BUSCA O CLIENTE
        $obCliente = self::getClienteValido($id);

        //ATUALIZA O CLIENTE
        self::preencher($obCliente, $request->getPostVars());
        $obCliente->atualizar();

        return $obCliente->toArray();
    }

   /**
    * Método responsável por inativar um cliente
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function setDeleteCliente($request, $id){
        //INATIVA O CLIENTE (RN12)
        self::getClienteValido($id)->inativar();

        return [
            'sucesso' => true
        ];
    }

   /**
    * Método responsável por validar e preencher os dados do cliente
    * @param EntityCliente $obCliente
    * @param array         $postVars
    */
    private static function preencher($obCliente, $postVars){
        //CAMPOS OBRIGATÓRIOS
        $obCliente->nome     = Validador::tamanho(Validador::obrigatorio($postVars['nome'] ?? '', 'nome', 'Nome'), 'nome', 3, 120);
        $obCliente->telefone = Validador::telefone(Validador::obrigatorio($postVars['telefone'] ?? '', 'telefone', 'Telefone'));

        //CAMPOS OPCIONAIS
        $email = trim((string)($postVars['email'] ?? ''));
        $obCliente->email     = strlen($email) ? Validador::email($email) : null;
        $obCliente->endereco  = mb_substr(trim((string)($postVars['endereco'] ?? '')), 0, 255) ?: null;
        $obCliente->documento = preg_replace('/\D/', '', (string)($postVars['documento'] ?? '')) ?: null;
        $obCliente->status    = Validador::opcao($postVars['status'] ?? 'ativo', ['ativo', 'inativo'], 'status');

        //VALIDA O CPF E A DUPLICAÇÃO (RN13)
        $obCliente->validarDocumento();
        if($obCliente->documento){
            $obExistente = EntityCliente::getClientes('documento = ?', null, 1, '*', [$obCliente->documento])->fetchObject(EntityCliente::class);
            if($obExistente and $obExistente->id != $obCliente->id){
                throw new DadoInvalidoException("Já existe um cliente cadastrado com este CPF.", null, 'documento');
            }
        }
    }

   /**
    * Método responsável por buscar o cliente e validar se existe
    * @param  integer $id
    * @return EntityCliente
    */
    private static function getClienteValido($id){
        $obCliente = EntityCliente::getClienteById($id);
        if(!$obCliente instanceof EntityCliente){
            throw new RegistroNaoEncontradoException("Cliente não encontrado.");
        }
        return $obCliente;
    }
}
