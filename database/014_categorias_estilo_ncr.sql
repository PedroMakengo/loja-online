-- =====================================================================
-- 014 — Menu de categorias ao estilo NCR: poucas categorias principais
-- na barra, cada uma com as suas subcategorias.
--
--   Informática            › Desktops, Portáteis, Periféricos, Impressão, Redes (013)
--   Smartphones e Tablets  › Smartphones (iPhone, Samsung Galaxy, Xiaomi, Huawei)
--                            Tablets (iPad, Samsung Galaxy Tab), Acessórios
--   Imagem e Som           › Televisores, Áudio (Auscultadores e Earbuds, Colunas)
--   Gaming                 › Consolas, Comandos e Acessórios, Monitores Gaming
--   Electrodomésticos      › Grandes Electrodomésticos, Climatização,
--                            Pequenos Electrodomésticos
--
-- Conservadora como a 013: só move categorias que ainda estão no topo e
-- produtos que ainda estão na categoria original. Pode correr mais de uma vez.
-- =====================================================================

SET NAMES utf8mb4;

-- 1) Novas categorias principais
INSERT INTO categorias (nome, slug, descricao, ordem, destaque, activo)
SELECT 'Smartphones e Tablets', 'smartphones-e-tablets', 'Smartphones, tablets e acessórios das principais marcas', 2, 0, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE slug = 'smartphones-e-tablets');

INSERT INTO categorias (nome, slug, descricao, ordem, destaque, activo)
SELECT 'Imagem e Som', 'imagem-e-som', 'Televisores, auscultadores e colunas', 3, 0, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE slug = 'imagem-e-som');

SET @inf  = (SELECT id FROM categorias WHERE slug = 'informatica');
SET @smt  = (SELECT id FROM categorias WHERE slug = 'smartphones-e-tablets');
SET @img  = (SELECT id FROM categorias WHERE slug = 'imagem-e-som');
SET @gam  = (SELECT id FROM categorias WHERE slug = 'gaming');
SET @ele  = (SELECT id FROM categorias WHERE slug = 'electrodomesticos');

-- Ordem na barra
UPDATE categorias SET ordem = 1 WHERE id = @inf;
UPDATE categorias SET ordem = 4 WHERE id = @gam AND parent_id IS NULL;
UPDATE categorias SET ordem = 5 WHERE id = @ele AND parent_id IS NULL;

-- 2) Categorias antigas do topo passam a subcategorias (o "destaque" fica: a
--    secção "Compre por categoria" da página inicial continua igual)
UPDATE categorias SET parent_id = @smt, ordem = 1 WHERE slug = 'smartphones' AND parent_id IS NULL AND @smt IS NOT NULL;
UPDATE categorias SET parent_id = @smt, ordem = 2 WHERE slug = 'tablets'     AND parent_id IS NULL AND @smt IS NOT NULL;
UPDATE categorias SET parent_id = @smt, ordem = 3 WHERE slug = 'acessorios'  AND parent_id IS NULL AND @smt IS NOT NULL;
UPDATE categorias SET parent_id = @img, ordem = 1 WHERE slug = 'tvs'         AND parent_id IS NULL AND @img IS NOT NULL;
UPDATE categorias SET parent_id = @img, ordem = 2 WHERE slug = 'audio'       AND parent_id IS NULL AND @img IS NOT NULL;
UPDATE categorias SET nome = 'Televisores' WHERE slug = 'tvs' AND nome = 'TVs';

SET @smp = (SELECT id FROM categorias WHERE slug = 'smartphones');
SET @tab = (SELECT id FROM categorias WHERE slug = 'tablets');
SET @aud = (SELECT id FROM categorias WHERE slug = 'audio');
SET @ace = (SELECT id FROM categorias WHERE slug = 'acessorios');

-- 3) Novas subcategorias (só se a mãe existir e o endereço estiver livre)
INSERT INTO categorias (parent_id, nome, slug, ordem, activo)
SELECT n.mae, n.nome, n.slug, n.ordem, 1
  FROM (          SELECT @inf mae, 'Periféricos' nome, 'perifericos' slug, 3 ordem
        UNION ALL SELECT @smp, 'iPhone', 'iphone', 1
        UNION ALL SELECT @smp, 'Samsung Galaxy', 'samsung-galaxy', 2
        UNION ALL SELECT @smp, 'Xiaomi', 'xiaomi', 3
        UNION ALL SELECT @smp, 'Huawei', 'huawei', 4
        UNION ALL SELECT @tab, 'iPad', 'ipad', 1
        UNION ALL SELECT @tab, 'Samsung Galaxy Tab', 'samsung-galaxy-tab', 2
        UNION ALL SELECT @aud, 'Auscultadores e Earbuds', 'auscultadores', 1
        UNION ALL SELECT @aud, 'Colunas', 'colunas', 2
        UNION ALL SELECT @gam, 'Consolas', 'consolas', 1
        UNION ALL SELECT @gam, 'Comandos e Acessórios', 'comandos-e-acessorios-gaming', 2
        UNION ALL SELECT @gam, 'Monitores Gaming', 'monitores-gaming', 3
        UNION ALL SELECT @ele, 'Grandes Electrodomésticos', 'grandes-electrodomesticos', 1
        UNION ALL SELECT @ele, 'Climatização', 'climatizacao', 2
        UNION ALL SELECT @ele, 'Pequenos Electrodomésticos', 'pequenos-electrodomesticos', 3) n
 WHERE n.mae IS NOT NULL AND NOT EXISTS (SELECT 1 FROM categorias c WHERE c.slug = n.slug);

-- 4) Produtos de demonstração para a subcategoria certa (só se ainda estiverem na original)
UPDATE produtos p JOIN categorias c ON c.slug = 'iphone'
   SET p.categoria_id = c.id WHERE p.slug IN ('iphone-16-pro-256gb', 'iphone-16-128gb', 'iphone-15-128gb') AND p.categoria_id = @smp;
UPDATE produtos p JOIN categorias c ON c.slug = 'samsung-galaxy'
   SET p.categoria_id = c.id WHERE p.slug IN ('samsung-galaxy-s25-ultra-256gb', 'samsung-galaxy-a56-5g-128gb', 'samsung-galaxy-a16-128gb') AND p.categoria_id = @smp;
UPDATE produtos p JOIN categorias c ON c.slug = 'xiaomi'
   SET p.categoria_id = c.id WHERE p.slug = 'xiaomi-redmi-note-14-pro-256gb' AND p.categoria_id = @smp;
UPDATE produtos p JOIN categorias c ON c.slug = 'huawei'
   SET p.categoria_id = c.id WHERE p.slug = 'huawei-nova-13-256gb' AND p.categoria_id = @smp;

UPDATE produtos p JOIN categorias c ON c.slug = 'ipad'
   SET p.categoria_id = c.id WHERE p.slug = 'ipad-10-a-geracao-64gb-wi-fi' AND p.categoria_id = @tab;
UPDATE produtos p JOIN categorias c ON c.slug = 'samsung-galaxy-tab'
   SET p.categoria_id = c.id WHERE p.slug IN ('samsung-galaxy-tab-s10-fe', 'samsung-galaxy-tab-a9-64gb') AND p.categoria_id = @tab;

UPDATE produtos p JOIN categorias c ON c.slug = 'auscultadores'
   SET p.categoria_id = c.id WHERE p.slug IN ('sony-wh-1000xm5', 'airpods-pro-2-usb-c') AND p.categoria_id = @aud;
UPDATE produtos p JOIN categorias c ON c.slug = 'colunas'
   SET p.categoria_id = c.id WHERE p.slug IN ('jbl-partybox-110', 'jbl-flip-6') AND p.categoria_id = @aud;

UPDATE produtos p JOIN categorias c ON c.slug = 'consolas'
   SET p.categoria_id = c.id WHERE p.slug = 'playstation-5-slim-1tb' AND p.categoria_id = @gam;
UPDATE produtos p JOIN categorias c ON c.slug = 'comandos-e-acessorios-gaming'
   SET p.categoria_id = c.id WHERE p.slug = 'comando-dualsense-ps5' AND p.categoria_id = @gam;
UPDATE produtos p JOIN categorias c ON c.slug = 'monitores-gaming'
   SET p.categoria_id = c.id WHERE p.slug = 'monitor-samsung-odyssey-g5-27-165hz' AND p.categoria_id = @gam;

UPDATE produtos p JOIN categorias c ON c.slug = 'grandes-electrodomesticos'
   SET p.categoria_id = c.id WHERE p.slug IN ('frigorifico-lg-combinado-384l-inverter', 'maquina-de-lavar-lg-9kg-ai-dd') AND p.categoria_id = @ele;
UPDATE produtos p JOIN categorias c ON c.slug = 'climatizacao'
   SET p.categoria_id = c.id WHERE p.slug = 'ar-condicionado-samsung-windfree-12000-btu' AND p.categoria_id = @ele;
UPDATE produtos p JOIN categorias c ON c.slug = 'pequenos-electrodomesticos'
   SET p.categoria_id = c.id WHERE p.slug = 'micro-ondas-samsung-23l-grill' AND p.categoria_id = @ele;

-- O rato vai para Informática › Periféricos (power bank e carregador ficam em Acessórios)
UPDATE produtos p JOIN categorias c ON c.slug = 'perifericos'
   SET p.categoria_id = c.id WHERE p.slug = 'rato-dell-sem-fios-ms5120w' AND p.categoria_id = @ace;
