-- =====================================================================
-- 011_utilizadores_demo.sql — Contas de teste para o deploy (Coolify)
--   Cliente:        kay.edson@gmail.com / 123456
--   Administrador:  admin@gmail.com     / 123456  (papel: super)
-- Senhas guardadas só como hash bcrypt (password_hash). Mudar em produção real.
-- =====================================================================

SET NAMES utf8mb4;

INSERT INTO admins (nome, email, senha_hash, papel, activo, deve_mudar_senha) VALUES
('Administrador', 'admin@gmail.com', '$2y$12$bAl4p3JT/V6/nhB6I3zlv.xyecjhZEyCZbbUc9GlpLBGoPLnAk37a', 'super', 1, 0)
ON DUPLICATE KEY UPDATE senha_hash = VALUES(senha_hash), papel = 'super', activo = 1, deve_mudar_senha = 0;

INSERT INTO clientes (nome, email, telefone, senha_hash, estado, email_verificado_em) VALUES
('Kay Edson', 'kay.edson@gmail.com', '+244 923 000 001', '$2y$12$ko/U4Y8.pH94h8gpTLb4RuSvGifIzhGk7L/l4NZE2nEy1MUXZqQeS', 'activo', NOW())
ON DUPLICATE KEY UPDATE senha_hash = VALUES(senha_hash), estado = 'activo';
