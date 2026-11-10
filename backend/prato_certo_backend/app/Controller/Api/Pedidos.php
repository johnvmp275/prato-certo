<?php

namespace App\Controller\Api;

use \App\Model\Entity\Pedido as EntityPedido;
use \App\Model\Entity\PedidoMesa as EntityPedidoMesa;
use \App\Model\Entity\PedidoOnline as EntityPedidoOnline;
use \App\Model\Entity\Pagamento as EntityPagamento;
use \App\Model\Entity\Mesa as EntityMesa;
use \App\Model\Entity\Cupom as EntityCupom;
use \App\Utils\Validador;
use \App\Exception\EstadoInvalidoException;
use \App\Exception\RegistroNaoEncontradoException;

class Pedidos extends Api{

   /**
    * Método responsável por retornar os pedidos com filtros e paginação
    * Filtros: ?status=aberto,em_espera &tipo=mesa &busca=... &data=AAAA-MM-DD
    * @param  Request $request
    * @return array
    */
    public static function getPedidos($request){
        //PEDIDOS
        $itens = [];

        //OBTÉM OS PARÂMETROS DA URL
        $queryParams = $request->getQueryParams();

        //FILTROS (WHERE)
        $filtros = [];
        $params  = [];

        //FILTRO DE STATUS (ACEITA VÁRIOS SEPARADOS POR VÍRGULA)
        if(!empty($queryParams['status'])){
            $status = array_values(array_intersect(explode(',', $queryParams['status']), array_keys(EntityPedido::STATUS_DESCRICAO)));
            if(count($status)){
                $filtros[] = 'status IN ('.implode(',', array_fill(0, count($status), '?')).')';
                $params    = array_merge($params, $status);
            }
        }

        //FILTRO DE TIPO
        if(!empty($queryParams['tipo']) and in_array($queryParams['tipo'], ['mesa', 'balcao', 'online'])){
            $filtros[] = 'tipo = ?';
            $params[]  = $queryParams['tipo'];
        }

        //FILTRO DE DATA
        if(!empty($queryParams['data'])){
            $filtros[] = 'DATE(criadoEm) = ?';
            $params[]  = Validador::data($queryParams['data'], 'data');
        }

        //BUSCA POR CÓDIGO, CLIENTE, CONTATO OU NÚMERO DA MESA
        if($busca = self::getBusca($request)){
            $filtros[] = '(codigo LIKE ? OR nomeContato LIKE ? OR clienteId IN (SELECT c.id FROM clientes c WHERE c.nome LIKE ?) OR mesaId IN (SELECT m.id FROM mesas m WHERE m.numero = ?))';
            array_push($params, '%'.ltrim($busca, '#').'%', '%'.$busca.'%', '%'.$busca.'%', (int)$busca);
        }

        //CONSTRUTOR WHERE
        $where = implode(' AND ', $filtros);

        //QUANTIDADE TOTAL DE REGISTROS
        $quantidadeTotal = EntityPedido::getPedidos($where, null, null, 'COUNT(*) as qtd', $params)->fetchObject()->qtd;

        //INSTANCIA DE PAGINAÇÃO
        $obPagination = self::getPaginacao($request, $quantidadeTotal, 15);

        //RESULTADOS DA PÁGINA
        $results = EntityPedido::getPedidos($where, 'id DESC', $obPagination->getLimit(), '*', $params);
        while($dados = $results->fetch(\PDO::FETCH_ASSOC)){
            $itens[] = EntityPedido::instanciar($dados)->toArray(false);
        }

        //RETORNA OS PEDIDOS
        return [
            'pedidos'   => $itens,
            'paginacao' => parent::getPagination($request, $obPagination)
        ];
    }

   /**
    * Método responsável por retornar os detalhes de um pedido
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function getPedido($request, $id){
        return self::getPedidoValido($id)->toArray();
    }

   /**
    * Método responsável por abrir um novo pedido (mesa ou balcão) no PDV
    * @param  Request $request
    * @return array
    */
    public static function setNovoPedido($request){
        //POST VARS
        $postVars = $request->getPostVars();
        $tipo     = Validador::opcao($postVars['tipo'] ?? '', ['mesa', 'balcao'], 'tipo');

        //EXECUTA EM TRANSAÇÃO (MESA, PEDIDO E ITENS SÃO GRAVADOS JUNTOS)
        $obPedido = self::transacao(function() use ($request, $postVars, $tipo){
            //NOVO PEDIDO (FÁBRICA)
            $obPedido = EntityPedido::instanciar(['tipo' => $tipo]);
            $obPedido->usuarioId  = $request->usuario->id;
            $obPedido->observacao = mb_substr(trim((string)($postVars['observacao'] ?? '')), 0, 255) ?: null;

            //CLIENTE (OPCIONAL)
            self::definirCliente($obPedido, $postVars['clienteId'] ?? null);

            //ABRE A COMANDA NA MESA (RN03, RN04)
            if($obPedido instanceof EntityPedidoMesa){
                $obMesa = EntityMesa::getMesaById(Validador::obrigatorio($postVars['mesaId'] ?? '', 'mesaId', 'Mesa'));
                if(!$obMesa instanceof EntityMesa){
                    throw new RegistroNaoEncontradoException("Mesa não encontrada.", null, 'mesaId');
                }
                $obPedido->cobrarServico = isset($postVars['cobrarServico']) ? (int)(bool)$postVars['cobrarServico'] : 1;
                $obPedido->abrir($obMesa, Validador::obrigatorio($postVars['numPessoas'] ?? '', 'numPessoas', 'Número de pessoas'));
            }

            //ITENS
            self::preencherItens($obPedido, $postVars['itens'] ?? []);

            //SALVA O PEDIDO
            $obPedido->salvar();
            return $obPedido;
        });

        //RETORNA O PEDIDO CRIADO
        return $obPedido->toArray();
    }

   /**
    * Método responsável por atualizar itens, cliente e observação de um pedido
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function setEditPedido($request, $id){
        //POST VARS
        $postVars = $request->getPostVars();
        $obPedido = self::getPedidoValido($id);

        self::transacao(function() use ($obPedido, $postVars){
            //SUBSTITUI OS ITENS
            if(array_key_exists('itens', $postVars)){
                $obPedido->limparItens();
                self::preencherItens($obPedido, $postVars['itens']);
            }

            //CLIENTE
            if(array_key_exists('clienteId', $postVars)){
                self::definirCliente($obPedido, $postVars['clienteId']);
            }

            //OBSERVAÇÃO
            if(array_key_exists('observacao', $postVars)){
                $obPedido->observacao = mb_substr(trim((string)$postVars['observacao']), 0, 255) ?: null;
            }

            //TAXA DE SERVIÇO (SOMENTE MESA)
            if($obPedido instanceof EntityPedidoMesa and array_key_exists('cobrarServico', $postVars)){
                $obPedido->cobrarServico = (int)(bool)$postVars['cobrarServico'];
            }

            //O NOVO TOTAL NÃO PODE FICAR MENOR QUE O JÁ PAGO
            $obPedido->calcularTotal();
            if($obPedido->total < $obPedido->getValorPago()){
                throw new EstadoInvalidoException("O total do pedido ficaria menor que o valor já pago.");
            }

            //SALVA O PEDIDO
            $obPedido->salvar();
        });

        return $obPedido->toArray();
    }

   /**
    * Método responsável por colocar o pedido em espera
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function setEspera($request, $id){
        $obPedido = self::getPedidoValido($id);
        $obPedido->colocarEmEspera();
        $obPedido->salvar(false);

        return $obPedido->toArray();
    }

   /**
    * Método responsável por retomar um pedido em espera
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function setRetomar($request, $id){
        $obPedido = self::getPedidoValido($id);
        $obPedido->retomar();
        $obPedido->salvar(false);

        return $obPedido->toArray();
    }

   /**
    * Método responsável por aceitar um pedido online pendente
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function setAceitar($request, $id){
        //BUSCA O PEDIDO
        $obPedido = self::getPedidoValido($id);

        //SOMENTE PEDIDOS ONLINE PRECISAM SER ACEITOS
        if(!$obPedido instanceof EntityPedidoOnline){
            throw new EstadoInvalidoException("Somente pedidos online precisam ser aceitos.");
        }

        //ACEITA O PEDIDO
        $obPedido->aceitar($request->usuario);
        $obPedido->salvar(false);

        return $obPedido->toArray();
    }

   /**
    * Método responsável por aplicar um desconto ao pedido
    * Body: { "tipo": "valor" | "percentual" | "cupom", "valor": 10, "codigo": "BEMVINDO10" }
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function setDesconto($request, $id){
        //POST VARS
        $postVars = $request->getPostVars();
        $obPedido = self::getPedidoValido($id);
        $tipo     = Validador::opcao($postVars['tipo'] ?? '', ['valor', 'percentual', 'cupom'], 'tipo');

        //DESCONTO POR CUPOM (SOBRECARGA: aplicarDesconto(Cupom))
        if($tipo == 'cupom'){
            $obCupom = EntityCupom::getCupomByCodigo(Validador::obrigatorio($postVars['codigo'] ?? '', 'codigo', 'Cupom'));
            if(!$obCupom instanceof EntityCupom){
                throw new RegistroNaoEncontradoException("Cupom não encontrado.", null, 'codigo');
            }
            $obPedido->aplicarDesconto($obCupom);

        //DESCONTO EM VALOR OU PERCENTUAL (SOBRECARGA: aplicarDesconto(float, string))
        }else{
            $valor = Validador::numero(Validador::obrigatorio($postVars['valor'] ?? '', 'valor', 'Valor do desconto'), 'valor', 0, 'Valor do desconto');
            $obPedido->aplicarDesconto($valor, $tipo);
        }

        //SALVA OS NOVOS VALORES
        $obPedido->salvar(false);

        return $obPedido->toArray();
    }

   /**
    * Método responsável por remover o desconto do pedido
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function deleteDesconto($request, $id){
        $obPedido = self::getPedidoValido($id);
        $obPedido->removerDesconto();
        $obPedido->salvar(false);

        return $obPedido->toArray();
    }

   /**
    * Método responsável por registrar um pagamento (e finalizar o pedido quando quitado)
    * Body: { "forma": "dinheiro" | "cartao" | "pix", "valor": 50, "modalidade": "credito", "bandeira": "Visa" }
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function setPagamento($request, $id){
        //POST VARS (SOMENTE OS CAMPOS PERMITIDOS)
        $postVars = array_intersect_key($request->getPostVars(), array_flip(['forma', 'valor', 'bandeira', 'modalidade']));
        $obPedido = self::getPedidoValido($id);

        return self::transacao(function() use ($obPedido, $postVars){
            //CRIA O PAGAMENTO DA FORMA ESCOLHIDA (FÁBRICA) E REGISTRA NO PEDIDO
            $obPagamento = EntityPagamento::instanciar($postVars);
            $obPedido->registrarPagamento($obPagamento);
            $obPedido->salvar(false);

            //FINALIZA AUTOMATICAMENTE QUANDO O PEDIDO FOR QUITADO
            $finalizado = false;
            if($obPedido->getValorRestante() <= 0){
                $obPedido->finalizar();
                $finalizado = true;
            }

            return [
                'pagamento'  => $obPagamento->toArray(),
                'troco'      => (float)$obPagamento->troco,
                'finalizado' => $finalizado,
                'pedido'     => $obPedido->toArray()
            ];
        });
    }

   /**
    * Método responsável por finalizar manualmente um pedido já quitado
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function setFinalizar($request, $id){
        $obPedido = self::getPedidoValido($id);

        self::transacao(function() use ($obPedido){
            $obPedido->finalizar();
        });

        return $obPedido->toArray();
    }

   /**
    * Método responsável por cancelar um pedido (somente administrador - RN09)
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function setCancelar($request, $id){
        $obPedido = self::getPedidoValido($id);

        self::transacao(function() use ($obPedido){
            $obPedido->cancelar();
        });

        return $obPedido->toArray();
    }

   /**
    * Método responsável por buscar o pedido e validar se existe
    * @param  integer $id
    * @return EntityPedido
    */
    private static function getPedidoValido($id){
        $obPedido = is_numeric($id) ? EntityPedido::getPedidoById($id) : null;
        if(!$obPedido instanceof EntityPedido){
            throw new RegistroNaoEncontradoException("Pedido não encontrado.");
        }
        return $obPedido;
    }
}
