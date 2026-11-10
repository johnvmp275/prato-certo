<?php

namespace App\Controller\Api;

use \App\Model\Entity\Configuracao as EntityConfiguracao;
use \App\Model\Entity\Pix as EntityPix;
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

        //PIX DO RESTAURANTE (OPCIONAL: SEM ELE O CARDÁPIO NÃO MOSTRA O QR CODE)
        self::preencherPix($obConfiguracao, $postVars);

        $obConfiguracao->atualizar();

        return $obConfiguracao->toArray();
    }

   /**
    * Método responsável por validar e preencher os dados do PIX do restaurante
    * (chave, titular e cidade são obrigatórios juntos: o BR Code precisa dos três)
    * @param EntityConfiguracao $obConfiguracao
    * @param array              $postVars
    */
    private static function preencherPix($obConfiguracao, $postVars){
        $chave   = trim((string)($postVars['pixChave'] ?? ''));
        $titular = trim((string)($postVars['pixTitular'] ?? ''));
        $cidade  = trim((string)($postVars['pixCidade'] ?? ''));

        //SEM CHAVE: DESATIVA O PIX
        if(!strlen($chave)){
            $obConfiguracao->pixChave = $obConfiguracao->pixTitular = $obConfiguracao->pixCidade = null;
            return;
        }

        //NO BR CODE O TITULAR VAI ATÉ 25 E A CIDADE ATÉ 15 CARACTERES: A CLASSE Pix CORTA AO GERAR
        //A CHAVE É SALVA NO FORMATO DO PIX (EX.: TELEFONE GANHA O +55)
        $obConfiguracao->pixChave   = Validador::tamanho(EntityPix::normalizarChave($chave), 'pixChave', 1, 77);
        $obConfiguracao->pixTitular = Validador::tamanho(Validador::obrigatorio($titular, 'pixTitular', 'Titular do PIX'), 'pixTitular', 2, 60);
        $obConfiguracao->pixCidade  = Validador::tamanho(Validador::obrigatorio($cidade, 'pixCidade', 'Cidade do PIX'), 'pixCidade', 2, 60);
    }
}
