<?php

namespace App\DatabaseManager;

class Pagination{

  /**
   * Número máximo de registros por página
   * @var integer
   */
  private $limit;

  /**
   * Quantidade total de resultados do banco
   * @var integer
   */
  private $results;

  /**
   * Quantidade de páginas
   * @var integer
   */
  private $pages;

  /**
   * Página atual
   * @var integer
   */
  private $currentPage;

  /**
   * Construtor da classe
   * @param integer  $results
   * @param integer  $currentPage
   * @param integer  $limit
   */
  public function __construct($results, $currentPage = 1, $limit = 10){
    $this->results     = (int)$results;
    $this->limit       = max(1, min(100, (int)$limit));
    $this->currentPage = (is_numeric($currentPage) and $currentPage > 0) ? (int)$currentPage : 1;
    $this->calculate();
  }

  /**
   * Método responsável por calcular a páginação
   */
  private function calculate(){
    //CALCULA O TOTAL DE PÁGINAS
    $this->pages = $this->results > 0 ? (int)ceil($this->results / $this->limit) : 1;

    //VERIFICA SE A PÁGINA ATUAL NÃO EXCEDE O NÚMERO DE PÁGINAS
    $this->currentPage = $this->currentPage <= $this->pages ? $this->currentPage : $this->pages;
  }

  /**
   * Método responsável por retornar a cláusula limit da SQL
   * @return string
   */
  public function getLimit(){
    $offset = ($this->limit * ($this->currentPage - 1));
    return $offset.','.$this->limit;
  }

  /**
   * Método responsável por retornar os dados da paginação
   * @return array
   */
  public function getInfo(){
    return [
      'paginaAtual'       => $this->currentPage,
      'itensPorPagina'    => $this->limit,
      'quantidadePaginas' => $this->pages,
      'total'             => $this->results
    ];
  }

}
