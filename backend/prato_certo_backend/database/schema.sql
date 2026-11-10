-- =====================================================================
-- PRATO CERTO - PDV E CARDÁPIO ONLINE
-- Estrutura do banco de dados (MySQL 8 / MariaDB 10.6+)
-- =====================================================================

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS prato_certo
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE prato_certo;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS pagamentos, itens_pedido, pedidos, cupons, mesas, produtos, categorias, clientes, usuarios, configuracoes;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- USUÁRIOS (Usuario <- Administrador | Atendente, discriminador: perfil)
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome       VARCHAR(120) NOT NULL,
  email      VARCHAR(150) NOT NULL UNIQUE,
  senha      VARCHAR(255) NOT NULL,
  perfil     ENUM('administrador','atendente') NOT NULL DEFAULT 'atendente',
  status     ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
  criadoEm   DATETIME NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CLIENTES
-- ---------------------------------------------------------------------
CREATE TABLE clientes (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome       VARCHAR(120) NOT NULL,
  telefone   VARCHAR(11)  NOT NULL,
  email      VARCHAR(150) NULL,
  documento  VARCHAR(11)  NULL UNIQUE,
  endereco   VARCHAR(255) NULL,
  status     ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
  criadoEm   DATETIME NOT NULL,
  INDEX idx_clientes_nome (nome)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CATEGORIAS
-- ---------------------------------------------------------------------
CREATE TABLE categorias (
  id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome    VARCHAR(60) NOT NULL UNIQUE,
  ordem   INT NOT NULL DEFAULT 0,
  status  ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo'
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PRODUTOS
-- ---------------------------------------------------------------------
CREATE TABLE produtos (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoriaId      INT UNSIGNED NOT NULL,
  nome             VARCHAR(120) NOT NULL,
  descricao        VARCHAR(255) NULL,
  preco            DECIMAL(10,2) NOT NULL,
  imagem           VARCHAR(500) NULL,
  controlaEstoque  TINYINT(1) NOT NULL DEFAULT 0,
  estoque          INT NOT NULL DEFAULT 0,
  status           ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
  criadoEm         DATETIME NOT NULL,
  CONSTRAINT fk_produtos_categoria FOREIGN KEY (categoriaId) REFERENCES categorias(id),
  INDEX idx_produtos_nome (nome)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- MESAS
-- ---------------------------------------------------------------------
CREATE TABLE mesas (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  numero      INT NOT NULL UNIQUE,
  capacidade  INT NOT NULL DEFAULT 4,
  situacao    ENUM('livre','ocupada') NOT NULL DEFAULT 'livre',
  status      ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo'
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CUPONS
-- ---------------------------------------------------------------------
CREATE TABLE cupons (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo    VARCHAR(30) NOT NULL UNIQUE,
  tipo      ENUM('percentual','fixo') NOT NULL,
  valor     DECIMAL(10,2) NOT NULL,
  validade  DATE NULL,
  status    ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo'
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CONFIGURAÇÕES DO RESTAURANTE (registro único)
-- ---------------------------------------------------------------------
CREATE TABLE configuracoes (
  id               INT UNSIGNED PRIMARY KEY,
  nomeRestaurante  VARCHAR(120) NOT NULL,
  telefone         VARCHAR(20)  NULL,
  endereco         VARCHAR(255) NULL,
  taxaServico      DECIMAL(5,2)  NOT NULL DEFAULT 10.00,
  taxaEntrega      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  cardapioAberto   TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PEDIDOS (Pedido <- PedidoMesa | PedidoBalcao | PedidoOnline, discriminador: tipo)
-- ---------------------------------------------------------------------
CREATE TABLE pedidos (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo           VARCHAR(10) NOT NULL UNIQUE,
  tipo             ENUM('mesa','balcao','online') NOT NULL,
  status           ENUM('pendente','aberto','em_espera','finalizado','cancelado') NOT NULL DEFAULT 'aberto',
  clienteId        INT UNSIGNED NULL,
  usuarioId        INT UNSIGNED NULL,
  cupomId          INT UNSIGNED NULL,
  -- PedidoMesa
  mesaId           INT UNSIGNED NULL,
  numPessoas       INT NULL,
  cobrarServico    TINYINT(1) NOT NULL DEFAULT 1,
  -- PedidoOnline
  tipoEntrega      ENUM('retirada','entrega') NULL,
  nomeContato      VARCHAR(120) NULL,
  telefoneContato  VARCHAR(11)  NULL,
  enderecoEntrega  VARCHAR(255) NULL,
  taxaEntrega      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  -- Valores
  descontoTipo     ENUM('valor','percentual','cupom') NULL,
  descontoValor    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  desconto         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  subtotal         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  taxa             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  observacao       VARCHAR(255) NULL,
  criadoEm         DATETIME NOT NULL,
  atualizadoEm     DATETIME NULL,
  finalizadoEm     DATETIME NULL,
  CONSTRAINT fk_pedidos_cliente FOREIGN KEY (clienteId) REFERENCES clientes(id),
  CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuarioId) REFERENCES usuarios(id),
  CONSTRAINT fk_pedidos_cupom   FOREIGN KEY (cupomId)   REFERENCES cupons(id),
  CONSTRAINT fk_pedidos_mesa    FOREIGN KEY (mesaId)    REFERENCES mesas(id),
  INDEX idx_pedidos_status (status),
  INDEX idx_pedidos_criado (criadoEm)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ITENS DO PEDIDO (composição: removidos junto com o pedido)
-- ---------------------------------------------------------------------
CREATE TABLE itens_pedido (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedidoId       INT UNSIGNED NOT NULL,
  produtoId      INT UNSIGNED NOT NULL,
  nomeProduto    VARCHAR(120) NOT NULL,
  quantidade     INT NOT NULL,
  precoUnitario  DECIMAL(10,2) NOT NULL,
  observacao     VARCHAR(150) NULL,
  CONSTRAINT fk_itens_pedido  FOREIGN KEY (pedidoId)  REFERENCES pedidos(id) ON DELETE CASCADE,
  CONSTRAINT fk_itens_produto FOREIGN KEY (produtoId) REFERENCES produtos(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PAGAMENTOS (Pagamento <- Dinheiro | Cartao | Pix, discriminador: forma)
-- ---------------------------------------------------------------------
CREATE TABLE pagamentos (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedidoId         INT UNSIGNED NOT NULL,
  forma            ENUM('dinheiro','cartao','pix') NOT NULL,
  valor            DECIMAL(10,2) NOT NULL,
  valorRecebido    DECIMAL(10,2) NOT NULL,
  troco            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  bandeira         VARCHAR(30) NULL,
  modalidade       ENUM('credito','debito') NULL,
  codigoTransacao  VARCHAR(40) NULL,
  criadoEm         DATETIME NOT NULL,
  CONSTRAINT fk_pagamentos_pedido FOREIGN KEY (pedidoId) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB;
