-- =====================================================================
-- 010_imagens_produtos.sql — Fotografias oficiais dos produtos
-- Os ficheiros estão em assets/img/produtos/uploads/ (enviar com o site).
-- =====================================================================
SET NAMES utf8mb4;

-- Samsung Galaxy S25 Ultra 256GB
DELETE FROM produto_imagens WHERE produto_id = 1;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (1, 'uploads/samsung-galaxy-s25-ultra-256gb-1.webp', 'Samsung Galaxy S25 Ultra 256GB', 0), (1, 'uploads/samsung-galaxy-s25-ultra-256gb-2.webp', 'Samsung Galaxy S25 Ultra 256GB', 1), (1, 'uploads/samsung-galaxy-s25-ultra-256gb-3.webp', 'Samsung Galaxy S25 Ultra 256GB', 2), (1, 'uploads/samsung-galaxy-s25-ultra-256gb-4.webp', 'Samsung Galaxy S25 Ultra 256GB', 3);
UPDATE produtos SET imagem = 'uploads/samsung-galaxy-s25-ultra-256gb-1.webp' WHERE id = 1;

-- Samsung Galaxy A56 5G 128GB
DELETE FROM produto_imagens WHERE produto_id = 2;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (2, 'uploads/samsung-galaxy-a56-5g-128gb-1.webp', 'Samsung Galaxy A56 5G 128GB', 0), (2, 'uploads/samsung-galaxy-a56-5g-128gb-2.webp', 'Samsung Galaxy A56 5G 128GB', 1), (2, 'uploads/samsung-galaxy-a56-5g-128gb-3.webp', 'Samsung Galaxy A56 5G 128GB', 2), (2, 'uploads/samsung-galaxy-a56-5g-128gb-4.webp', 'Samsung Galaxy A56 5G 128GB', 3);
UPDATE produtos SET imagem = 'uploads/samsung-galaxy-a56-5g-128gb-1.webp' WHERE id = 2;

-- Samsung Galaxy A16 128GB
DELETE FROM produto_imagens WHERE produto_id = 3;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (3, 'uploads/samsung-galaxy-a16-128gb-1.webp', 'Samsung Galaxy A16 128GB', 0), (3, 'uploads/samsung-galaxy-a16-128gb-2.webp', 'Samsung Galaxy A16 128GB', 1), (3, 'uploads/samsung-galaxy-a16-128gb-3.webp', 'Samsung Galaxy A16 128GB', 2), (3, 'uploads/samsung-galaxy-a16-128gb-4.webp', 'Samsung Galaxy A16 128GB', 3);
UPDATE produtos SET imagem = 'uploads/samsung-galaxy-a16-128gb-1.webp' WHERE id = 3;

-- iPhone 16 Pro 256GB
DELETE FROM produto_imagens WHERE produto_id = 4;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (4, 'uploads/iphone-16-pro-256gb-1.webp', 'iPhone 16 Pro 256GB', 0), (4, 'uploads/iphone-16-pro-256gb-2.webp', 'iPhone 16 Pro 256GB', 1);
UPDATE produtos SET imagem = 'uploads/iphone-16-pro-256gb-1.webp' WHERE id = 4;

-- iPhone 16 128GB
DELETE FROM produto_imagens WHERE produto_id = 5;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (5, 'uploads/iphone-16-128gb-1.webp', 'iPhone 16 128GB', 0), (5, 'uploads/iphone-16-128gb-2.webp', 'iPhone 16 128GB', 1);
UPDATE produtos SET imagem = 'uploads/iphone-16-128gb-1.webp' WHERE id = 5;

-- iPhone 15 128GB
DELETE FROM produto_imagens WHERE produto_id = 6;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (6, 'uploads/iphone-15-128gb-1.webp', 'iPhone 15 128GB', 0), (6, 'uploads/iphone-15-128gb-2.webp', 'iPhone 15 128GB', 1);
UPDATE produtos SET imagem = 'uploads/iphone-15-128gb-1.webp' WHERE id = 6;

-- Xiaomi Redmi Note 14 Pro 256GB
DELETE FROM produto_imagens WHERE produto_id = 7;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (7, 'uploads/xiaomi-redmi-note-14-pro-256gb-1.webp', 'Xiaomi Redmi Note 14 Pro 256GB', 0);
UPDATE produtos SET imagem = 'uploads/xiaomi-redmi-note-14-pro-256gb-1.webp' WHERE id = 7;

-- Huawei nova 13 256GB
DELETE FROM produto_imagens WHERE produto_id = 8;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (8, 'uploads/huawei-nova-13-256gb-1.webp', 'Huawei nova 13 256GB', 0);
UPDATE produtos SET imagem = 'uploads/huawei-nova-13-256gb-1.webp' WHERE id = 8;

-- HP Pavilion All-in-One 24" Core i5
DELETE FROM produto_imagens WHERE produto_id = 9;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (9, 'uploads/hp-pavilion-all-in-one-24-core-i5-1.webp', 'HP Pavilion All-in-One 24\" Core i5', 0);
UPDATE produtos SET imagem = 'uploads/hp-pavilion-all-in-one-24-core-i5-1.webp' WHERE id = 9;

-- Dell OptiPlex 7020 Tower i7
DELETE FROM produto_imagens WHERE produto_id = 10;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (10, 'uploads/dell-optiplex-7020-tower-i7-1.webp', 'Dell OptiPlex 7020 Tower i7', 0), (10, 'uploads/dell-optiplex-7020-tower-i7-2.webp', 'Dell OptiPlex 7020 Tower i7', 1), (10, 'uploads/dell-optiplex-7020-tower-i7-3.webp', 'Dell OptiPlex 7020 Tower i7', 2), (10, 'uploads/dell-optiplex-7020-tower-i7-4.webp', 'Dell OptiPlex 7020 Tower i7', 3);
UPDATE produtos SET imagem = 'uploads/dell-optiplex-7020-tower-i7-1.webp' WHERE id = 10;

-- Lenovo IdeaCentre AIO 3 27"
DELETE FROM produto_imagens WHERE produto_id = 11;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (11, 'uploads/lenovo-ideacentre-aio-3-27-1.webp', 'Lenovo IdeaCentre AIO 3 27\"', 0), (11, 'uploads/lenovo-ideacentre-aio-3-27-2.webp', 'Lenovo IdeaCentre AIO 3 27\"', 1), (11, 'uploads/lenovo-ideacentre-aio-3-27-3.webp', 'Lenovo IdeaCentre AIO 3 27\"', 2), (11, 'uploads/lenovo-ideacentre-aio-3-27-4.webp', 'Lenovo IdeaCentre AIO 3 27\"', 3);
UPDATE produtos SET imagem = 'uploads/lenovo-ideacentre-aio-3-27-1.webp' WHERE id = 11;

-- MacBook Air 13" M3 256GB
DELETE FROM produto_imagens WHERE produto_id = 12;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (12, 'uploads/macbook-air-13-m3-256gb-1.webp', 'MacBook Air 13\" M3 256GB', 0), (12, 'uploads/macbook-air-13-m3-256gb-2.webp', 'MacBook Air 13\" M3 256GB', 1);
UPDATE produtos SET imagem = 'uploads/macbook-air-13-m3-256gb-1.webp' WHERE id = 12;

-- HP 250 G10 Core i5 8GB
DELETE FROM produto_imagens WHERE produto_id = 13;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (13, 'uploads/hp-250-g10-core-i5-8gb-1.webp', 'HP 250 G10 Core i5 8GB', 0), (13, 'uploads/hp-250-g10-core-i5-8gb-2.webp', 'HP 250 G10 Core i5 8GB', 1), (13, 'uploads/hp-250-g10-core-i5-8gb-3.webp', 'HP 250 G10 Core i5 8GB', 2), (13, 'uploads/hp-250-g10-core-i5-8gb-4.webp', 'HP 250 G10 Core i5 8GB', 3);
UPDATE produtos SET imagem = 'uploads/hp-250-g10-core-i5-8gb-1.webp' WHERE id = 13;

-- Dell Latitude 5450 Core Ultra 5
DELETE FROM produto_imagens WHERE produto_id = 14;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (14, 'uploads/dell-latitude-5450-core-ultra-5-1.webp', 'Dell Latitude 5450 Core Ultra 5', 0), (14, 'uploads/dell-latitude-5450-core-ultra-5-2.webp', 'Dell Latitude 5450 Core Ultra 5', 1), (14, 'uploads/dell-latitude-5450-core-ultra-5-3.webp', 'Dell Latitude 5450 Core Ultra 5', 2), (14, 'uploads/dell-latitude-5450-core-ultra-5-4.webp', 'Dell Latitude 5450 Core Ultra 5', 3);
UPDATE produtos SET imagem = 'uploads/dell-latitude-5450-core-ultra-5-1.webp' WHERE id = 14;

-- Lenovo IdeaPad Slim 3 Ryzen 7
DELETE FROM produto_imagens WHERE produto_id = 15;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (15, 'uploads/lenovo-ideapad-slim-3-ryzen-7-1.webp', 'Lenovo IdeaPad Slim 3 Ryzen 7', 0), (15, 'uploads/lenovo-ideapad-slim-3-ryzen-7-2.webp', 'Lenovo IdeaPad Slim 3 Ryzen 7', 1), (15, 'uploads/lenovo-ideapad-slim-3-ryzen-7-3.webp', 'Lenovo IdeaPad Slim 3 Ryzen 7', 2), (15, 'uploads/lenovo-ideapad-slim-3-ryzen-7-4.webp', 'Lenovo IdeaPad Slim 3 Ryzen 7', 3);
UPDATE produtos SET imagem = 'uploads/lenovo-ideapad-slim-3-ryzen-7-1.webp' WHERE id = 15;

-- Lenovo LOQ 15 RTX 4050
DELETE FROM produto_imagens WHERE produto_id = 16;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (16, 'uploads/lenovo-loq-15-rtx-4050-1.webp', 'Lenovo LOQ 15 RTX 4050', 0), (16, 'uploads/lenovo-loq-15-rtx-4050-2.webp', 'Lenovo LOQ 15 RTX 4050', 1), (16, 'uploads/lenovo-loq-15-rtx-4050-3.webp', 'Lenovo LOQ 15 RTX 4050', 2), (16, 'uploads/lenovo-loq-15-rtx-4050-4.webp', 'Lenovo LOQ 15 RTX 4050', 3);
UPDATE produtos SET imagem = 'uploads/lenovo-loq-15-rtx-4050-1.webp' WHERE id = 16;

-- iPad 10.ª geração 64GB Wi-Fi
DELETE FROM produto_imagens WHERE produto_id = 17;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (17, 'uploads/ipad-10-a-geracao-64gb-wi-fi-1.webp', 'iPad 10.ª geração 64GB Wi-Fi', 0), (17, 'uploads/ipad-10-a-geracao-64gb-wi-fi-2.webp', 'iPad 10.ª geração 64GB Wi-Fi', 1);
UPDATE produtos SET imagem = 'uploads/ipad-10-a-geracao-64gb-wi-fi-1.webp' WHERE id = 17;

-- Samsung Galaxy Tab S10 FE
DELETE FROM produto_imagens WHERE produto_id = 18;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (18, 'uploads/samsung-galaxy-tab-s10-fe-1.webp', 'Samsung Galaxy Tab S10 FE', 0), (18, 'uploads/samsung-galaxy-tab-s10-fe-2.webp', 'Samsung Galaxy Tab S10 FE', 1), (18, 'uploads/samsung-galaxy-tab-s10-fe-3.webp', 'Samsung Galaxy Tab S10 FE', 2), (18, 'uploads/samsung-galaxy-tab-s10-fe-4.webp', 'Samsung Galaxy Tab S10 FE', 3);
UPDATE produtos SET imagem = 'uploads/samsung-galaxy-tab-s10-fe-1.webp' WHERE id = 18;

-- Samsung Galaxy Tab A9+ 64GB
DELETE FROM produto_imagens WHERE produto_id = 19;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (19, 'uploads/samsung-galaxy-tab-a9-64gb-1.webp', 'Samsung Galaxy Tab A9+ 64GB', 0), (19, 'uploads/samsung-galaxy-tab-a9-64gb-2.webp', 'Samsung Galaxy Tab A9+ 64GB', 1), (19, 'uploads/samsung-galaxy-tab-a9-64gb-3.webp', 'Samsung Galaxy Tab A9+ 64GB', 2);
UPDATE produtos SET imagem = 'uploads/samsung-galaxy-tab-a9-64gb-1.webp' WHERE id = 19;

-- Samsung 55" Crystal UHD 4K DU7100
DELETE FROM produto_imagens WHERE produto_id = 20;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (20, 'uploads/samsung-55-crystal-uhd-4k-du7100-1.webp', 'Samsung 55\" Crystal UHD 4K DU7100', 0), (20, 'uploads/samsung-55-crystal-uhd-4k-du7100-2.webp', 'Samsung 55\" Crystal UHD 4K DU7100', 1), (20, 'uploads/samsung-55-crystal-uhd-4k-du7100-3.webp', 'Samsung 55\" Crystal UHD 4K DU7100', 2), (20, 'uploads/samsung-55-crystal-uhd-4k-du7100-4.webp', 'Samsung 55\" Crystal UHD 4K DU7100', 3);
UPDATE produtos SET imagem = 'uploads/samsung-55-crystal-uhd-4k-du7100-1.webp' WHERE id = 20;

-- LG 65" OLED evo C4
DELETE FROM produto_imagens WHERE produto_id = 21;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (21, 'uploads/lg-65-oled-evo-c4-1.webp', 'LG 65\" OLED evo C4', 0);
UPDATE produtos SET imagem = 'uploads/lg-65-oled-evo-c4-1.webp' WHERE id = 21;

-- Hisense 43" Full HD A4
DELETE FROM produto_imagens WHERE produto_id = 22;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (22, 'uploads/hisense-43-full-hd-a4-1.webp', 'Hisense 43\" Full HD A4', 0), (22, 'uploads/hisense-43-full-hd-a4-2.webp', 'Hisense 43\" Full HD A4', 1), (22, 'uploads/hisense-43-full-hd-a4-3.webp', 'Hisense 43\" Full HD A4', 2), (22, 'uploads/hisense-43-full-hd-a4-4.webp', 'Hisense 43\" Full HD A4', 3);
UPDATE produtos SET imagem = 'uploads/hisense-43-full-hd-a4-1.webp' WHERE id = 22;

-- Sony Bravia 75" 4K X85L
DELETE FROM produto_imagens WHERE produto_id = 23;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (23, 'uploads/sony-bravia-75-4k-x85l-1.webp', 'Sony Bravia 75\" 4K X85L', 0);
UPDATE produtos SET imagem = 'uploads/sony-bravia-75-4k-x85l-1.webp' WHERE id = 23;

-- Sony WH-1000XM5
DELETE FROM produto_imagens WHERE produto_id = 24;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (24, 'uploads/sony-wh-1000xm5-1.webp', 'Sony WH-1000XM5', 0), (24, 'uploads/sony-wh-1000xm5-2.webp', 'Sony WH-1000XM5', 1), (24, 'uploads/sony-wh-1000xm5-3.webp', 'Sony WH-1000XM5', 2), (24, 'uploads/sony-wh-1000xm5-4.webp', 'Sony WH-1000XM5', 3);
UPDATE produtos SET imagem = 'uploads/sony-wh-1000xm5-1.webp' WHERE id = 24;

-- AirPods Pro 2 USB-C
DELETE FROM produto_imagens WHERE produto_id = 25;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (25, 'uploads/airpods-pro-2-usb-c-1.webp', 'AirPods Pro 2 USB-C', 0), (25, 'uploads/airpods-pro-2-usb-c-2.webp', 'AirPods Pro 2 USB-C', 1);
UPDATE produtos SET imagem = 'uploads/airpods-pro-2-usb-c-1.webp' WHERE id = 25;

-- JBL PartyBox 110
DELETE FROM produto_imagens WHERE produto_id = 26;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (26, 'uploads/jbl-partybox-110-1.webp', 'JBL PartyBox 110', 0), (26, 'uploads/jbl-partybox-110-2.webp', 'JBL PartyBox 110', 1), (26, 'uploads/jbl-partybox-110-3.webp', 'JBL PartyBox 110', 2), (26, 'uploads/jbl-partybox-110-4.webp', 'JBL PartyBox 110', 3);
UPDATE produtos SET imagem = 'uploads/jbl-partybox-110-1.webp' WHERE id = 26;

-- JBL Flip 6
DELETE FROM produto_imagens WHERE produto_id = 27;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (27, 'uploads/jbl-flip-6-1.webp', 'JBL Flip 6', 0), (27, 'uploads/jbl-flip-6-2.webp', 'JBL Flip 6', 1), (27, 'uploads/jbl-flip-6-3.webp', 'JBL Flip 6', 2), (27, 'uploads/jbl-flip-6-4.webp', 'JBL Flip 6', 3);
UPDATE produtos SET imagem = 'uploads/jbl-flip-6-1.webp' WHERE id = 27;

-- PlayStation 5 Slim 1TB
DELETE FROM produto_imagens WHERE produto_id = 28;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (28, 'uploads/playstation-5-slim-1tb-1.webp', 'PlayStation 5 Slim 1TB', 0), (28, 'uploads/playstation-5-slim-1tb-2.webp', 'PlayStation 5 Slim 1TB', 1);
UPDATE produtos SET imagem = 'uploads/playstation-5-slim-1tb-1.webp' WHERE id = 28;

-- Comando DualSense PS5
DELETE FROM produto_imagens WHERE produto_id = 29;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (29, 'uploads/comando-dualsense-ps5-1.webp', 'Comando DualSense PS5', 0);
UPDATE produtos SET imagem = 'uploads/comando-dualsense-ps5-1.webp' WHERE id = 29;

-- Monitor Samsung Odyssey G5 27" 165Hz
DELETE FROM produto_imagens WHERE produto_id = 30;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (30, 'uploads/monitor-samsung-odyssey-g5-27-165hz-1.webp', 'Monitor Samsung Odyssey G5 27\" 165Hz', 0), (30, 'uploads/monitor-samsung-odyssey-g5-27-165hz-2.webp', 'Monitor Samsung Odyssey G5 27\" 165Hz', 1), (30, 'uploads/monitor-samsung-odyssey-g5-27-165hz-3.webp', 'Monitor Samsung Odyssey G5 27\" 165Hz', 2), (30, 'uploads/monitor-samsung-odyssey-g5-27-165hz-4.webp', 'Monitor Samsung Odyssey G5 27\" 165Hz', 3);
UPDATE produtos SET imagem = 'uploads/monitor-samsung-odyssey-g5-27-165hz-1.webp' WHERE id = 30;

-- Frigorífico LG Combinado 384L Inverter
DELETE FROM produto_imagens WHERE produto_id = 31;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (31, 'uploads/frigorifico-lg-combinado-384l-inverter-1.webp', 'Frigorífico LG Combinado 384L Inverter', 0), (31, 'uploads/frigorifico-lg-combinado-384l-inverter-2.webp', 'Frigorífico LG Combinado 384L Inverter', 1), (31, 'uploads/frigorifico-lg-combinado-384l-inverter-3.webp', 'Frigorífico LG Combinado 384L Inverter', 2), (31, 'uploads/frigorifico-lg-combinado-384l-inverter-4.webp', 'Frigorífico LG Combinado 384L Inverter', 3);
UPDATE produtos SET imagem = 'uploads/frigorifico-lg-combinado-384l-inverter-1.webp' WHERE id = 31;

-- Ar Condicionado Samsung WindFree 12000 BTU
DELETE FROM produto_imagens WHERE produto_id = 32;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (32, 'uploads/ar-condicionado-samsung-windfree-12000-btu-1.webp', 'Ar Condicionado Samsung WindFree 12000 BTU', 0), (32, 'uploads/ar-condicionado-samsung-windfree-12000-btu-2.webp', 'Ar Condicionado Samsung WindFree 12000 BTU', 1), (32, 'uploads/ar-condicionado-samsung-windfree-12000-btu-3.webp', 'Ar Condicionado Samsung WindFree 12000 BTU', 2), (32, 'uploads/ar-condicionado-samsung-windfree-12000-btu-4.webp', 'Ar Condicionado Samsung WindFree 12000 BTU', 3);
UPDATE produtos SET imagem = 'uploads/ar-condicionado-samsung-windfree-12000-btu-1.webp' WHERE id = 32;

-- Micro-ondas Samsung 23L Grill
DELETE FROM produto_imagens WHERE produto_id = 33;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (33, 'uploads/micro-ondas-samsung-23l-grill-1.webp', 'Micro-ondas Samsung 23L Grill', 0), (33, 'uploads/micro-ondas-samsung-23l-grill-2.webp', 'Micro-ondas Samsung 23L Grill', 1), (33, 'uploads/micro-ondas-samsung-23l-grill-3.webp', 'Micro-ondas Samsung 23L Grill', 2), (33, 'uploads/micro-ondas-samsung-23l-grill-4.webp', 'Micro-ondas Samsung 23L Grill', 3);
UPDATE produtos SET imagem = 'uploads/micro-ondas-samsung-23l-grill-1.webp' WHERE id = 33;

-- Máquina de Lavar LG 9kg AI DD
DELETE FROM produto_imagens WHERE produto_id = 34;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (34, 'uploads/maquina-de-lavar-lg-9kg-ai-dd-1.webp', 'Máquina de Lavar LG 9kg AI DD', 0), (34, 'uploads/maquina-de-lavar-lg-9kg-ai-dd-2.webp', 'Máquina de Lavar LG 9kg AI DD', 1), (34, 'uploads/maquina-de-lavar-lg-9kg-ai-dd-3.webp', 'Máquina de Lavar LG 9kg AI DD', 2), (34, 'uploads/maquina-de-lavar-lg-9kg-ai-dd-4.webp', 'Máquina de Lavar LG 9kg AI DD', 3);
UPDATE produtos SET imagem = 'uploads/maquina-de-lavar-lg-9kg-ai-dd-1.webp' WHERE id = 34;

-- HP LaserJet Pro MFP 4103fdw
DELETE FROM produto_imagens WHERE produto_id = 35;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (35, 'uploads/hp-laserjet-pro-mfp-4103fdw-1.webp', 'HP LaserJet Pro MFP 4103fdw', 0);
UPDATE produtos SET imagem = 'uploads/hp-laserjet-pro-mfp-4103fdw-1.webp' WHERE id = 35;

-- Epson EcoTank L3250
DELETE FROM produto_imagens WHERE produto_id = 36;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (36, 'uploads/epson-ecotank-l3250-1.webp', 'Epson EcoTank L3250', 0), (36, 'uploads/epson-ecotank-l3250-2.webp', 'Epson EcoTank L3250', 1), (36, 'uploads/epson-ecotank-l3250-3.webp', 'Epson EcoTank L3250', 2), (36, 'uploads/epson-ecotank-l3250-4.webp', 'Epson EcoTank L3250', 3);
UPDATE produtos SET imagem = 'uploads/epson-ecotank-l3250-1.webp' WHERE id = 36;

-- TP-Link Deco X20 (2 unidades)
DELETE FROM produto_imagens WHERE produto_id = 37;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (37, 'uploads/tp-link-deco-x20-2-unidades-1.webp', 'TP-Link Deco X20 (2 unidades)', 0), (37, 'uploads/tp-link-deco-x20-2-unidades-2.webp', 'TP-Link Deco X20 (2 unidades)', 1), (37, 'uploads/tp-link-deco-x20-2-unidades-3.webp', 'TP-Link Deco X20 (2 unidades)', 2);
UPDATE produtos SET imagem = 'uploads/tp-link-deco-x20-2-unidades-1.webp' WHERE id = 37;

-- Router Huawei 4G B535
DELETE FROM produto_imagens WHERE produto_id = 38;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (38, 'uploads/router-huawei-4g-b535-1.webp', 'Router Huawei 4G B535', 0);
UPDATE produtos SET imagem = 'uploads/router-huawei-4g-b535-1.webp' WHERE id = 38;

-- Power Bank Xiaomi 20000mAh 22,5W
DELETE FROM produto_imagens WHERE produto_id = 39;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (39, 'uploads/power-bank-xiaomi-20000mah-22-5w-1.webp', 'Power Bank Xiaomi 20000mAh 22,5W', 0);
UPDATE produtos SET imagem = 'uploads/power-bank-xiaomi-20000mah-22-5w-1.webp' WHERE id = 39;

-- Carregador Samsung 45W USB-C
DELETE FROM produto_imagens WHERE produto_id = 40;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (40, 'uploads/carregador-samsung-45w-usb-c-1.webp', 'Carregador Samsung 45W USB-C', 0), (40, 'uploads/carregador-samsung-45w-usb-c-2.webp', 'Carregador Samsung 45W USB-C', 1), (40, 'uploads/carregador-samsung-45w-usb-c-3.webp', 'Carregador Samsung 45W USB-C', 2), (40, 'uploads/carregador-samsung-45w-usb-c-4.webp', 'Carregador Samsung 45W USB-C', 3);
UPDATE produtos SET imagem = 'uploads/carregador-samsung-45w-usb-c-1.webp' WHERE id = 40;

-- Rato Dell sem fios MS5120W
DELETE FROM produto_imagens WHERE produto_id = 41;
INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (41, 'uploads/rato-dell-sem-fios-ms5120w-1.webp', 'Rato Dell sem fios MS5120W', 0), (41, 'uploads/rato-dell-sem-fios-ms5120w-2.webp', 'Rato Dell sem fios MS5120W', 1), (41, 'uploads/rato-dell-sem-fios-ms5120w-3.webp', 'Rato Dell sem fios MS5120W', 2), (41, 'uploads/rato-dell-sem-fios-ms5120w-4.webp', 'Rato Dell sem fios MS5120W', 3);
UPDATE produtos SET imagem = 'uploads/rato-dell-sem-fios-ms5120w-1.webp' WHERE id = 41;
