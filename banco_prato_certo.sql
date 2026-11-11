-- =====================================================================
--  PRATO CERTO - Banco de dados completo (estrutura + dados de teste)
--  Compatível com MySQL 8 e MariaDB 10+
--
--  Como usar:
--    mysql -u root -p < banco_prato_certo.sql
--    (ou importar pelo phpMyAdmin / MySQL Workbench)
--
--  ACESSOS DE TESTE
--  Painel (usuários):
--    administrador -> admin@pratocerto.com     / admin123
--    atendente     -> atendente@pratocerto.com / atendente123
--  Cardápio (clientes com conta):
--    maria@email.com / cliente123
--    joao@email.com  / cliente123
--
--  Cupons: BEMVINDO10 (10%), DESCONTO5 (R$ 5,00)
-- =====================================================================

DROP DATABASE IF EXISTS prato_certo;
CREATE DATABASE prato_certo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE prato_certo;

-- ---------------------------------------------------------------------
-- Configurações do restaurante (registro único, id = 1)
-- ---------------------------------------------------------------------
CREATE TABLE configuracoes (
    id               INT UNSIGNED NOT NULL PRIMARY KEY,
    nomeRestaurante  VARCHAR(150) NOT NULL,
    telefone         VARCHAR(20)  NULL,
    endereco         VARCHAR(255) NULL,
    taxaServico      DECIMAL(5,2)  NOT NULL DEFAULT 10.00,
    taxaEntrega      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    cardapioAberto   TINYINT(1)   NOT NULL DEFAULT 1,
    pixChave         VARCHAR(100) NULL,
    pixTitular       VARCHAR(25)  NULL,
    pixCidade        VARCHAR(15)  NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Usuários do painel (perfil: administrador | atendente)
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nome      VARCHAR(150) NOT NULL,
    email     VARCHAR(150) NOT NULL,
    senha     VARCHAR(255) NOT NULL,
    perfil    VARCHAR(20)  NOT NULL DEFAULT 'atendente',
    status    VARCHAR(10)  NOT NULL DEFAULT 'ativo',
    criadoEm  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_usuarios_email (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Clientes (senha NULL = cliente cadastrado no balcão, sem conta)
-- ---------------------------------------------------------------------
CREATE TABLE clientes (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(150) NOT NULL,
    telefone   VARCHAR(20)  NULL,
    email      VARCHAR(150) NULL,
    documento  VARCHAR(20)  NULL,
    senha      VARCHAR(255) NULL,
    endereco   VARCHAR(255) NULL,
    status     VARCHAR(10)  NOT NULL DEFAULT 'ativo',
    criadoEm   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_clientes_email (email),
    KEY idx_clientes_telefone (telefone)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Categorias
-- ---------------------------------------------------------------------
CREATE TABLE categorias (
    id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nome    VARCHAR(100) NOT NULL,
    ordem   INT          NOT NULL DEFAULT 0,
    status  VARCHAR(10)  NOT NULL DEFAULT 'ativo'
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Produtos
-- ---------------------------------------------------------------------
CREATE TABLE produtos (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    categoriaId      INT UNSIGNED NOT NULL,
    nome             VARCHAR(150) NOT NULL,
    descricao        TEXT         NULL,
    preco            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    imagem           VARCHAR(255) NULL,
    controlaEstoque  TINYINT(1)   NOT NULL DEFAULT 0,
    estoque          INT          NOT NULL DEFAULT 0,
    status           VARCHAR(10)  NOT NULL DEFAULT 'ativo',
    criadoEm         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_produtos_categoria FOREIGN KEY (categoriaId) REFERENCES categorias (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Mesas (situacao: livre | ocupada)
-- ---------------------------------------------------------------------
CREATE TABLE mesas (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    numero      INT          NOT NULL,
    capacidade  INT          NOT NULL DEFAULT 4,
    situacao    VARCHAR(10)  NOT NULL DEFAULT 'livre',
    status      VARCHAR(10)  NOT NULL DEFAULT 'ativo',
    UNIQUE KEY uk_mesas_numero (numero)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Cupons (tipo: percentual | fixo)
-- ---------------------------------------------------------------------
CREATE TABLE cupons (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    codigo    VARCHAR(30)   NOT NULL,
    tipo      VARCHAR(15)   NOT NULL DEFAULT 'percentual',
    valor     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    validade  DATE          NULL,
    status    VARCHAR(10)   NOT NULL DEFAULT 'ativo',
    UNIQUE KEY uk_cupons_codigo (codigo)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Pedidos (tabela única para mesa | balcao | online)
-- status: pendente | aberto | em_espera | finalizado | cancelado
-- ---------------------------------------------------------------------
CREATE TABLE pedidos (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    codigo           VARCHAR(10)   NOT NULL,
    tipo             VARCHAR(10)   NOT NULL,
    status           VARCHAR(15)   NOT NULL DEFAULT 'aberto',
    clienteId        INT UNSIGNED  NULL,
    usuarioId        INT UNSIGNED  NULL,
    cupomId          INT UNSIGNED  NULL,
    descontoTipo     VARCHAR(15)   NULL,
    descontoValor    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    desconto         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    taxa             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    observacao       TEXT          NULL,
    -- pedido de mesa
    mesaId           INT UNSIGNED  NULL,
    numPessoas       INT           NULL,
    cobrarServico    TINYINT(1)    NULL,
    -- pedido online
    tipoEntrega      VARCHAR(10)   NULL,
    nomeContato      VARCHAR(150)  NULL,
    telefoneContato  VARCHAR(20)   NULL,
    enderecoEntrega  VARCHAR(255)  NULL,
    taxaEntrega      DECIMAL(10,2) NULL,
    formaPagamento   VARCHAR(10)   NULL,
    trocoPara        DECIMAL(10,2) NULL,
    -- datas
    criadoEm         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizadoEm     DATETIME      NULL,
    finalizadoEm     DATETIME      NULL,
    UNIQUE KEY uk_pedidos_codigo (codigo),
    KEY idx_pedidos_status (status),
    CONSTRAINT fk_pedidos_cliente FOREIGN KEY (clienteId) REFERENCES clientes (id),
    CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuarioId) REFERENCES usuarios (id),
    CONSTRAINT fk_pedidos_cupom   FOREIGN KEY (cupomId)   REFERENCES cupons (id),
    CONSTRAINT fk_pedidos_mesa    FOREIGN KEY (mesaId)    REFERENCES mesas (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Itens do pedido
-- ---------------------------------------------------------------------
CREATE TABLE itens_pedido (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    pedidoId       INT UNSIGNED  NOT NULL,
    produtoId      INT UNSIGNED  NULL,
    nomeProduto    VARCHAR(150)  NOT NULL,
    quantidade     INT           NOT NULL DEFAULT 1,
    precoUnitario  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    observacao     VARCHAR(255)  NULL,
    CONSTRAINT fk_itens_pedido   FOREIGN KEY (pedidoId)  REFERENCES pedidos (id) ON DELETE CASCADE,
    CONSTRAINT fk_itens_produto  FOREIGN KEY (produtoId) REFERENCES produtos (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Pagamentos (forma: dinheiro | cartao | pix)
-- ---------------------------------------------------------------------
CREATE TABLE pagamentos (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    pedidoId         INT UNSIGNED  NOT NULL,
    forma            VARCHAR(10)   NOT NULL,
    valor            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    valorRecebido    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    troco            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    bandeira         VARCHAR(30)   NULL,
    modalidade       VARCHAR(10)   NULL,
    codigoTransacao  VARCHAR(100)  NULL,
    criadoEm         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pagamentos_pedido FOREIGN KEY (pedidoId) REFERENCES pedidos (id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- =====================================================================
--  DADOS DE TESTE
-- =====================================================================

INSERT INTO configuracoes (id, nomeRestaurante, telefone, endereco, taxaServico, taxaEntrega, cardapioAberto, pixChave, pixTitular, pixCidade) VALUES
(1, 'Prato Certo', '(11) 99999-0000', 'Rua das Flores, 100 - Centro', 10.00, 7.00, 1, 'pratocerto@email.com', 'PRATO CERTO', 'SAO PAULO');

-- Senhas: admin123 / atendente123 (hash bcrypt gerado com password_hash do PHP)
INSERT INTO usuarios (nome, email, senha, perfil, status) VALUES
('Administrador', 'admin@pratocerto.com',     '$2y$10$9kjKh7IWIgNgA/j49CmpXeufwEJ2cUwUHP4Lwj/qW.IHAmF3f1rmy', 'administrador', 'ativo'),
('Atendente',     'atendente@pratocerto.com', '$2y$10$rWD1n6mHq7BehzHv77hKiOq300F4YduS7OnXlUh7.A1pbmjzwFfLa', 'atendente',     'ativo');

-- Senha dos clientes com conta: cliente123
INSERT INTO clientes (nome, telefone, email, documento, senha, endereco, status) VALUES
('Maria Silva',  '11988887777', 'maria@email.com', '12345678909', '$2y$10$3HIv9CSHiBxv79urhv/g5OyQG/YanYeTGLikqgckqPEJxq/doUg4K', 'Av. Brasil, 500 - Apto 12', 'ativo'),
('João Souza',   '11977776666', 'joao@email.com',  NULL,          '$2y$10$3HIv9CSHiBxv79urhv/g5OyQG/YanYeTGLikqgckqPEJxq/doUg4K', 'Rua Verde, 45',             'ativo'),
('Carlos Lima',  '11966665555', NULL,              NULL,          NULL,                                                           NULL,                        'ativo');

INSERT INTO categorias (nome, ordem, status) VALUES
('Lanches',          1, 'ativo'),
('Pratos Principais',2, 'ativo'),
('Bebidas',          3, 'ativo'),
('Sobremesas',       4, 'ativo');

INSERT INTO produtos (categoriaId, nome, descricao, preco, imagem, controlaEstoque, estoque, status) VALUES
(1, 'X-Burger',             'Pão, hambúrguer 150g, queijo e salada',        25.90, NULL, 0, 0,  'ativo'),
(1, 'X-Bacon',              'Pão, hambúrguer 150g, queijo, bacon e salada', 29.90, NULL, 0, 0,  'ativo'),
(1, 'Batata Frita',         'Porção de batata frita crocante (300g)',       18.00, NULL, 0, 0,  'ativo'),
(2, 'Prato Feito',          'Arroz, feijão, bife, salada e batata frita',   32.00, NULL, 0, 0,  'ativo'),
(2, 'Frango Grelhado',      'Filé de frango grelhado com legumes',          34.50, NULL, 0, 0,  'ativo'),
(2, 'Feijoada',             'Feijoada completa (serve 1 pessoa)',           42.00, NULL, 0, 0,  'ativo'),
(3, 'Refrigerante Lata',    'Lata 350ml',                                    6.50, NULL, 1, 48, 'ativo'),
(3, 'Suco Natural',         'Laranja, limão ou abacaxi (500ml)',             9.00, NULL, 0, 0,  'ativo'),
(3, 'Água Mineral',         'Garrafa 500ml',                                 4.00, NULL, 1, 60, 'ativo'),
(4, 'Pudim',                'Pudim de leite condensado',                    12.00, NULL, 1, 10, 'ativo'),
(4, 'Brownie com Sorvete',  'Brownie de chocolate com bola de sorvete',     16.90, NULL, 0, 0,  'ativo');

INSERT INTO mesas (numero, capacidade, situacao, status) VALUES
(1, 2, 'livre', 'ativo'),
(2, 4, 'livre', 'ativo'),
(3, 4, 'livre', 'ativo'),
(4, 6, 'livre', 'ativo'),
(5, 8, 'livre', 'ativo');

INSERT INTO cupons (codigo, tipo, valor, validade, status) VALUES
('BEMVINDO10', 'percentual', 10.00, '2030-12-31', 'ativo'),
('DESCONTO5',  'fixo',        5.00, NULL,         'ativo');

-- Um pedido de balcão já finalizado (para o dashboard ter dados)
INSERT INTO pedidos (codigo, tipo, status, clienteId, usuarioId, subtotal, taxa, total, criadoEm, atualizadoEm, finalizadoEm) VALUES
('1000001', 'balcao', 'finalizado', 3, 2, 38.90, 0.00, 38.90, NOW(), NOW(), NOW());

INSERT INTO itens_pedido (pedidoId, produtoId, nomeProduto, quantidade, precoUnitario, observacao) VALUES
(1, 1, 'X-Burger',          1, 25.90, 'Sem cebola'),
(1, 7, 'Refrigerante Lata', 2,  6.50, NULL);

INSERT INTO pagamentos (pedidoId, forma, valor, valorRecebido, troco) VALUES
(1, 'dinheiro', 38.90, 50.00, 11.10);
