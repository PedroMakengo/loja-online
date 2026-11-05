-- =====================================================================
-- DIOTEKNO — Loja Virtual
-- 001_schema_base.sql — Estrutura base (MySQL 5.7+ / MariaDB 10.3+)
--
-- Cobre o núcleo da loja e já prepara as tabelas dos módulos seguintes
-- (conta do cliente, checkout, pagamentos, logística, cupões,
-- avaliações, blog e painel administrativo).
-- Módulos avançados (fidelidade, afiliados, marketplace) entram em
-- ficheiros 00X_ sequenciais próprios.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Catálogo
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id      BIGINT UNSIGNED NULL,
    nome           VARCHAR(120) NOT NULL,
    slug           VARCHAR(140) NOT NULL UNIQUE,
    descricao      VARCHAR(255) NULL,
    ordem          SMALLINT NOT NULL DEFAULT 0,
    destaque       TINYINT(1) NOT NULL DEFAULT 0,
    activo         TINYINT(1) NOT NULL DEFAULT 1,
    meta_titulo    VARCHAR(160) NULL,
    meta_descricao VARCHAR(255) NULL,
    criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_categorias_parent FOREIGN KEY (parent_id) REFERENCES categorias(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marcas (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(80) NOT NULL,
    slug       VARCHAR(100) NOT NULL UNIQUE,
    descricao  VARCHAR(255) NULL,
    logo       VARCHAR(255) NULL,
    destaque   TINYINT(1) NOT NULL DEFAULT 0,
    activo     TINYINT(1) NOT NULL DEFAULT 1,
    criado_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS produtos (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id      BIGINT UNSIGNED NOT NULL,
    marca_id          BIGINT UNSIGNED NULL,
    nome              VARCHAR(200) NOT NULL,
    slug              VARCHAR(220) NOT NULL UNIQUE,
    sku               VARCHAR(60)  NOT NULL UNIQUE,
    resumo            VARCHAR(300) NULL,
    descricao         TEXT NULL,
    preco             DECIMAL(14,2) NOT NULL,                 -- preço actual, com IVA
    preco_anterior    DECIMAL(14,2) NULL,                     -- preço riscado (promoção)
    custo             DECIMAL(14,2) NULL,                     -- para relatórios de margem
    stock             INT NOT NULL DEFAULT 0,
    stock_minimo      INT NOT NULL DEFAULT 2,
    imagem            VARCHAR(255) NULL,                      -- imagem principal
    garantia_meses    SMALLINT NOT NULL DEFAULT 12,
    peso_kg           DECIMAL(8,2) NULL,
    destaque          TINYINT(1) NOT NULL DEFAULT 0,
    novidade          TINYINT(1) NOT NULL DEFAULT 0,
    oferta_ate        DATETIME NULL,                          -- "Promoção do dia" com contagem
    vendas            INT NOT NULL DEFAULT 0,                 -- alimenta "Mais vendidos"
    visualizacoes     INT NOT NULL DEFAULT 0,
    avaliacao_media   DECIMAL(3,2) NOT NULL DEFAULT 0,
    total_avaliacoes  INT NOT NULL DEFAULT 0,
    estado            ENUM('activo','rascunho','inactivo') NOT NULL DEFAULT 'activo',
    meta_titulo       VARCHAR(160) NULL,
    meta_descricao    VARCHAR(255) NULL,
    criado_em         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_em    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_produtos_categoria (categoria_id, estado),
    KEY idx_produtos_marca (marca_id, estado),
    KEY idx_produtos_preco (preco),
    FULLTEXT KEY ft_produtos_pesquisa (nome, resumo, sku),
    CONSTRAINT fk_produtos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id),
    CONSTRAINT fk_produtos_marca FOREIGN KEY (marca_id) REFERENCES marcas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS produto_imagens (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    produto_id  BIGINT UNSIGNED NOT NULL,
    ficheiro    VARCHAR(255) NOT NULL,
    alt         VARCHAR(200) NULL,
    ordem       SMALLINT NOT NULL DEFAULT 0,
    CONSTRAINT fk_imagens_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS produto_especificacoes (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    produto_id  BIGINT UNSIGNED NOT NULL,
    grupo       VARCHAR(80) NOT NULL DEFAULT 'Geral',
    nome        VARCHAR(100) NOT NULL,
    valor       VARCHAR(255) NOT NULL,
    ordem       SMALLINT NOT NULL DEFAULT 0,
    KEY idx_specs_nome (nome),
    CONSTRAINT fk_specs_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Conteúdo e marketing
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS banners (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo       VARCHAR(140) NOT NULL,
    subtitulo    VARCHAR(255) NULL,
    texto_botao  VARCHAR(40) NULL,
    link         VARCHAR(255) NULL,
    imagem       VARCHAR(255) NULL,
    produto_id   BIGINT UNSIGNED NULL,          -- banner a destacar um produto (usa preço vivo)
    tema         ENUM('azul','grafite','coral','verde') NOT NULL DEFAULT 'azul',
    ordem        SMALLINT NOT NULL DEFAULT 0,
    inicio       DATETIME NULL,
    fim          DATETIME NULL,
    activo       TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_banners_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cupoes (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo           VARCHAR(40) NOT NULL UNIQUE,
    descricao        VARCHAR(160) NULL,
    tipo             ENUM('percentual','fixo') NOT NULL DEFAULT 'percentual',
    valor            DECIMAL(14,2) NOT NULL,
    minimo_compra    DECIMAL(14,2) NOT NULL DEFAULT 0,
    utilizacoes_max  INT NULL,
    utilizacoes      INT NOT NULL DEFAULT 0,
    inicio           DATETIME NULL,
    fim              DATETIME NULL,
    activo           TINYINT(1) NOT NULL DEFAULT 1,
    criado_em        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS newsletter (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(160) NOT NULL UNIQUE,
    origem     VARCHAR(40) NULL,
    criado_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contactos (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(120) NOT NULL,
    email      VARCHAR(160) NOT NULL,
    telefone   VARCHAR(30) NULL,
    assunto    VARCHAR(120) NOT NULL,
    mensagem   TEXT NOT NULL,
    lido       TINYINT(1) NOT NULL DEFAULT 0,
    criado_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_categorias (
    id    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome  VARCHAR(80) NOT NULL,
    slug  VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_posts (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id    BIGINT UNSIGNED NOT NULL,
    titulo          VARCHAR(200) NOT NULL,
    slug            VARCHAR(220) NOT NULL UNIQUE,
    resumo          VARCHAR(300) NULL,
    conteudo        MEDIUMTEXT NOT NULL,
    imagem          VARCHAR(255) NULL,
    publicado_em    DATETIME NULL,
    estado          ENUM('publicado','rascunho') NOT NULL DEFAULT 'rascunho',
    criado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_posts_categoria FOREIGN KEY (categoria_id) REFERENCES blog_categorias(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Clientes
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS clientes (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome                 VARCHAR(140) NOT NULL,
    email                VARCHAR(160) NOT NULL UNIQUE,
    telefone             VARCHAR(30) NULL,
    senha_hash           VARCHAR(255) NOT NULL,
    nif                  VARCHAR(20) NULL,                    -- para factura com NIF (AGT)
    pontos               INT NOT NULL DEFAULT 0,              -- programa de fidelidade
    aceita_marketing     TINYINT(1) NOT NULL DEFAULT 0,
    email_verificado_em  DATETIME NULL,
    token_recuperacao    VARCHAR(100) NULL,
    token_expira_em      DATETIME NULL,
    estado               ENUM('activo','bloqueado') NOT NULL DEFAULT 'activo',
    ultimo_login_em      DATETIME NULL,
    criado_em            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enderecos (
    id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id         BIGINT UNSIGNED NOT NULL,
    nome_destinatario  VARCHAR(140) NOT NULL,
    telefone           VARCHAR(30) NOT NULL,
    provincia          VARCHAR(60) NOT NULL,
    municipio          VARCHAR(80) NOT NULL,
    bairro             VARCHAR(120) NOT NULL,
    rua                VARCHAR(200) NULL,
    referencia         VARCHAR(255) NULL,                     -- "perto da escola X" — essencial em Angola
    principal          TINYINT(1) NOT NULL DEFAULT 0,
    criado_em          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_enderecos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS favoritos (
    cliente_id  BIGINT UNSIGNED NOT NULL,
    produto_id  BIGINT UNSIGNED NOT NULL,
    criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (cliente_id, produto_id),
    CONSTRAINT fk_favoritos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    CONSTRAINT fk_favoritos_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS avaliacoes (
    id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    produto_id         BIGINT UNSIGNED NOT NULL,
    cliente_id         BIGINT UNSIGNED NOT NULL,
    nota               TINYINT UNSIGNED NOT NULL,             -- 1 a 5
    titulo             VARCHAR(140) NULL,
    comentario         TEXT NULL,
    compra_verificada  TINYINT(1) NOT NULL DEFAULT 0,
    estado             ENUM('pendente','aprovada','rejeitada') NOT NULL DEFAULT 'pendente',
    criado_em          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_avaliacao (produto_id, cliente_id),
    CONSTRAINT fk_avaliacoes_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    CONSTRAINT fk_avaliacoes_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Carrinho (persistente — base para recuperação de carrinho abandonado)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS carrinhos (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token                   CHAR(40) NOT NULL UNIQUE,         -- cookie do visitante
    cliente_id              BIGINT UNSIGNED NULL,
    cupao_id                BIGINT UNSIGNED NULL,
    email_contacto          VARCHAR(160) NULL,
    recuperacao_enviada_em  DATETIME NULL,
    convertido              TINYINT(1) NOT NULL DEFAULT 0,
    criado_em               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_em          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_carrinhos_abandono (convertido, actualizado_em),
    CONSTRAINT fk_carrinhos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    CONSTRAINT fk_carrinhos_cupao FOREIGN KEY (cupao_id) REFERENCES cupoes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS carrinho_itens (
    carrinho_id  BIGINT UNSIGNED NOT NULL,
    produto_id   BIGINT UNSIGNED NOT NULL,
    quantidade   INT NOT NULL DEFAULT 1,
    adicionado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (carrinho_id, produto_id),
    CONSTRAINT fk_citens_carrinho FOREIGN KEY (carrinho_id) REFERENCES carrinhos(id) ON DELETE CASCADE,
    CONSTRAINT fk_citens_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Logística
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS zonas_entrega (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provincia   VARCHAR(60) NOT NULL,
    municipio   VARCHAR(80) NULL,                              -- NULL = toda a província
    preco       DECIMAL(14,2) NOT NULL,
    prazo_min   TINYINT UNSIGNED NOT NULL DEFAULT 1,            -- dias úteis
    prazo_max   TINYINT UNSIGNED NOT NULL DEFAULT 3,
    expressa    TINYINT(1) NOT NULL DEFAULT 0,                  -- entrega expressa (Luanda)
    transportadora VARCHAR(80) NULL,
    activo      TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Pedidos e pagamentos
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pedidos (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero            VARCHAR(20) NOT NULL UNIQUE,              -- DTK-2026-000123
    cliente_id        BIGINT UNSIGNED NOT NULL,
    cupao_id          BIGINT UNSIGNED NULL,
    -- cópia do endereço no momento da compra
    entrega_nome      VARCHAR(140) NOT NULL,
    entrega_telefone  VARCHAR(30) NOT NULL,
    entrega_provincia VARCHAR(60) NOT NULL,
    entrega_municipio VARCHAR(80) NOT NULL,
    entrega_bairro    VARCHAR(120) NOT NULL,
    entrega_rua       VARCHAR(200) NULL,
    entrega_referencia VARCHAR(255) NULL,
    metodo_entrega    ENUM('normal','expressa','levantamento') NOT NULL DEFAULT 'normal',
    subtotal          DECIMAL(14,2) NOT NULL,
    desconto          DECIMAL(14,2) NOT NULL DEFAULT 0,
    portes            DECIMAL(14,2) NOT NULL DEFAULT 0,
    iva               DECIMAL(14,2) NOT NULL DEFAULT 0,          -- IVA incluído (informativo)
    total             DECIMAL(14,2) NOT NULL,
    metodo_pagamento  ENUM('multicaixa_express','referencia_multicaixa','transferencia','afrimoney','unitel_money','cartao') NOT NULL,
    estado            ENUM('pendente','pago','em_preparacao','enviado','entregue','cancelado','reembolsado') NOT NULL DEFAULT 'pendente',
    codigo_rastreio   VARCHAR(60) NULL,
    nif_factura       VARCHAR(20) NULL,
    notas             VARCHAR(500) NULL,
    criado_em         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_em    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_pedidos_estado (estado, criado_em),
    CONSTRAINT fk_pedidos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    CONSTRAINT fk_pedidos_cupao FOREIGN KEY (cupao_id) REFERENCES cupoes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedido_itens (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id       BIGINT UNSIGNED NOT NULL,
    produto_id      BIGINT UNSIGNED NULL,
    nome            VARCHAR(200) NOT NULL,                     -- cópia no momento da compra
    sku             VARCHAR(60) NOT NULL,
    preco_unitario  DECIMAL(14,2) NOT NULL,
    quantidade      INT NOT NULL,
    total           DECIMAL(14,2) NOT NULL,
    CONSTRAINT fk_pitens_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    CONSTRAINT fk_pitens_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedido_historico (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id   BIGINT UNSIGNED NOT NULL,
    estado      VARCHAR(30) NOT NULL,
    nota        VARCHAR(255) NULL,                              -- visível ao cliente no rastreamento
    criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_historico_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pagamentos (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id         BIGINT UNSIGNED NOT NULL,
    metodo            VARCHAR(30) NOT NULL,
    entidade          VARCHAR(10) NULL,                         -- referência Multicaixa
    referencia        VARCHAR(40) NULL,
    telefone          VARCHAR(30) NULL,                         -- Multicaixa Express / carteiras móveis
    valor             DECIMAL(14,2) NOT NULL,
    estado            ENUM('pendente','confirmado','falhado','expirado') NOT NULL DEFAULT 'pendente',
    comprovativo      VARCHAR(255) NULL,                        -- transferência bancária
    resposta_gateway  TEXT NULL,
    pago_em           DATETIME NULL,
    expira_em         DATETIME NULL,
    criado_em         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pagamentos_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Administração
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(120) NOT NULL,
    email           VARCHAR(160) NOT NULL UNIQUE,
    senha_hash      VARCHAR(255) NOT NULL,
    papel           ENUM('super','gestor','stock','atendimento') NOT NULL DEFAULT 'gestor',
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_login_em DATETIME NULL,
    criado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracoes (
    chave  VARCHAR(80) PRIMARY KEY,
    valor  TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
