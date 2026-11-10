<?php

namespace App\Controller\Api;

use \App\DatabaseManager\Database;
use \App\Model\Entity\Pedido as EntityPedido;

class Dashboard extends Api{

   /**
    * Método responsável por retornar o resumo do dia para o painel
    * @param  Request $request
    * @return array
    */
    public static function getResumo($request){
        //CONEXÃO PARA CONSULTAS AGREGADAS
        $obDatabase = new Database();
        $hoje       = date('Y-m-d');

        //VENDAS FINALIZADAS HOJE
        $vendas = $obDatabase->execute(
            'SELECT COUNT(*) AS quantidade, COALESCE(SUM(total), 0) AS valor FROM pedidos WHERE status = "finalizado" AND DATE(finalizadoEm) = ?',
            [$hoje]
        )->fetch(\PDO::FETCH_ASSOC);

        //PEDIDOS EM ANDAMENTO POR STATUS
        $andamento = $obDatabase->execute(
            'SELECT status, COUNT(*) AS quantidade FROM pedidos WHERE status IN ("pendente","aberto","em_espera") GROUP BY status'
        )->fetchAll(\PDO::FETCH_KEY_PAIR);

        //OCUPAÇÃO DAS MESAS
        $mesas = $obDatabase->execute(
            'SELECT COUNT(*) AS total, SUM(situacao = "ocupada") AS ocupadas FROM mesas WHERE status = "ativo"'
        )->fetch(\PDO::FETCH_ASSOC);

        //PRODUTOS MAIS VENDIDOS HOJE
        $maisVendidos = $obDatabase->execute(
            'SELECT i.nomeProduto AS nome, SUM(i.quantidade) AS quantidade, SUM(i.quantidade * i.precoUnitario) AS valor
               FROM itens_pedido i
              INNER JOIN pedidos p ON p.id = i.pedidoId
              WHERE p.status = "finalizado" AND DATE(p.finalizadoEm) = ?
              GROUP BY i.produtoId, i.nomeProduto
              ORDER BY quantidade DESC
              LIMIT 5',
            [$hoje]
        )->fetchAll(\PDO::FETCH_ASSOC);

        //VENDAS DOS ÚLTIMOS 7 DIAS
        $semana = $obDatabase->execute(
            'SELECT DATE(finalizadoEm) AS dia, COALESCE(SUM(total), 0) AS valor
               FROM pedidos
              WHERE status = "finalizado" AND DATE(finalizadoEm) >= ?
              GROUP BY DATE(finalizadoEm)',
            [date('Y-m-d', strtotime('-6 days'))]
        )->fetchAll(\PDO::FETCH_KEY_PAIR);

        //PREENCHE OS DIAS SEM VENDA
        $ultimosDias = [];
        for($i = 6; $i >= 0; $i--){
            $dia = date('Y-m-d', strtotime('-'.$i.' days'));
            $ultimosDias[] = ['dia' => $dia, 'valor' => (float)($semana[$dia] ?? 0)];
        }

        //ÚLTIMOS PEDIDOS
        $ultimosPedidos = [];
        $results = EntityPedido::getPedidos(null, 'id DESC', 6);
        while($dados = $results->fetch(\PDO::FETCH_ASSOC)){
            $ultimosPedidos[] = EntityPedido::instanciar($dados)->toArray(false);
        }

        //RETORNA O RESUMO
        $quantidadeVendas = (int)$vendas['quantidade'];
        return [
            'vendasHoje'      => (float)$vendas['valor'],
            'pedidosHoje'     => $quantidadeVendas,
            'ticketMedio'     => $quantidadeVendas ? round($vendas['valor'] / $quantidadeVendas, 2) : 0,
            'pedidosAbertos'  => (int)($andamento['aberto'] ?? 0) + (int)($andamento['em_espera'] ?? 0),
            'pedidosEmEspera' => (int)($andamento['em_espera'] ?? 0),
            'pedidosOnline'   => (int)($andamento['pendente'] ?? 0),
            'mesas'           => [
                'total'    => (int)$mesas['total'],
                'ocupadas' => (int)$mesas['ocupadas']
            ],
            'maisVendidos'    => array_map(fn($item) => [
                'nome'       => $item['nome'],
                'quantidade' => (int)$item['quantidade'],
                'valor'      => (float)$item['valor']
            ], $maisVendidos),
            'ultimosDias'     => $ultimosDias,
            'ultimosPedidos'  => $ultimosPedidos
        ];
    }
}
