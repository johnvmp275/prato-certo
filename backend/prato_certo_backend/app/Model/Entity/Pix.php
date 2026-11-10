<?php

namespace App\Model\Entity;

use \Mpdf\QrCode\QrCode;
use \Mpdf\QrCode\Output;

class Pix{

    /**
     * IDs do Payload do Pix (padrão BR Code do Banco Central)
     * @var string
     */
    const ID_PAYLOAD_FORMAT_INDICATOR                 = '00';
    const ID_MERCHANT_ACCOUNT_INFORMATION             = '26';
    const ID_MERCHANT_ACCOUNT_INFORMATION_GUI         = '00';
    const ID_MERCHANT_ACCOUNT_INFORMATION_KEY         = '01';
    const ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION = '02';
    const ID_MERCHANT_CATEGORY_CODE                   = '52';
    const ID_TRANSACTION_CURRENCY                     = '53';
    const ID_TRANSACTION_AMOUNT                       = '54';
    const ID_COUNTRY_CODE                             = '58';
    const ID_MERCHANT_NAME                            = '59';
    const ID_MERCHANT_CITY                            = '60';
    const ID_ADDITIONAL_DATA_FIELD_TEMPLATE           = '62';
    const ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TXID      = '05';
    const ID_CRC16                                    = '63';

    /**
     * Chave Pix
     * @var string
     */
    private $pixKey;

    /**
     * Descrição do Pagamento
     * @var string
     */
    private $description = '';

    /**
     * Nome do titular da conta
     * @var string
     */
    private $merchantName;

    /**
     * Cidade do titular da conta
     * @var string
     */
    private $merchantCity;

    /**
     * Id da transação pix
     * @var string
     */
    private $txid = '***';

    /**
     * Valor da transação
     * @var string
     */
    private $amount;

    /**
     * Método responsável por definir o valor de $pixKey
     * @param string $pixKey
     */
    public function setPixKey($pixKey){
        $this->pixKey = self::normalizarChave($pixKey);
        return $this;
    }

    /**
     * Método responsável por colocar a chave no formato exigido pelo PIX
     * - Telefone: +55 + DDD + número (sem o +55 o banco lê 11 dígitos como CPF e recusa o código)
     * - CPF/CNPJ: somente números · E-mail e chave aleatória: minúsculas
     * @param  string $chave
     * @return string
     */
    public static function normalizarChave($chave){
        $chave = trim((string)$chave);

        //E-MAIL OU CHAVE ALEATÓRIA (UUID)
        if(str_contains($chave, '@') or preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $chave)){
            return mb_strtolower($chave);
        }

        //A PARTIR DAQUI SÓ FAZ SENTIDO PARA CHAVES NUMÉRICAS (COM OU SEM PONTUAÇÃO)
        if(!preg_match('/^[\d\s().+\/-]+$/', $chave)) return $chave;
        $numeros = preg_replace('/\D/', '', $chave);

        //TELEFONE JÁ COM CÓDIGO DO PAÍS
        if(str_starts_with($chave, '+')) return '+'.$numeros;

        //CPF (11 DÍGITOS COM DÍGITOS VERIFICADORES VÁLIDOS) E CNPJ (14 DÍGITOS)
        if(strlen($numeros) == 11 and \App\Utils\Validador::isCpfValido($numeros)) return $numeros;
        if(strlen($numeros) == 14) return $numeros;

        //TELEFONE COM DDD (10 OU 11 DÍGITOS) OU JÁ COM 55 NA FRENTE (12 OU 13 DÍGITOS)
        if(in_array(strlen($numeros), [10, 11])) return '+55'.$numeros;
        if(in_array(strlen($numeros), [12, 13]) and str_starts_with($numeros, '55')) return '+'.$numeros;

        return $chave;
    }

    /**
     * Método responsável por definir o valor de $description
     * @param string $description
     */
    public function setDescription($description){
        $this->description = mb_substr(self::limparTexto($description), 0, 40);
        return $this;
    }

    /**
     * Método responsável por definir o valor de $merchantName (máximo 25 caracteres)
     * @param string $merchantName
     */
    public function setMerchantName($merchantName){
        $this->merchantName = mb_substr(self::limparTexto($merchantName), 0, 25);
        return $this;
    }

    /**
     * Método responsável por definir o valor de $merchantCity (máximo 15 caracteres)
     * @param string $merchantCity
     */
    public function setMerchantCity($merchantCity){
        $this->merchantCity = mb_substr(self::limparTexto($merchantCity), 0, 15);
        return $this;
    }

    /**
     * Método responsável por definir o valor de $txid (somente letras e números, até 25)
     * @param string $txid
     */
    public function setTxId($txid){
        $this->txid = substr(preg_replace('/[^A-Za-z0-9]/', '', (string)$txid), 0, 25) ?: '***';
        return $this;
    }

    /**
     * Método responsável por definir o valor de $amount
     * @param float $amount
     */
    public function setAmount($amount){
        //FAZ O TRATAMENTO DA VARIAVEL $amount
        $formatAmount = (float)str_replace(',', '.', (string)$amount);

        $this->amount = number_format($formatAmount, 2, '.', '');

        return $this;
    }

    /**
     * Método responsável por remover acentos e caracteres fora do padrão do BR Code
     * @param  string $texto
     * @return string
     */
    private static function limparTexto($texto){
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)$texto);
        return trim(preg_replace('/[^A-Za-z0-9 .\-]/', '', (string)$texto));
    }

    /**
     * Método responsável por retornar um valor completo do objeto de payLoad
     * @param string $id
     * @param string $value
     * @return string $id.$size.$value
     */
    private function getValue($id, $value){
        $size = str_pad(strlen($value), 2, '0', STR_PAD_LEFT);
        return $id.$size.$value;
    }

    /**
     * Método responsável por retornar os valores completos da informação da conta
     * @return string
     */
    private function getMerchantAccountInformation(){
        //DOMINIO DO BANCO
        $gui = $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION_GUI, 'br.gov.bcb.pix');

        //CHAVE PIX
        $key = $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION_KEY, $this->pixKey);

        //DESCRIÇÃO DO PAGAMENTO
        $description = strlen($this->description) ? $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION, $this->description) : '';

        //VALOR COMPLETO DA CONTA
        return $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION, $gui.$key.$description);
    }

    /**
     * Método responsável por retornar os valores completos do campo adicional
     * @return string
     */
    private function getAdditionalDataFieldTemplate(){
        //TXID
        $txId = $this->getValue(self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TXID, $this->txid);

        //RETORNA O VALOR COMPLETO
        return $this->getValue(self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE, $txId);
    }

    /**
     * Método responsável por gerar o código completo do payLoad Pix ("PIX copia e cola")
     * @return string
     */
    public function getPayLoad(){
        //CRIA O PAYLOAD
        $payLoad = $this->getValue(self::ID_PAYLOAD_FORMAT_INDICATOR, '01').
                   $this->getMerchantAccountInformation().
                   $this->getValue(self::ID_MERCHANT_CATEGORY_CODE, '0000').
                   $this->getValue(self::ID_TRANSACTION_CURRENCY, '986').
                   $this->getValue(self::ID_TRANSACTION_AMOUNT, $this->amount).
                   $this->getValue(self::ID_COUNTRY_CODE, 'BR').
                   $this->getValue(self::ID_MERCHANT_NAME, $this->merchantName).
                   $this->getValue(self::ID_MERCHANT_CITY, $this->merchantCity).
                   $this->getAdditionalDataFieldTemplate();

        //RETORNA O PAYLOAD + CRC16
        return $payLoad.$this->getCRC16($payLoad);
    }

    /**
     * Método responsável por gerar a imagem do QR Code do payload (SVG em base64)
     * SVG não depende da extensão GD do PHP
     * @return string data URI pronto para usar no <img src>
     */
    public function getQrCodeImagem(){
        //QR CODE
        $obQrCode = new QrCode($this->getPayLoad());

        //IMAGEM DO QRCODE
        $svg = (new Output\Svg)->output($obQrCode, 280);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Método responsável por calcular o valor da hash de validação do código pix
     * @return string
     */
    private function getCRC16($payload){
        //ADICIONA DADOS GERAIS NO PAYLOAD
        $payload .= self::ID_CRC16.'04';

        //DADOS DEFINIDOS PELO BACEN
        $polinomio = 0x1021;
        $resultado = 0xFFFF;

        //CHECKSUM
        if(($length = strlen($payload)) > 0){
            for($offset = 0; $offset < $length; $offset++){
                $resultado ^= (ord($payload[$offset]) << 8);
                for($bitwise = 0; $bitwise < 8; $bitwise++){
                    if(($resultado <<= 1) & 0x10000) $resultado ^= $polinomio;
                    $resultado &= 0xFFFF;
                }
            }
        }

        //RETORNA CÓDIGO CRC16 DE 4 CARACTERES (COMPLETA COM ZEROS À ESQUERDA)
        return self::ID_CRC16.'04'.str_pad(strtoupper(dechex($resultado)), 4, '0', STR_PAD_LEFT);
    }
}
