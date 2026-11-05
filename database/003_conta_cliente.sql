-- =====================================================================
-- 003_conta_cliente.sql — Fase 2: conta do cliente
--  * protecção contra ataques de força bruta no login
--  * "lembrar-me" com token seguro
--  * zonas de entrega actualizadas para as 21 províncias
-- =====================================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS tentativas_login (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip         VARCHAR(45) NOT NULL,
    email      VARCHAR(160) NULL,
    criado_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_tentativas (ip, criado_em),
    KEY idx_tentativas_email (email, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sessoes_lembradas (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id   BIGINT UNSIGNED NOT NULL,
    seletor      CHAR(24) NOT NULL UNIQUE,
    validador_hash CHAR(64) NOT NULL,
    expira_em    DATETIME NOT NULL,
    criado_em    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lembradas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Um carrinho activo por cliente
ALTER TABLE carrinhos ADD KEY idx_carrinhos_cliente (cliente_id, convertido);

-- Divisão político-administrativa com 21 províncias
UPDATE zonas_entrega SET provincia = 'Cuando'  WHERE provincia = 'Cuando Cubango';
INSERT INTO zonas_entrega (provincia, municipio, preco, prazo_min, prazo_max, expressa, transportadora)
SELECT 'Cubango', NULL, 14500, 3, 6, 0, 'Transportadora parceira' FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM zonas_entrega WHERE provincia = 'Cubango');
INSERT INTO zonas_entrega (provincia, municipio, preco, prazo_min, prazo_max, expressa, transportadora)
SELECT 'Moxico Leste', NULL, 14500, 3, 6, 0, 'Transportadora parceira' FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM zonas_entrega WHERE provincia = 'Moxico Leste');
