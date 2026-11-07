-- =====================================================================
-- 012 — Entrar / criar conta com o Google (OpenID Connect)
--  * google_id: identificador estável do Google ("sub"); liga a conta
--  * senha_definida: 0 = conta criada pelo Google, ainda sem senha própria
--    (pode definir uma em "Os meus dados" sem indicar a senha actual)
-- =====================================================================

ALTER TABLE clientes
    ADD COLUMN google_id      VARCHAR(64)  NULL AFTER senha_hash,
    ADD COLUMN avatar_url     VARCHAR(500) NULL AFTER google_id,
    ADD COLUMN senha_definida TINYINT(1)   NOT NULL DEFAULT 1 AFTER avatar_url,
    ADD UNIQUE KEY uq_clientes_google (google_id);
