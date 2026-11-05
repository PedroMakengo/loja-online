-- =====================================================================
-- 006_avaliacoes_fidelidade.sql — Fase 5: avaliações, comparador e
-- programa de pontos
-- =====================================================================
SET NAMES utf8mb4;

-- As classificações de demonstração (002) não correspondiam a avaliações
-- reais. A partir daqui a média e o total são calculados só a partir de
-- avaliações aprovadas.
UPDATE produtos SET avaliacao_media = 0, total_avaliacoes = 0;

ALTER TABLE avaliacoes
    ADD COLUMN pedido_id BIGINT UNSIGNED NULL AFTER cliente_id,
    ADD COLUMN resposta_loja VARCHAR(600) NULL AFTER estado,
    ADD COLUMN respondido_em DATETIME NULL AFTER resposta_loja,
    ADD COLUMN moderado_por BIGINT UNSIGNED NULL AFTER respondido_em,
    ADD COLUMN moderado_em DATETIME NULL AFTER moderado_por,
    ADD KEY idx_avaliacoes_estado (estado, criado_em),
    ADD KEY idx_avaliacoes_produto (produto_id, estado),
    ADD CONSTRAINT fk_avaliacoes_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL;

-- Programa de pontos: cada linha é um movimento; clientes.pontos guarda o saldo
CREATE TABLE IF NOT EXISTS pontos_movimentos (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id  BIGINT UNSIGNED NOT NULL,
    pedido_id   BIGINT UNSIGNED NULL,
    tipo        ENUM('compra','avaliacao','resgate','estorno','anulacao','bonus','ajuste') NOT NULL,
    pontos      INT NOT NULL,                       -- positivo ganha, negativo gasta
    descricao   VARCHAR(200) NOT NULL,
    admin_id    BIGINT UNSIGNED NULL,
    criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pontos_cliente (cliente_id, criado_em),
    UNIQUE KEY uq_pontos_pedido_tipo (pedido_id, tipo),  -- impede ganhar ou gastar duas vezes pelo mesmo pedido
    CONSTRAINT fk_pontos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    CONSTRAINT fk_pontos_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE pedidos
    ADD COLUMN pontos_usados INT NOT NULL DEFAULT 0 AFTER desconto,
    ADD COLUMN desconto_pontos DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER pontos_usados,
    ADD COLUMN pontos_ganhos INT NOT NULL DEFAULT 0 AFTER desconto_pontos;
