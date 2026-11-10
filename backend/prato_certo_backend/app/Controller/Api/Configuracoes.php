<?php

namespace App\Controller\Api;

use \App\Model\Entity\Configuracao as EntityConfiguracao;
use \App\Utils\Validador;
use \App\Exception\DadoInvalidoException;

class Configuracoes extends Api{

   /**
    * Método responsável por retornar as configurações do restaurante
    * @param  Request $request
    * @return array
    */
    public static function getConfiguracoes($request){
        return EntityConfiguracao::getConfiguracao()->toArray();
    }

   /**
    * Método responsável por atualizar as configurações do restaurante
    * @param  Request $request
    * @return array
    */
    public static function setConfiguracoes($request){
        //POST VARS
        $postVars       = $request->getPostVars();
        $obConfiguracao = EntityConfiguracao::getConfiguracao();

        //VALIDA A TAXA DE SERVIÇO
        $taxaServico = Validador::numero($postVars['taxaServico'] ?? $obConfiguracao->taxaServico, 'taxaServico', 0, 'Taxa de serviço');
        if($taxaServico > 30){
            throw new DadoInvalidoException("A taxa de serviço não pode passar de 30%.", null, 'taxaServico');
        }

        //ATUALIZA A CONFIGURAÇÃO
        $obConfiguracao->nomeRestaurante = Validador::tamanho(Validador::obrigatorio($postVars['nomeRestaurante'] ?? '', 'nomeRestaurante', 'Nome do restaurante'), 'nomeRestaurante', 2, 120);
        $obConfiguracao->telefone        = mb_substr(trim((string)($postVars['telefone'] ?? '')), 0, 20) ?: null;
        $obConfiguracao->endereco        = mb_substr(trim((string)($postVars['endereco'] ?? '')), 0, 255) ?: null;
        $obConfiguracao->taxaServico     = round($taxaServico, 2);
        $obConfiguracao->taxaEntrega     = round(Validador::numero($postVars['taxaEntrega'] ?? $obConfiguracao->taxaEntrega, 'taxaEntrega', 0, 'Taxa de entrega'), 2);
        $obConfiguracao->cardapioAberto  = !empty($postVars['cardapioAberto']) ? 1 : 0;
        $obConfiguracao->atualizar();

        return $obConfiguracao->toArray();
    }
}
