-- =====================================================================
-- 007_marketing.sql — Fase 6: carrinho abandonado e newsletter
-- =====================================================================
SET NAMES utf8mb4;

ALTER TABLE carrinhos
    ADD COLUMN lembretes_enviados TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER recuperacao_enviada_em,
    ADD COLUMN recuperacao_token CHAR(32) NULL AFTER lembretes_enviados,
    ADD COLUMN recuperado_em DATETIME NULL AFTER recuperacao_token,
    ADD COLUMN cupao_recuperacao_id BIGINT UNSIGNED NULL AFTER recuperado_em,
    ADD UNIQUE KEY uq_carrinhos_recuperacao (recuperacao_token);

ALTER TABLE clientes ADD COLUMN recebe_lembretes TINYINT(1) NOT NULL DEFAULT 1 AFTER aceita_marketing;

ALTER TABLE newsletter
    ADD COLUMN estado ENUM('activo','cancelado') NOT NULL DEFAULT 'activo' AFTER origem,
    ADD COLUMN cancelado_em DATETIME NULL AFTER estado;

-- Pedido criado a partir de um carrinho recuperado (para medir resultados)
ALTER TABLE pedidos ADD COLUMN carrinho_recuperado TINYINT(1) NOT NULL DEFAULT 0 AFTER chave_idempotencia;

CREATE TABLE IF NOT EXISTS newsletter_campanhas (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assunto       VARCHAR(150) NOT NULL,
    pre_cabecalho VARCHAR(150) NULL,              -- texto de pré-visualização na caixa de entrada
    titulo        VARCHAR(150) NOT NULL,
    texto         TEXT NULL,
    fonte         ENUM('promocoes','novidades','mais_vendidos','escolhidos','nenhum') NOT NULL DEFAULT 'promocoes',
    produtos_ids  VARCHAR(255) NULL,              -- para 'escolhidos' (ids separados por vírgula)
    botao_texto   VARCHAR(60) NULL,
    botao_link    VARCHAR(255) NULL,
    estado        ENUM('rascunho','a_enviar','enviada') NOT NULL DEFAULT 'rascunho',
    total         INT NOT NULL DEFAULT 0,
    enviados      INT NOT NULL DEFAULT 0,
    falhados      INT NOT NULL DEFAULT 0,
    criado_por    BIGINT UNSIGNED NULL,
    criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    enviada_em    DATETIME NULL,
    CONSTRAINT fk_campanhas_admin FOREIGN KEY (criado_por) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fila de envio: cada email é uma linha; o cron envia em lotes
CREATE TABLE IF NOT EXISTS newsletter_envios (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campanha_id  BIGINT UNSIGNED NOT NULL,
    email        VARCHAR(160) NOT NULL,
    estado       ENUM('pendente','enviado','falhado') NOT NULL DEFAULT 'pendente',
    enviado_em   DATETIME NULL,
    UNIQUE KEY uq_envio (campanha_id, email),
    KEY idx_envios_fila (estado, id),
    KEY idx_envios_hora (enviado_em),
    CONSTRAINT fk_envios_campanha FOREIGN KEY (campanha_id) REFERENCES newsletter_campanhas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
