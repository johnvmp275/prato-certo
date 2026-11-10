<?php

namespace App\Utils;

use \App\Exception\CampoObrigatorioException;
use \App\Exception\DadoInvalidoException;

class Validador{

    /**
     * Método responsável por validar se um valor obrigatório foi informado
     * @param  mixed  $valor
     * @param  string $campo
     * @param  string $label
     * @return string Valor limpo
     */
    public static function obrigatorio($valor, $campo, $label = null){
        //REMOVE ESPAÇOS DAS STRINGS
        $valor = is_string($valor) ? trim($valor) : $valor;

        //VERIFICA SE O VALOR ESTÁ VAZIO
        if($valor === null or $valor === '' or $valor === []){
            throw new CampoObrigatorioException($campo, $label);
        }

        return $valor;
    }

    /**
     * Método responsável por validar o tamanho de um texto
     * @param string  $valor
     * @param string  $campo
     * @param integer $minimo
     * @param integer $maximo
     * @return string
     */
    public static function tamanho($valor, $campo, $minimo = 1, $maximo = 255){
        $tamanho = mb_strlen(trim((string)$valor));
        if($tamanho < $minimo or $tamanho > $maximo){
            throw new DadoInvalidoException("O campo '".$campo."' deve ter entre ".$minimo." e ".$maximo." caracteres.", null, $campo);
        }
        return trim((string)$valor);
    }

    /**
     * Método responsável por validar um e-mail
     * @param  string $email
     * @param  string $campo
     * @return string
     */
    public static function email($email, $campo = 'email'){
        $email = mb_strtolower(trim((string)$email));
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            throw new DadoInvalidoException("Informe um e-mail válido.", null, $campo);
        }
        return $email;
    }

    /**
     * Método responsável por validar um número de telefone (10 ou 11 dígitos)
     * @param  string $telefone
     * @param  string $campo
     * @return string Somente os dígitos
     */
    public static function telefone($telefone, $campo = 'telefone'){
        $numeros = preg_replace('/\D/', '', (string)$telefone);
        if(strlen($numeros) < 10 or strlen($numeros) > 11){
            throw new DadoInvalidoException("Informe um telefone válido com DDD.", null, $campo);
        }
        return $numeros;
    }

    /**
     * Método responsável por verificar se um CPF é válido (dígitos verificadores)
     * @param  string $cpf
     * @return boolean
     */
    public static function isCpfValido($cpf){
        //MANTÉM APENAS OS NÚMEROS
        $cpf = preg_replace('/\D/', '', (string)$cpf);

        //VERIFICA O TAMANHO E SEQUÊNCIAS REPETIDAS
        if(strlen($cpf) != 11 or preg_match('/^(\d)\1{10}$/', $cpf)) return false;

        //CALCULA OS DOIS DÍGITOS VERIFICADORES
        for($t = 9; $t < 11; $t++){
            $soma = 0;
            for($i = 0; $i < $t; $i++){
                $soma += $cpf[$i] * (($t + 1) - $i);
            }
            $digito = ((10 * $soma) % 11) % 10;
            if($cpf[$t] != $digito) return false;
        }

        return true;
    }

    /**
     * Método responsável por validar um número maior ou igual a um mínimo
     * @param  mixed  $valor
     * @param  string $campo
     * @param  float  $minimo
     * @param  string $label
     * @return float
     */
    public static function numero($valor, $campo, $minimo = 0, $label = null){
        //ACEITA VÍRGULA COMO SEPARADOR DECIMAL
        $valor = is_string($valor) ? str_replace(',', '.', $valor) : $valor;

        if(!is_numeric($valor) or $valor < $minimo){
            throw new DadoInvalidoException("O campo '".($label ?? $campo)."' deve ser um número maior ou igual a ".$minimo.".", null, $campo);
        }
        return (float)$valor;
    }

    /**
     * Método responsável por validar um inteiro maior ou igual a um mínimo
     * @param  mixed   $valor
     * @param  string  $campo
     * @param  integer $minimo
     * @param  string  $label
     * @return integer
     */
    public static function inteiro($valor, $campo, $minimo = 0, $label = null){
        if(filter_var($valor, FILTER_VALIDATE_INT) === false or $valor < $minimo){
            throw new DadoInvalidoException("O campo '".($label ?? $campo)."' deve ser um número inteiro maior ou igual a ".$minimo.".", null, $campo);
        }
        return (int)$valor;
    }

    /**
     * Método responsável por validar se um valor está entre as opções permitidas
     * @param  string $valor
     * @param  array  $opcoes
     * @param  string $campo
     * @return string
     */
    public static function opcao($valor, $opcoes, $campo){
        if(!in_array($valor, $opcoes, true)){
            throw new DadoInvalidoException("Valor inválido para o campo '".$campo."'. Opções: ".implode(', ', $opcoes).".", null, $campo);
        }
        return $valor;
    }

    /**
     * Método responsável por validar uma data no formato AAAA-MM-DD
     * @param  string $data
     * @param  string $campo
     * @return string
     */
    public static function data($data, $campo){
        $obData = \DateTime::createFromFormat('Y-m-d', (string)$data);
        if(!$obData or $obData->format('Y-m-d') !== $data){
            throw new DadoInvalidoException("Informe uma data válida para o campo '".$campo."'.", null, $campo);
        }
        return $data;
    }
}
