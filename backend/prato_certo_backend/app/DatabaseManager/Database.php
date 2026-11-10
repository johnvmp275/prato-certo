<?php

namespace App\DatabaseManager;

use \PDO;
use \PDOException;
use \App\Exception\FalhaPersistenciaException;

class Database{

  /**
   * Host de conexão com o banco de dados
   * @var string
   */
  private static $host;

  /**
   * Nome do banco de dados
   * @var string
   */
  private static $name;

  /**
   * Usuário do banco
   * @var string
   */
  private static $user;

  /**
   * Senha de acesso ao banco de dados
   * @var string
   */
  private static $pass;

  /**
   * Porta de acesso ao banco
   * @var integer
   */
  private static $port;

  /**
   * Instancia de conexão com o banco de dados (compartilhada entre as instâncias para permitir transações)
   * @var PDO
   */
  private static $connection;

  /**
   * Nome da tabela a ser manipulada
   * @var string
   */
  private $table;

  /**
   * Método responsável por configurar a classe
   * @param  string  $host
   * @param  string  $name
   * @param  string  $user
   * @param  string  $pass
   * @param  integer $port
   */
  public static function config($host,$name,$user,$pass,$port = 3306){
    self::$host = $host;
    self::$name = $name;
    self::$user = $user;
    self::$pass = $pass;
    self::$port = $port ?: 3306;
  }

  /**
   * Define a tabela e instancia e conexão
   * @param string $table
   */
  public function __construct($table = null){
    $this->table = $table;
    self::setConnection();
  }

  /**
   * Método responsável por criar uma conexão com o banco de dados
   */
  private static function setConnection(){
    //REAPROVEITA A CONEXÃO JÁ ABERTA
    if(self::$connection instanceof PDO) return;

    try{
      self::$connection = new PDO('mysql:host='.self::$host.';dbname='.self::$name.';port='.self::$port.';charset=utf8mb4',self::$user,self::$pass);
      self::$connection->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    }catch(PDOException $e){
      //REGISTRA O ERRO TÉCNICO NO LOG E EXIBE UMA MENSAGEM AMIGÁVEL
      error_log('[PRATO CERTO][DB] '.$e->getMessage());
      throw new FalhaPersistenciaException('Não foi possível conectar ao banco de dados. Tente novamente em instantes.');
    }
  }

  /**
   * Método responsável por iniciar uma transação
   */
  public static function beginTransaction(){
    self::setConnection();
    if(!self::$connection->inTransaction()) self::$connection->beginTransaction();
  }

  /**
   * Método responsável por confirmar a transação atual
   */
  public static function commit(){
    if(self::$connection and self::$connection->inTransaction()) self::$connection->commit();
  }

  /**
   * Método responsável por desfazer a transação atual
   */
  public static function rollBack(){
    if(self::$connection and self::$connection->inTransaction()) self::$connection->rollBack();
  }

  /**
   * Método responsável por executar queries dentro do banco de dados
   * @param  string $query
   * @param  array  $params
   * @return PDOStatement
   */
  public function execute($query,$params = []){
    try{
      $statement = self::$connection->prepare($query);
      $statement->execute($params);
      return $statement;
    }catch(PDOException $e){
      //REGISTRA O ERRO TÉCNICO NO LOG E EXIBE UMA MENSAGEM AMIGÁVEL
      error_log('[PRATO CERTO][DB] '.$e->getMessage().' | SQL: '.$query);

      //VIOLAÇÃO DE CHAVE ÚNICA (REGISTRO DUPLICADO)
      if($e->getCode() == 23000){
        throw new FalhaPersistenciaException('Já existe um registro com estes dados.', 409);
      }

      //DEFINE A MENSAGEM DE ACORDO COM O TIPO DA OPERAÇÃO (SALVAR OU CARREGAR)
      $leitura = stripos(ltrim($query), 'SELECT') === 0;
      throw new FalhaPersistenciaException($leitura
        ? 'Não foi possível carregar os dados. Tente novamente.'
        : 'Não foi possível salvar os dados. Tente novamente.');
    }
  }

  /**
   * Método responsável por inserir dados no banco
   * @param  array $values [ field => value ]
   * @return integer ID inserido
   */
  public function insert($values){
    //DADOS DA QUERY
    $fields = array_keys($values);
    $binds  = array_pad([],count($fields),'?');

    //MONTA A QUERY
    $query = 'INSERT INTO '.$this->table.' ('.implode(',',$fields).') VALUES ('.implode(',',$binds).')';

    //EXECUTA O INSERT
    $this->execute($query,array_values($values));

    //RETORNA O ID INSERIDO
    return (int)self::$connection->lastInsertId();
  }

  /**
   * Método responsável por executar uma consulta no banco
   * (SOBRECARGA: os parâmetros de bind são opcionais)
   * @param  string $where
   * @param  string $order
   * @param  string $limit
   * @param  string $fields
   * @param  array  $params Valores dos "?" do where
   * @return PDOStatement
   */
  public function select($where = null, $order = null, $limit = null, $fields = '*', $params = []){
    //DADOS DA QUERY
    $where = strlen($where ?? '') ? 'WHERE '.$where : '';
    $order = strlen($order ?? '') ? 'ORDER BY '.$order : '';
    $limit = strlen($limit ?? '') ? 'LIMIT '.$limit : '';

    //MONTA A QUERY
    $query = 'SELECT '.$fields.' FROM '.$this->table.' '.$where.' '.$order.' '.$limit;

    //EXECUTA A QUERY
    return $this->execute($query, $params);
  }

  /**
   * Método responsável por executar atualizações no banco de dados
   * @param  string $where
   * @param  array  $values [ field => value ]
   * @param  array  $params Valores dos "?" do where
   * @return boolean
   */
  public function update($where,$values,$params = []){
    //DADOS DA QUERY
    $fields = array_keys($values);

    //MONTA A QUERY
    $query = 'UPDATE '.$this->table.' SET '.implode('=?,',$fields).'=? WHERE '.$where;

    //EXECUTAR A QUERY
    $this->execute($query,array_merge(array_values($values), $params));

    //RETORNA SUCESSO
    return true;
  }

  /**
   * Método responsável por excluir dados do banco
   * @param  string $where
   * @param  array  $params Valores dos "?" do where
   * @return boolean
   */
  public function delete($where,$params = []){
    //MONTA A QUERY
    $query = 'DELETE FROM '.$this->table.' WHERE '.$where;

    //EXECUTA A QUERY
    $this->execute($query,$params);

    //RETORNA SUCESSO
    return true;
  }

}
