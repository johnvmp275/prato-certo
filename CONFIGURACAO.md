# Prato Certo — Configuração

O projeto tem duas partes:

| Pasta | O que é |
|---|---|
| `backend/prato_certo_backend` | API em PHP (porta 8000) |
| `frontend/prato_certo_vue` | Painel e cardápio em Vue + Vite (porta 5173) |

Ordem recomendada: **1. banco → 2. backend → 3. frontend**.

---

## 1. Banco de dados

O arquivo [`banco_prato_certo.sql`](banco_prato_certo.sql), na raiz do projeto, cria o banco `prato_certo` com todas as tabelas e dados de teste.

> ⚠️ O script começa com `DROP DATABASE IF EXISTS prato_certo`, ou seja, apaga o banco caso ele já exista.

Para importar (MySQL 8 ou MariaDB 10+):

```bash
mysql -u root -p < banco_prato_certo.sql
```

Também é possível importar pelo **phpMyAdmin** (aba *Importar*) ou pelo **MySQL Workbench** (*File → Run SQL Script*).

### Tabelas

| Tabela | Conteúdo |
|---|---|
| `configuracoes` | Dados do restaurante, taxas e chave PIX (registro único) |
| `usuarios` | Usuários do painel (`administrador` ou `atendente`) |
| `clientes` | Clientes; os que têm senha podem entrar no cardápio |
| `categorias` | Categorias do cardápio |
| `produtos` | Produtos, preço e controle de estoque |
| `mesas` | Mesas do salão |
| `cupons` | Cupons de desconto (`percentual` ou `fixo`) |
| `pedidos` | Pedidos de mesa, balcão e online |
| `itens_pedido` | Itens de cada pedido |
| `pagamentos` | Pagamentos (dinheiro, cartão ou PIX) |

### Acessos de teste

| Onde | E-mail | Senha |
|---|---|---|
| Painel — administrador | admin@pratocerto.com | admin123 |
| Painel — atendente | atendente@pratocerto.com | atendente123 |
| Cardápio — cliente | maria@email.com | cliente123 |
| Cardápio — cliente | joao@email.com | cliente123 |

Cupons de teste: `BEMVINDO10` (10% de desconto) e `DESCONTO5` (R$ 5,00 de desconto).

---

## 2. Backend (PHP)

Dentro de `backend/prato_certo_backend`, copie o arquivo de exemplo e renomeie para `.env`:

```bash
cd backend/prato_certo_backend
cp .env.exemple .env
```

### Variáveis do `.env`

| Variável | Exemplo | Para que serve |
|---|---|---|
| `URL` | `http://localhost:8000` | Endereço da API (usado, por exemplo, nos links das imagens dos produtos) |
| `URL_FRONT` | `http://localhost:5173` | Endereço do frontend |
| `DB_HOST` | `localhost` | Servidor do MySQL |
| `DB_NAME` | `prato_certo` | Nome do banco (o mesmo criado pelo script SQL) |
| `DB_USER` | `root` | Usuário do MySQL |
| `DB_PASS` | *(vazio)* | Senha do MySQL — preencha com a senha da sua instalação |
| `DB_PORT` | `3306` | Porta do MySQL |
| `MAINTENANCE` | `false` | Se `true`, a API responde que está em manutenção |

Exemplo:

```env
URL=http://localhost:8000
URL_FRONT=http://localhost:5173
DB_HOST=localhost
DB_NAME=prato_certo
DB_USER=root
DB_PASS=senha_desejada
DB_PORT=3306
MAINTENANCE=false
```

Instale as dependências e suba a API:

```bash
composer install
php -S localhost:8000 index.php
```

---

## 3. Frontend (Vue)

Dentro de `frontend/prato_certo_vue`, copie o arquivo de exemplo e renomeie para `.env`:

```bash
cd frontend/prato_certo_vue
cp .env.example .env
```

### Variáveis do `.env`

| Variável | Exemplo | Para que serve |
|---|---|---|
| `VITE_API_PROXY` | `http://127.0.0.1:8000` | Endereço da API PHP. Em desenvolvimento, o Vite redireciona as chamadas `/api` para este endereço |
| `VITE_API_URL` | *(vazio)* | URL base da API em produção. Deixe vazio para usar `/api/v1` no mesmo domínio |

Exemplo:

```env
VITE_API_PROXY=http://localhost:8000
VITE_API_URL=
```

Instale as dependências e rode:

```bash
npm install
npm run dev
```

O sistema fica disponível em **http://localhost:5173**.

---

## Problemas comuns

- **Erro de conexão com o banco:** confira `DB_USER`, `DB_PASS` e `DB_PORT` no `.env` do backend e se o MySQL está rodando.
- **Frontend não carrega os dados:** confira se a API está rodando na porta 8000 e se `VITE_API_PROXY` aponta para ela.
- **Login não funciona:** confira se o `banco_prato_certo.sql` foi importado.
