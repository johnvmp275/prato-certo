-- =====================================================================
-- PRATO CERTO - DADOS INICIAIS PARA TESTE
-- Senha dos usuários: 123456
-- =====================================================================

SET NAMES utf8mb4;
USE prato_certo;

-- CONFIGURAÇÃO DO RESTAURANTE
INSERT INTO configuracoes (id, nomeRestaurante, telefone, endereco, taxaServico, taxaEntrega, cardapioAberto) VALUES
(1, 'Prato Certo', '(11) 4002-8922', 'Rua das Palmeiras, 120 - Centro, Bragança Paulista/SP', 10.00, 7.90, 1);

-- USUÁRIOS (Administrador e Atendente)
INSERT INTO usuarios (nome, email, senha, perfil, status, criadoEm) VALUES
('João Vitor',     'admin@pratocerto.com',     '$2y$10$/znvD8u6pExYq6q48FWBMezzlp4rWz.xs.sI1X0.8DC1UoUYF766e', 'administrador', 'ativo', NOW()),
('Mariana Souza',  'atendente@pratocerto.com', '$2y$10$/znvD8u6pExYq6q48FWBMezzlp4rWz.xs.sI1X0.8DC1UoUYF766e', 'atendente',     'ativo', NOW());

-- CATEGORIAS
INSERT INTO categorias (id, nome, ordem, status) VALUES
(1, 'Lanches',    1, 'ativo'),
(2, 'Pratos',     2, 'ativo'),
(3, 'Massas',     3, 'ativo'),
(4, 'Saladas',    4, 'ativo'),
(5, 'Sobremesas', 5, 'ativo'),
(6, 'Bebidas',    6, 'ativo');

-- PRODUTOS
INSERT INTO produtos (categoriaId, nome, descricao, preco, imagem, controlaEstoque, estoque, status, criadoEm) VALUES
(1, 'X-Burguer Artesanal', 'Pão brioche, blend 180g, queijo prato e maionese da casa', 32.90, 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&h=400&fit=crop', 0, 0, 'ativo', NOW()),
(1, 'X-Bacon Duplo',       'Dois blends 120g, bacon crocante e cheddar',                38.90, 'https://images.unsplash.com/photo-1553979459-d2229ba7433b?w=400&h=400&fit=crop', 0, 0, 'ativo', NOW()),
(1, 'Smash Clássico',      'Dois smash 80g, queijo americano e cebola caramelizada',    29.90, 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=400&h=400&fit=crop', 0, 0, 'ativo', NOW()),
(1, 'Batata Frita',        'Porção de 400g com sal e alecrim',                          19.90, 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=400&h=400&fit=crop', 0, 0, 'ativo', NOW()),
(2, 'Picanha na Chapa',    'Picanha 300g, arroz, farofa e vinagrete',                   69.90, 'https://images.unsplash.com/photo-1600891964092-4316c288032e?w=400&h=400&fit=crop', 1, 15, 'ativo', NOW()),
(2, 'Costela no Bafo',     'Costela bovina assada lentamente com mandioca',             59.90, 'https://images.unsplash.com/photo-1544025162-d76694265947?w=400&h=400&fit=crop', 1, 8, 'ativo', NOW()),
(2, 'Bowl de Frango',      'Arroz integral, frango grelhado, legumes e molho tarê',     36.90, 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&h=400&fit=crop', 0, 0, 'ativo', NOW()),
(3, 'Spaghetti à Bolonhesa','Massa fresca com ragu de carne e parmesão',               42.90, 'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?w=400&h=400&fit=crop', 0, 0, 'ativo', NOW()),
(3, 'Penne ao Pesto',      'Penne, pesto de manjericão, tomate confit e castanhas',     39.90, 'https://images.unsplash.com/photo-1563379926898-05f4575a45d8?w=400&h=400&fit=crop', 0, 0, 'ativo', NOW()),
(3, 'Pizza Margherita',    'Molho de tomate, muçarela de búfala e manjericão',          54.90, 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=400&h=400&fit=crop', 0, 0, 'ativo', NOW()),
(3, 'Pizza Pepperoni',     'Muçarela e pepperoni fatiado',                              58.90, 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=400&h=400&fit=crop', 0, 0, 'ativo', NOW()),
(4, 'Salada Caesar',       'Alface americana, frango, croutons e molho caesar',         31.90, 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400&h=400&fit=crop', 0, 0, 'ativo', NOW()),
(5, 'Petit Gâteau',        'Bolinho de chocolate com sorvete de creme',                 24.90, 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=400&h=400&fit=crop', 1, 20, 'ativo', NOW()),
(5, 'Taça de Sorvete',     'Três bolas com calda e farofa doce',                        18.90, 'https://images.unsplash.com/photo-1551024601-bec78aea704b?w=400&h=400&fit=crop', 0, 0, 'ativo', NOW()),
(6, 'Suco Natural 500ml',  'Laranja, limão, abacaxi ou maracujá',                       12.90, 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=400&h=400&fit=crop', 0, 0, 'ativo', NOW()),
(6, 'Smoothie de Frutas',  'Morango, banana e iogurte natural',                         16.90, 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=400&h=400&fit=crop', 0, 0, 'ativo', NOW()),
(6, 'Refrigerante Lata',   'Coca-Cola, Guaraná ou Sprite 350ml',                         7.90, 'https://images.unsplash.com/photo-1437418747212-8d9709afab22?w=400&h=400&fit=crop', 1, 48, 'ativo', NOW()),
(6, 'Drink da Casa',       'Caipirinha de limão com cachaça artesanal',                 22.90, 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=400&h=400&fit=crop', 0, 0, 'ativo', NOW());

-- MESAS
INSERT INTO mesas (numero, capacidade, situacao, status) VALUES
(1, 2, 'livre', 'ativo'), (2, 2, 'livre', 'ativo'), (3, 4, 'livre', 'ativo'), (4, 4, 'livre', 'ativo'),
(5, 4, 'livre', 'ativo'), (6, 4, 'livre', 'ativo'), (7, 6, 'livre', 'ativo'), (8, 6, 'livre', 'ativo'),
(9, 8, 'livre', 'ativo'), (10, 8, 'livre', 'ativo'), (11, 4, 'livre', 'ativo'), (12, 10, 'livre', 'ativo');

-- CLIENTES
INSERT INTO clientes (nome, telefone, email, documento, endereco, status, criadoEm) VALUES
('Vicente Lobo',     '11987654321', 'vicente.lobo@email.com',   '52998224725', 'Rua Coronel Leme, 45 - Centro', 'ativo', NOW()),
('Beatriz Carvalho', '11991234567', 'beatriz.c@email.com',      NULL,          'Av. Dom Pedro I, 900 - Taboão', 'ativo', NOW()),
('Carlos Eduardo',   '11998765432', 'carlos.edu@email.com',     NULL,          NULL,                            'ativo', NOW()),
('Fernanda Lima',    '11993456789', NULL,                       NULL,          'Rua Santa Clara, 210 - Jardim', 'ativo', NOW()),
('Rafael Oliveira',  '11996543210', 'rafa.oliveira@email.com',  NULL,          NULL,                            'ativo', NOW());

-- CUPONS
INSERT INTO cupons (codigo, tipo, valor, validade, status) VALUES
('BEMVINDO10', 'percentual', 10.00, NULL,         'ativo'),
('DESCONTO15', 'fixo',       15.00, '2027-12-31', 'ativo'),
('EXPIRADO',   'percentual', 20.00, '2025-01-01', 'ativo');
