<?php

namespace App\Controller\Api;

use \App\Model\Entity\Cupom as EntityCupom;
use \App\Utils\Validador;
use \App\Exception\DadoInvalidoException;
use \App\Exception\RegistroNaoEncontradoException;

class Cupons extends Api{

   /**
    * Método responsável por retornar os cupons cadastrados
    * @param  Request $request
    * @return array
    */
    public static function getCupons($request){
        //CUPONS
        $itens = [];

        //FILTROS (WHERE)
        $filtros = [];
        $params  = [];

        //BUSCA PELO CÓDIGO
        if($busca = self::getBusca($request)){
            $filtros[] = 'codigo LIKE ?';
            $params[]  = '%'.mb_strtoupper($busca).'%';
        }

        //RESULTADOS
        $results = EntityCupom::getCupons(implode(' AND ', $filtros), 'status ASC, codigo ASC', null, '*', $params);
        while($obCupom = $results->fetchObject(EntityCupom::class)){
            $itens[] = $obCupom->toArray();
        }

        //RETORNA OS CUPONS
        return [
            'cupons' => $itens
        ];
    }

   /**
    * Método responsável por cadastrar um novo cupom
    * @param  Request $request
    * @return array
    */
    public static function setNovoCupom($request){
        //NOVO CUPOM
        $obCupom = new EntityCupom;
        self::preencher($obCupom, $request->getPostVars());
        $obCupom->cadastrar();

        return $obCupom->toArray();
    }

   /**
    * Método responsável por atualizar um cupom
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function setEditCupom($request, $id){
        //BUSCA O CUPOM
        $obCupom = self::getCupomValido($id);

        //ATUALIZA O CUPOM
        self::preencher($obCupom, $request->getPostVars());
        $obCupom->atualizar();

        return $obCupom->toArray();
    }

   /**
    * Método responsável por inativar um cupom
    * @param  Request $request
    * @param  integer $id
    * @return array
    */
    public static function setDeleteCupom($request, $id){
        //INATIVA O CUPOM
        self::getCupomValido($id)->inativar();

        return [
            'sucesso' => true
        ];
    }

   /**
    * Método responsável por validar e preencher os dados do cupom
    * @param EntityCupom $obCupom
    * @param array       $postVars
    */
    private static function preencher($obCupom, $postVars){
        //VALIDA O CÓDIGO (LETRAS E NÚMEROS)
        $codigo = mb_strtoupper(Validador::obrigatorio($postVars['codigo'] ?? '', 'codigo', 'Código'));
        if(!preg_match('/^[A-Z0-9]{3,30}$/', $codigo)){
            throw new DadoInvalidoException("O código deve ter de 3 a 30 letras ou números, sem espaços.", null, 'codigo');
        }

        //VALIDA A DUPLICAÇÃO DO CÓDIGO
        $obExistente = EntityCupom::getCupomByCodigo($codigo);
        if($obExistente and $obExistente->id != $obCupom->id){
            throw new DadoInvalidoException("Já existe um cupom com o código '".$codigo."'.", null, 'codigo');
        }

        //VALIDA O TIPO E O VALOR
        $tipo  = Validador::opcao($postVars['tipo'] ?? '', ['percentual', 'fixo'], 'tipo');
        $valor = Validador::numero(Validador::obrigatorio($postVars['valor'] ?? '', 'valor', 'Valor'), 'valor', 0.01, 'Valor');
        if($tipo == 'percentual' and $valor > 100){
            throw new DadoInvalidoException("O desconto percentual não pode passar de 100%.", null, 'valor');
        }

        //VALIDA A VALIDADE (OPCIONAL)
        $validade = trim((string)($postVars['validade'] ?? ''));

        $obCupom->codigo   = $codigo;
        $obCupom->tipo     = $tipo;
        $obCupom->valor    = round($valor, 2);
        $obCupom->validade = strlen($validade) ? Validador::data($validade, 'validade') : null;
        $obCupom->status   = Validador::opcao($postVars['status'] ?? 'ativo', ['ativo', 'inativo'], 'status');
    }

   /**
    * Método responsável por buscar o cupom e validar se existe
    * @param  integer $id
    * @return EntityCupom
    */
    private static function getCupomValido($id){
        $obCupom = EntityCupom::getCupomById($id);
        if(!$obCupom instanceof EntityCupom){
            throw new RegistroNaoEncontradoException("Cupom não encontrado.");
        }
        return $obCupom;
    }
}
