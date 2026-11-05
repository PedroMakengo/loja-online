-- =====================================================================
-- 004_checkout_pagamentos.sql — Fase 3: checkout e pagamentos
-- =====================================================================
SET NAMES utf8mb4;

ALTER TABLE pedidos
    ADD COLUMN chave_idempotencia CHAR(32) NULL AFTER numero,
    ADD COLUMN prazo_entrega VARCHAR(80) NULL AFTER metodo_entrega,
    ADD COLUMN pago_em DATETIME NULL AFTER estado,
    ADD UNIQUE KEY uq_pedidos_idempotencia (chave_idempotencia);

ALTER TABLE pagamentos
    ADD COLUMN id_externo VARCHAR(100) NULL AFTER referencia,
    ADD COLUMN tentativas TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER telefone,
    ADD UNIQUE KEY uq_pagamentos_referencia (entidade, referencia),
    ADD KEY idx_pagamentos_expira (estado, expira_em),
    ADD KEY idx_pagamentos_externo (id_externo);

-- Registo bruto de notificações dos gateways (auditoria e depuração)
CREATE TABLE IF NOT EXISTS pagamento_notificacoes (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    metodo      VARCHAR(30) NULL,
    corpo       TEXT NOT NULL,
    assinatura_valida TINYINT(1) NOT NULL DEFAULT 0,
    resultado   VARCHAR(120) NULL,
    ip          VARCHAR(45) NULL,
    criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
