-- =====================================================================
-- 005_painel_admin.sql — Fase 4: painel administrativo
-- =====================================================================
SET NAMES utf8mb4;

-- Registo de auditoria: quem fez o quê, e quando
CREATE TABLE IF NOT EXISTS admin_registos (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id    BIGINT UNSIGNED NULL,
    accao       VARCHAR(60) NOT NULL,
    alvo        VARCHAR(60) NULL,
    alvo_id     VARCHAR(40) NULL,
    detalhe     VARCHAR(500) NULL,
    ip          VARCHAR(45) NULL,
    criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_registos_alvo (alvo, alvo_id),
    KEY idx_registos_data (criado_em),
    CONSTRAINT fk_registos_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE admins ADD COLUMN deve_mudar_senha TINYINT(1) NOT NULL DEFAULT 0 AFTER activo;
UPDATE admins SET deve_mudar_senha = 1 WHERE email = 'admin@diotekno.ao';

ALTER TABLE pedidos ADD KEY idx_pedidos_pago (pago_em);
ALTER TABLE produtos ADD KEY idx_produtos_stock (stock, stock_minimo);
