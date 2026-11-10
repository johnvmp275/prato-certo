<?php

namespace App\Controller\Api;

use \App\Model\Entity\Cliente as EntityCliente;
use \App\Model\Entity\Pedido as EntityPedido;
use \App\Session\Cardapio\Login as SessionCardapioLogin;
use \App\Utils\Validador;
use \App\Exception\DadoInvalidoException;
use \App\Exception\OperacaoNaoPermitidaException;

class Conta extends Api{

   /**
    * Método responsável pelo cadastro do cliente no cardápio online
    * Se o telefone já foi cadastrado pelo painel (sem senha), aproveita esse cadastro
    * Body: { "nome": "", "telefone": "", "email": "", "senha": "" }
    * @param  Request $request
    * @return array
    */
    public static function setCadastro($request){
        //POST VARS
        $postVars = $request->getPostVars();
        $nome     = Validador::tamanho(Validador::obrigatorio($postVars['nome'] ?? '', 'nome', 'Nome'), 'nome', 2, 120);
        $telefone = Validador::telefone(Validador::obrigatorio($postVars['telefone'] ?? '', 'telefone', 'Telefone'));
        $email    = self::getEmail($postVars['email'] ?? '');
        $senha    = Validador::obrigatorio($postVars['senha'] ?? '', 'senha', 'Senha');

        //VALIDA SE JÁ EXISTE UMA CONTA COM ESTE TELEFONE
        if(EntityCliente::getContaByTelefone($telefone) instanceof EntityCliente){
            throw new DadoInvalidoException("Já existe uma conta com este telefone. Entre com sua senha.", null, 'telefone');
        }

        //APROVEITA O CADASTRO FEITO PELO PAINEL (MESMO TELEFONE) OU CRIA UM NOVO
        $obCliente = EntityCliente::getClienteSemContaByTelefone($telefone) ?: new EntityCliente;
        $obCliente->nome     = $nome;
        $obCliente->telefone = $telefone;
        if($email) $obCliente->email = $email;
        $obCliente->definirSenha($senha);

        //SALVA O CLIENTE
        $obCliente->id ? $obCliente->atualizar() : $obCliente->cadastrar();

        //JÁ DEIXA O CLIENTE LOGADO
        SessionCardapioLogin::login($obCliente);

        return $obCliente->toArrayConta();
    }

   /**
    * Método responsável pelo login do cliente (telefone + senha)
    * @param  Request $request
    * @return array
    */
    public static function setLogin($request){
        //POST VARS
        $postVars = $request->getPostVars();
        $telefone = preg_replace('/\D/', '', (string)Validador::obrigatorio($postVars['telefone'] ?? '', 'telefone', 'Telefone'));
        $senha    = Validador::obrigatorio($postVars['senha'] ?? '', 'senha', 'Senha');

        //BUSCA A CONTA PELO TELEFONE
        $obCliente = EntityCliente::getContaByTelefone($telefone);

        //VALIDA A INSTÂNCIA E A SENHA (MESMA MENSAGEM PARA NÃO REVELAR QUAL DADO ESTÁ ERRADO)
        if(!$obCliente instanceof EntityCliente or !$obCliente->verificarSenha($senha)){
            throw new DadoInvalidoException("Telefone ou senha inválidos.", 401);
        }

        //VALIDA SE O CLIENTE ESTÁ ATIVO
        if($obCliente->status != 'ativo'){
            throw new OperacaoNaoPermitidaException("Esta conta está desativada. Fale com o restaurante.");
        }

        //CRIA A SESSÃO DE LOGIN
        SessionCardapioLogin::login($obCliente);

        return $obCliente->toArrayConta();
    }

   /**
    * Método responsável por retornar o cliente logado
    * @param  Request $request
    * @return array
    */
    public static function getConta($request){
        return $request->cliente->toArrayConta();
    }

   /**
    * Método responsável por atualizar os dados do cliente logado
    * Body: { "nome", "telefone", "email", "endereco", "senhaAtual", "novaSenha" }
    * @param  Request $request
    * @return array
    */
    public static function setEditConta($request){
        //POST VARS
        $postVars  = $request->getPostVars();
        $obCliente = $request->cliente;

        //DADOS PESSOAIS
        $obCliente->nome     = Validador::tamanho(Validador::obrigatorio($postVars['nome'] ?? '', 'nome', 'Nome'), 'nome', 2, 120);
        $obCliente->email    = self::getEmail($postVars['email'] ?? '') ?: null;
        $obCliente->endereco = mb_substr(trim((string)($postVars['endereco'] ?? '')), 0, 255) ?: null;

        //TELEFONE (É O LOGIN: NÃO PODE SER IGUAL AO DE OUTRA CONTA)
        $telefone = Validador::telefone(Validador::obrigatorio($postVars['telefone'] ?? '', 'telefone', 'Telefone'));
        $obOutraConta = EntityCliente::getContaByTelefone($telefone);
        if($obOutraConta instanceof EntityCliente and $obOutraConta->id != $obCliente->id){
            throw new DadoInvalidoException("Já existe outra conta com este telefone.", null, 'telefone');
        }
        $obCliente->telefone = $telefone;

        //TROCA DE SENHA (OPCIONAL, EXIGE A SENHA ATUAL)
        if(strlen((string)($postVars['novaSenha'] ?? ''))){
            if(!$obCliente->verificarSenha($postVars['senhaAtual'] ?? '')){
                throw new DadoInvalidoException("A senha atual não confere.", null, 'senhaAtual');
            }
            $obCliente->definirSenha($postVars['novaSenha']);
        }

        //SALVA
        $obCliente->atualizar();

        return $obCliente->toArrayConta();
    }

   /**
    * Método responsável por retornar os pedidos do cliente logado (com paginação)
    * @param  Request $request
    * @return array
    */
    public static function getPedidos($request){
        //PEDIDOS DO CLIENTE
        $itens  = [];
        $params = [$request->cliente->id];

        //QUANTIDADE TOTAL DE REGISTROS
        $quantidadeTotal = EntityPedido::getPedidos('clienteId = ? AND tipo = "online"', null, null, 'COUNT(*) as qtd', $params)->fetchObject()->qtd;

        //INSTANCIA DE PAGINAÇÃO
        $obPagination = self::getPaginacao($request, $quantidadeTotal, 10);

        //RESULTADOS DA PÁGINA (MESMO RESUMO PÚBLICO DO ACOMPANHAMENTO)
        $results = EntityPedido::getPedidos('clienteId = ? AND tipo = "online"', 'id DESC', $obPagination->getLimit(), '*', $params);
        while($dados = $results->fetch(\PDO::FETCH_ASSOC)){
            $itens[] = Cardapio::getResumoPublico(EntityPedido::instanciar($dados));
        }

        return [
            'pedidos'   => $itens,
            'paginacao' => parent::getPagination($request, $obPagination)
        ];
    }

   /**
    * Método responsável pelo logout do cliente
    * @param  Request $request
    * @return array
    */
    public static function setLogout($request){
        //DESLOGA O CLIENTE
        SessionCardapioLogin::logout();

        return [
            'sucesso' => true
        ];
    }

   /**
    * Método responsável por validar o e-mail opcional
    * @param  string $email
    * @return string|null
    */
    private static function getEmail($email){
        $email = trim((string)$email);
        return strlen($email) ? Validador::email($email) : null;
    }
}
