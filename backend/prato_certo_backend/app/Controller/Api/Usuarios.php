<?php

namespace App\Controller\Api;

use \App\Model\Entity\Usuario as EntityUsuario;
use \App\Utils\Validador;
use \App\Exception\DadoInvalidoException;
use \App\Exception\RegistroNaoEncontradoException;
use \App\Exception\OperacaoNaoPermitidaException;

class Usuarios extends Api{

   /**
    * Método responsável por retornar os usuários cadastrados
    * @param  Request $request
    * @return array
    */
    public static function getUsuarios($request){
        //USUÁRIOS
        $itens = [];

        //FILTROS (WHERE)
        $filtros = [];
        $params  = [];

        //BUSCA POR NOME OU E-MAIL
        if($busca = self::getBusca($request)){
            $filtros[] = '(nome LIKE ? OR email LIKE ?)';
            array_push($params, '%'.$busca.'%', '%'.$busca.'%');
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
        $quantidadeTotal = EntityUsuario::getUsuarios($where, null, null, 'COUNT(*) as qtd', $params)->fetchObject()->qtd;

        //INSTANCIA DE PAGINAÇÃO
        $obPagination = self::getPaginacao($request, $quantidadeTotal);

        //RESULTADOS DA PÁGINA
        $results = EntityUsuario::getUsuarios($where, 'nome ASC', $obPagination->getLimit(), '*', $params);
        while($dados = $results->fetch(\PDO::FETCH_ASSOC)){
            $itens[] = EntityUsuario::instanciar($dados)->toArray();
        }

        //RETORNA OS USUÁRIOS
        return [
            'usuarios'  => $itens,
            'paginacao' => parent::getPagination($request, $obPagination)
        ];
    }

   /**
    * Método responsável por retornar um usuário pelo ID
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function getUsuario($request, $id){
        return self::getUsuarioValido($id)->toArray();
    }

   /**
    * Método responsável por cadastrar um novo usuário
    * @param  Request $request
    * @return array
    */
    public static function setNovoUsuario($request){
        //POST VARS
        $postVars = $request->getPostVars();

        //VALIDA OS CAMPOS
        $dados = self::validarDados($postVars);
        Validador::obrigatorio($postVars['senha'] ?? '', 'senha', 'Senha');

        //VALIDA A DUPLICAÇÃO DO E-MAIL (RN13)
        if(EntityUsuario::getUsuarioByEmail($dados['email'])){
            throw new DadoInvalidoException("O e-mail '".$dados['email']."' já está em uso.", null, 'email');
        }

        //NOVO USUÁRIO (FÁBRICA ESCOLHE ADMINISTRADOR OU ATENDENTE)
        $obUsuario = EntityUsuario::instanciar($dados);
        $obUsuario->definirSenha($postVars['senha']);
        $obUsuario->cadastrar();

        //RETORNA OS DETALHES DO USUÁRIO CADASTRADO
        return $obUsuario->toArray();
    }

   /**
    * Método responsável por atualizar um usuário
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function setEditUsuario($request, $id){
        //POST VARS
        $postVars  = $request->getPostVars();
        $obUsuario = self::getUsuarioValido($id);

        //VALIDA OS CAMPOS
        $dados = self::validarDados($postVars);

        //VALIDA A DUPLICAÇÃO DO E-MAIL
        $obUsuarioEmail = EntityUsuario::getUsuarioByEmail($dados['email']);
        if($obUsuarioEmail and $obUsuarioEmail->id != $obUsuario->id){
            throw new DadoInvalidoException("O e-mail '".$dados['email']."' já está em uso.", null, 'email');
        }

        //O USUÁRIO NÃO PODE ALTERAR O PRÓPRIO PERFIL NEM SE INATIVAR
        $status = $postVars['status'] ?? $obUsuario->status;
        if($obUsuario->id == $request->usuario->id and ($dados['perfil'] != $obUsuario->perfil or $status != 'ativo')){
            throw new OperacaoNaoPermitidaException("Você não pode alterar o próprio perfil ou se inativar.");
        }

        //ATUALIZA O USUÁRIO
        $obUsuario->nome   = $dados['nome'];
        $obUsuario->email  = $dados['email'];
        $obUsuario->perfil = $dados['perfil'];
        $obUsuario->status = Validador::opcao($status, ['ativo', 'inativo'], 'status');
        if(!empty($postVars['senha'])) $obUsuario->definirSenha($postVars['senha']);
        $obUsuario->atualizar();

        //RETORNA OS DETALHES DO USUÁRIO (RECARREGADO PARA REFLETIR O NOVO PERFIL)
        return EntityUsuario::getUsuarioById($obUsuario->id)->toArray();
    }

   /**
    * Método responsável por inativar um usuário
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function setDeleteUsuario($request, $id){
        //BUSCA O USUÁRIO
        $obUsuario = self::getUsuarioValido($id);

        //NÃO PERMITE INATIVAR A SI MESMO
        if($obUsuario->id == $request->usuario->id){
            throw new OperacaoNaoPermitidaException("Você não pode inativar o próprio usuário.");
        }

        //INATIVA O USUÁRIO (RN12)
        $obUsuario->inativar();

        return [
            'sucesso' => true
        ];
    }

   /**
    * Método responsável por validar os dados comuns do formulário
    * @param  array $postVars
    * @return array
    */
    private static function validarDados($postVars){
        return [
            'nome'   => Validador::tamanho(Validador::obrigatorio($postVars['nome'] ?? '', 'nome', 'Nome'), 'nome', 3, 120),
            'email'  => Validador::email(Validador::obrigatorio($postVars['email'] ?? '', 'email', 'E-mail')),
            'perfil' => Validador::opcao($postVars['perfil'] ?? '', ['administrador', 'atendente'], 'perfil')
        ];
    }

   /**
    * Método responsável por buscar o usuário e validar se existe
    * @param  integer $id
    * @return EntityUsuario
    */
    private static function getUsuarioValido($id){
        $obUsuario = EntityUsuario::getUsuarioById($id);
        if(!$obUsuario instanceof EntityUsuario){
            throw new RegistroNaoEncontradoException("Usuário não encontrado.");
        }
        return $obUsuario;
    }
}
