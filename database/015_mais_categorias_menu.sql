-- =====================================================================
-- 015 — Mais categorias para o menu ficar completo, como na NCR:
--
--   Escritório e Papelaria › Papel e Cadernos, Escrita, Arquivo e Organização,
--                            Consumíveis de Impressão, Mobiliário de Escritório
--   Energia                › UPS, Estabilizadores, Geradores, Energia Solar,
--                            Power Banks (o power bank sai de Acessórios)
--   Informática            + Monitores, Armazenamento (Discos Externos, SSD, Pens USB), Software
--   Imagem e Som           + Soundbars, Projectores
--
-- Ordem na barra (como a NCR): Informática, Escritório e Papelaria,
-- Electrodomésticos, Smartphones e Tablets, Energia, Imagem e Som, Gaming.
--
-- O menu esconde categorias sem produtos, por isso cada subcategoria nova
-- recebe produtos de demonstração (sem fotografia: usam a imagem da
-- categoria até se carregar uma no painel). Só insere o que ainda não existe
-- (por slug/sku). Pode correr mais de uma vez.
-- =====================================================================

SET NAMES utf8mb4;

-- 1) Categorias principais novas
INSERT INTO categorias (nome, slug, descricao, ordem, destaque, activo)
SELECT n.nome, n.slug, n.descricao, n.ordem, 0, 1
  FROM (          SELECT 'Escritório e Papelaria' nome, 'escritorio-e-papelaria' slug, 'Papel, escrita, arquivo, consumíveis e mobiliário de escritório' descricao, 2 ordem
        UNION ALL SELECT 'Energia', 'energia', 'UPS, estabilizadores, geradores, energia solar e power banks', 5) n
 WHERE NOT EXISTS (SELECT 1 FROM categorias c WHERE c.slug = n.slug);

-- Ordem na barra
UPDATE categorias SET ordem = 1 WHERE slug = 'informatica'            AND parent_id IS NULL;
UPDATE categorias SET ordem = 2 WHERE slug = 'escritorio-e-papelaria' AND parent_id IS NULL;
UPDATE categorias SET ordem = 3 WHERE slug = 'electrodomesticos'      AND parent_id IS NULL;
UPDATE categorias SET ordem = 4 WHERE slug = 'smartphones-e-tablets'  AND parent_id IS NULL;
UPDATE categorias SET ordem = 5 WHERE slug = 'energia'                AND parent_id IS NULL;
UPDATE categorias SET ordem = 6 WHERE slug = 'imagem-e-som'           AND parent_id IS NULL;
UPDATE categorias SET ordem = 7 WHERE slug = 'gaming'                 AND parent_id IS NULL;

SET @inf = (SELECT id FROM categorias WHERE slug = 'informatica');
SET @esc = (SELECT id FROM categorias WHERE slug = 'escritorio-e-papelaria');
SET @ene = (SELECT id FROM categorias WHERE slug = 'energia');
SET @img = (SELECT id FROM categorias WHERE slug = 'imagem-e-som');

-- 2) Subcategorias
INSERT INTO categorias (parent_id, nome, slug, ordem, activo)
SELECT n.mae, n.nome, n.slug, n.ordem, 1
  FROM (          SELECT @esc mae, 'Papel e Cadernos' nome, 'papel-e-cadernos' slug, 1 ordem
        UNION ALL SELECT @esc, 'Escrita', 'escrita', 2
        UNION ALL SELECT @esc, 'Arquivo e Organização', 'arquivo-e-organizacao', 3
        UNION ALL SELECT @esc, 'Consumíveis de Impressão', 'consumiveis-de-impressao', 4
        UNION ALL SELECT @esc, 'Mobiliário de Escritório', 'mobiliario-de-escritorio', 5
        UNION ALL SELECT @ene, 'UPS', 'ups', 1
        UNION ALL SELECT @ene, 'Estabilizadores', 'estabilizadores', 2
        UNION ALL SELECT @ene, 'Geradores', 'geradores', 3
        UNION ALL SELECT @ene, 'Energia Solar', 'energia-solar', 4
        UNION ALL SELECT @ene, 'Power Banks', 'power-banks', 5
        UNION ALL SELECT @inf, 'Monitores', 'monitores', 4
        UNION ALL SELECT @inf, 'Armazenamento', 'armazenamento', 7
        UNION ALL SELECT @inf, 'Software', 'software', 8
        UNION ALL SELECT @img, 'Soundbars', 'soundbars', 3
        UNION ALL SELECT @img, 'Projectores', 'projectores', 4) n
 WHERE n.mae IS NOT NULL AND NOT EXISTS (SELECT 1 FROM categorias c WHERE c.slug = n.slug);

SET @arm = (SELECT id FROM categorias WHERE slug = 'armazenamento');
INSERT INTO categorias (parent_id, nome, slug, ordem, activo)
SELECT @arm, n.nome, n.slug, n.ordem, 1
  FROM (          SELECT 'Discos Externos' nome, 'discos-externos' slug, 1 ordem
        UNION ALL SELECT 'Discos SSD', 'discos-ssd', 2
        UNION ALL SELECT 'Pens USB e Cartões', 'pens-usb-e-cartoes', 3) n
 WHERE @arm IS NOT NULL AND NOT EXISTS (SELECT 1 FROM categorias c WHERE c.slug = n.slug);

-- O power bank passa para Energia › Power Banks (só se ainda estiver em Acessórios)
UPDATE produtos p
  JOIN categorias destino ON destino.slug = 'power-banks'
  JOIN categorias origem  ON origem.slug  = 'acessorios'
   SET p.categoria_id = destino.id
 WHERE p.slug = 'power-bank-xiaomi-20000mah-22-5w' AND p.categoria_id = origem.id;

-- 3) Marcas que faltam
INSERT INTO marcas (nome, slug, destaque)
SELECT n.nome, n.slug, 0
  FROM (          SELECT 'Navigator' nome, 'navigator' slug
        UNION ALL SELECT 'Oxford', 'oxford'
        UNION ALL SELECT 'BIC', 'bic'
        UNION ALL SELECT 'Leitz', 'leitz'
        UNION ALL SELECT 'APC', 'apc'
        UNION ALL SELECT 'Firman', 'firman'
        UNION ALL SELECT 'Jinko Solar', 'jinko-solar'
        UNION ALL SELECT 'Seagate', 'seagate'
        UNION ALL SELECT 'SanDisk', 'sandisk'
        UNION ALL SELECT 'Microsoft', 'microsoft') n
 WHERE NOT EXISTS (SELECT 1 FROM marcas m WHERE m.slug = n.slug);

-- 4) Produtos de demonstração (um ou mais por subcategoria nova)
CREATE TEMPORARY TABLE tmp_produtos_015 (
    cat VARCHAR(120), marca VARCHAR(120), nome VARCHAR(200), slug VARCHAR(220), sku VARCHAR(60),
    resumo VARCHAR(300), preco DECIMAL(14,2), anterior DECIMAL(14,2) NULL, stock INT, garantia SMALLINT
);
INSERT INTO tmp_produtos_015 VALUES
('papel-e-cadernos',         'navigator',  'Resma de Papel A4 Navigator 80g (500 folhas)', 'resma-papel-a4-navigator-80g',       'DTK-ESC-0001', 'Papel branco de alta qualidade para impressoras laser e jacto de tinta.', 6900, NULL, 200, 0),
('papel-e-cadernos',         'oxford',     'Caderno A4 Oxford Quadriculado 90 folhas',     'caderno-a4-oxford-quadriculado-90',  'DTK-ESC-0002', 'Capa rígida, papel Optik de 90 g e folhas destacáveis.', 4500, NULL, 120, 0),
('escrita',                  'bic',        'Canetas BIC Cristal Azul (caixa de 50)',       'canetas-bic-cristal-azul-50',        'DTK-ESC-0003', 'A caneta de sempre: escrita suave e tinta que dura.', 7900, 8900, 80, 0),
('arquivo-e-organizacao',    'leitz',      'Pasta de Arquivo Leitz A4 Lombada 80 mm',      'pasta-arquivo-leitz-a4-80mm',        'DTK-ESC-0004', 'Pasta de arquivo com mecanismo de alavanca e argola para tirar da prateleira.', 3900, NULL, 150, 0),
('consumiveis-de-impressao', 'hp',         'Tinteiro HP 305 Preto Original',               'tinteiro-hp-305-preto',              'DTK-ESC-0005', 'Tinteiro original para impressoras HP DeskJet e Envy da série 2700/6000.', 14900, NULL, 60, 6),
('consumiveis-de-impressao', 'hp',         'Toner HP 85A Preto Original (CE285A)',         'toner-hp-85a-preto',                 'DTK-ESC-0006', 'Cerca de 1600 páginas, para LaserJet Pro P1102 e M1132.', 49900, 54900, 30, 6),
('mobiliario-de-escritorio', NULL,         'Cadeira de Escritório Ergonómica em Malha',    'cadeira-escritorio-ergonomica-malha','DTK-ESC-0007', 'Apoio lombar ajustável, braços 2D e encosto em malha respirável.', 129900, 149900, 12, 12),
('ups',                      'apc',        'UPS APC Back-UPS 950VA',                       'ups-apc-back-ups-950va',             'DTK-ENE-0001', 'Protege o computador e o router nos cortes de energia, com 6 tomadas.', 159900, NULL, 15, 24),
('estabilizadores',          'apc',        'Estabilizador de Tensão APC Line-R 1200VA',    'estabilizador-apc-line-r-1200va',    'DTK-ENE-0002', 'Regulação automática de tensão para equipamentos sensíveis.', 69900, NULL, 20, 24),
('geradores',                'firman',     'Gerador a Gasolina Firman 3,5 kVA',            'gerador-gasolina-firman-3-5kva',     'DTK-ENE-0003', 'Arranque eléctrico, 12 horas de autonomia a 50% de carga.', 489900, 529900, 6, 12),
('energia-solar',            'jinko-solar','Painel Solar Jinko 450W Monocristalino',       'painel-solar-jinko-450w',            'DTK-ENE-0004', 'Painel de alta eficiência para sistemas solares residenciais.', 119900, NULL, 25, 120),
('monitores',                'dell',       'Monitor Dell 24" P2425H Full HD IPS',          'monitor-dell-24-p2425h',             'DTK-INF-0101', 'Ecrã IPS de 24", 100 Hz, ajuste em altura e USB-C.', 219900, 239900, 14, 36),
('discos-externos',          'seagate',    'Disco Externo Seagate Expansion 2TB USB 3.0',  'disco-externo-seagate-expansion-2tb','DTK-INF-0102', 'Cópias de segurança simples: ligar e usar, sem fonte de alimentação.', 89900, NULL, 25, 24),
('discos-ssd',               'samsung',    'SSD Samsung 990 EVO 1TB NVMe M.2',             'ssd-samsung-990-evo-1tb',            'DTK-INF-0103', 'Até 5000 MB/s de leitura para arrancar o sistema em segundos.', 119900, 134900, 18, 60),
('pens-usb-e-cartoes',       'sandisk',    'Pen USB SanDisk Ultra Flair 64GB USB 3.0',     'pen-sandisk-ultra-flair-64gb',       'DTK-INF-0104', 'Corpo metálico resistente e transferências até 150 MB/s.', 9900, NULL, 100, 12),
('software',                 'microsoft',  'Microsoft 365 Personal (1 ano)',               'microsoft-365-personal-1-ano',       'DTK-INF-0105', 'Word, Excel, PowerPoint, Outlook e 1 TB no OneDrive para uma pessoa.', 59900, NULL, 999, 0),
('soundbars',                'samsung',    'Soundbar Samsung HW-B550 2.1 410W',            'soundbar-samsung-hw-b550',           'DTK-IMG-0001', 'Subwoofer sem fios, Dolby Audio e ligação Bluetooth à TV.', 199900, 229900, 10, 24),
('projectores',              'epson',      'Projector Epson EB-FH06 Full HD 3500 lm',      'projector-epson-eb-fh06',            'DTK-IMG-0002', 'Imagem Full HD brilhante para salas de aula, escritório e cinema em casa.', 549900, NULL, 5, 24);

INSERT INTO produtos (categoria_id, marca_id, nome, slug, sku, resumo, descricao, preco, preco_anterior, stock, garantia_meses, estado)
SELECT c.id, m.id, t.nome, t.slug, t.sku, t.resumo,
       CONCAT('<p>', t.resumo, '</p><p>Produto novo e com garantia oficial em Angola. Entregamos em Luanda em 24 horas e em todas as províncias com transportadora parceira.</p>'),
       t.preco, t.anterior, t.stock, t.garantia, 'activo'
  FROM tmp_produtos_015 t
  JOIN categorias c ON c.slug = t.cat
  LEFT JOIN marcas m ON m.slug = t.marca
 WHERE NOT EXISTS (SELECT 1 FROM produtos p WHERE p.slug = t.slug OR p.sku = t.sku);

DROP TEMPORARY TABLE tmp_produtos_015;
