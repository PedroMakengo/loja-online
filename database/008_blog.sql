-- =====================================================================
-- 008_blog.sql — Fase 7: blog com guias de compra
-- =====================================================================
SET NAMES utf8mb4;

ALTER TABLE blog_categorias
    ADD COLUMN descricao VARCHAR(255) NULL AFTER slug,
    ADD COLUMN ordem SMALLINT NOT NULL DEFAULT 0 AFTER descricao;

ALTER TABLE blog_posts
    ADD COLUMN autor_id BIGINT UNSIGNED NULL AFTER categoria_id,
    ADD COLUMN produtos_ids VARCHAR(255) NULL AFTER imagem,        -- "Produtos neste artigo"
    ADD COLUMN destaque TINYINT(1) NOT NULL DEFAULT 0 AFTER estado,
    ADD COLUMN visualizacoes INT NOT NULL DEFAULT 0 AFTER destaque,
    ADD COLUMN meta_titulo VARCHAR(160) NULL AFTER visualizacoes,
    ADD COLUMN meta_descricao VARCHAR(255) NULL AFTER meta_titulo,
    ADD COLUMN actualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER criado_em,
    ADD KEY idx_posts_publicados (estado, publicado_em),
    ADD CONSTRAINT fk_posts_autor FOREIGN KEY (autor_id) REFERENCES admins(id) ON DELETE SET NULL;

UPDATE blog_categorias SET descricao = 'O que há de novo no mundo da tecnologia', ordem = 4 WHERE slug = 'tecnologia';
UPDATE blog_categorias SET descricao = 'Como escolher o equipamento certo para o seu orçamento', ordem = 1 WHERE slug = 'guias-de-compra';
UPDATE blog_categorias SET descricao = 'Tirar mais partido do que já tem', ordem = 2 WHERE slug = 'dicas';
UPDATE blog_categorias SET descricao = 'Lançamentos, chegadas à loja e novidades DIOTEKNO', ordem = 3 WHERE slug = 'noticias';
