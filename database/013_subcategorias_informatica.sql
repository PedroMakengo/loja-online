-- =====================================================================
-- 013 — Subcategorias (estrutura ao estilo NCR) para os dados de demonstração:
--   Informática
--   ├─ Computadores Desktops  › StandAlone, All-In-One, Mini PC
--   ├─ Computadores Portáteis › Portáteis Casa e Escritório, Portáteis Gaming, Macbook
--   ├─ Impressão
--   └─ Redes e Internet
-- Conservadora: só mexe no que ainda está como o 002 deixou (categoria no topo,
-- nome original, produto ainda na categoria-mãe). O que o administrador já
-- tiver reorganizado no painel fica como está. Pode correr mais de uma vez.
-- =====================================================================

SET NAMES utf8mb4;

-- 1) Categoria principal
INSERT INTO categorias (nome, slug, descricao, ordem, destaque, activo)
SELECT 'Informática', 'informatica', 'Computadores, portáteis, impressão e redes para casa e escritório', 2, 1, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE slug = 'informatica');

SET @inf = (SELECT id FROM categorias WHERE slug = 'informatica');

-- 2) Categorias antigas passam a subcategorias (só se ainda estiverem no topo)
UPDATE categorias SET parent_id = @inf, ordem = 1, destaque = 0 WHERE slug = 'computadores' AND parent_id IS NULL;
UPDATE categorias SET parent_id = @inf, ordem = 2 WHERE slug = 'laptops'      AND parent_id IS NULL;
UPDATE categorias SET parent_id = @inf, ordem = 5 WHERE slug = 'impressoras'  AND parent_id IS NULL;
UPDATE categorias SET parent_id = @inf, ordem = 6 WHERE slug = 'redes'        AND parent_id IS NULL;

-- Nomes ao estilo da loja de referência (só os que ainda têm o nome original)
UPDATE categorias SET nome = 'Computadores Desktops'  WHERE slug = 'computadores' AND nome = 'Computadores';
UPDATE categorias SET nome = 'Computadores Portáteis' WHERE slug = 'laptops'      AND nome = 'Laptops';
UPDATE categorias SET nome = 'Impressão'              WHERE slug = 'impressoras'  AND nome = 'Impressoras';
UPDATE categorias SET nome = 'Redes e Internet'       WHERE slug = 'redes'        AND nome = 'Redes';

SET @desk = (SELECT id FROM categorias WHERE slug = 'computadores');
SET @port = (SELECT id FROM categorias WHERE slug = 'laptops');

-- 3) Sub-subcategorias (só se a mãe existir e o endereço estiver livre)
INSERT INTO categorias (parent_id, nome, slug, ordem, activo)
SELECT @desk, n.nome, n.slug, n.ordem, 1
  FROM (SELECT 'StandAlone' nome, 'standalone' slug, 1 ordem
        UNION ALL SELECT 'All-In-One', 'all-in-one', 2
        UNION ALL SELECT 'Mini PC', 'mini-pc', 3) n
 WHERE @desk IS NOT NULL AND NOT EXISTS (SELECT 1 FROM categorias c WHERE c.slug = n.slug);

INSERT INTO categorias (parent_id, nome, slug, ordem, activo)
SELECT @port, n.nome, n.slug, n.ordem, 1
  FROM (SELECT 'Portáteis Casa e Escritório' nome, 'portateis-casa-e-escritorio' slug, 1 ordem
        UNION ALL SELECT 'Portáteis Gaming', 'portateis-gaming', 2
        UNION ALL SELECT 'Macbook', 'macbook', 3) n
 WHERE @port IS NOT NULL AND NOT EXISTS (SELECT 1 FROM categorias c WHERE c.slug = n.slug);

-- 4) Produtos de demonstração para a subcategoria certa (só se ainda estiverem na mãe)
UPDATE produtos p JOIN categorias c ON c.slug = 'standalone'
   SET p.categoria_id = c.id WHERE p.slug = 'dell-optiplex-7020-tower-i7' AND p.categoria_id = @desk;
UPDATE produtos p JOIN categorias c ON c.slug = 'all-in-one'
   SET p.categoria_id = c.id WHERE p.slug IN ('hp-pavilion-all-in-one-24-core-i5', 'lenovo-ideacentre-aio-3-27') AND p.categoria_id = @desk;
UPDATE produtos p JOIN categorias c ON c.slug = 'macbook'
   SET p.categoria_id = c.id WHERE p.slug = 'macbook-air-13-m3-256gb' AND p.categoria_id = @port;
UPDATE produtos p JOIN categorias c ON c.slug = 'portateis-gaming'
   SET p.categoria_id = c.id WHERE p.slug = 'lenovo-loq-15-rtx-4050' AND p.categoria_id = @port;
UPDATE produtos p JOIN categorias c ON c.slug = 'portateis-casa-e-escritorio'
   SET p.categoria_id = c.id WHERE p.slug IN ('hp-250-g10-core-i5-8gb', 'dell-latitude-5450-core-ultra-5', 'lenovo-ideapad-slim-3-ryzen-7') AND p.categoria_id = @port;
