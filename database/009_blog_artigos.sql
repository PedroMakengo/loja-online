-- =====================================================================
-- 009_blog_artigos.sql — Três guias iniciais (editáveis no painel)
-- =====================================================================
SET NAMES utf8mb4;

INSERT INTO blog_posts (categoria_id, autor_id, titulo, slug, resumo, conteudo, publicado_em, estado, destaque)
SELECT (SELECT id FROM blog_categorias WHERE slug = 'guias-de-compra'), (SELECT MIN(id) FROM admins), 'Como escolher um telemóvel até 400 000 Kz', 'como-escolher-telemovel-ate-400-mil-kz', 'Bateria, armazenamento, câmara e garantia: o que realmente conta num telemóvel de gama média, e as nossas sugestões para cada orçamento.', '<p>Nesta faixa de preço há telemóveis muito bons e outros que parecem bons na ficha técnica mas desiludem no dia a dia. A diferença está em quatro pontos: bateria, armazenamento, actualizações e garantia. Os megapíxeis da câmara vêm depois.</p>
<h2>Comece pelo uso que lhe vai dar</h2>
<p>Antes de comparar modelos, responda a três perguntas: usa sobretudo WhatsApp, redes sociais e chamadas? Tira muitas fotografias e vídeos? Joga? A resposta decide onde vale a pena gastar mais.</p>
<ul>
<li><strong>Uso básico</strong> (mensagens, chamadas, YouTube): qualquer modelo actual com 4 GB de RAM serve. Gaste o que poupa em armazenamento.</li>
<li><strong>Fotografia e redes sociais</strong>: procure um ecrã AMOLED e uma câmara principal com estabilização óptica (OIS).</li>
<li><strong>Jogos</strong>: 8 GB de RAM e um ecrã de 120 Hz fazem mais diferença do que a câmara.</li>
</ul>
<h2>Bateria primeiro: os cortes de energia contam</h2>
<p>Com cortes de energia frequentes, um telemóvel que chega ao fim do dia com folga vale mais do que um ligeiramente mais rápido. Hoje, 5000 mAh é o valor de referência nesta gama. Confirme também a potência de carregamento: 25 W ou mais recupera metade da bateria em cerca de meia hora quando a luz volta.</p>
<blockquote>Um power bank de 20 000 mAh carrega a maioria dos telemóveis três a quatro vezes. É o acessório que mais compensa em Luanda.</blockquote>
<p>[produto:power-bank-xiaomi-20000mah-22-5w]</p>
<h2>Armazenamento: 128 GB no mínimo</h2>
<p>As fotografias e os vídeos do WhatsApp ocupam espaço depressa. Com 64 GB, o telemóvel fica cheio ao fim de poucos meses e começa a ficar lento. Escolha 128 GB; se guarda muitos vídeos, 256 GB. Nem todos os modelos aceitam cartão de memória, por isso confirme antes de contar com isso.</p>
<h2>Câmara: os megapíxeis não são tudo</h2>
<p>Uma câmara de 50 MP com bom processamento tira melhores fotografias à noite do que uma de 108 MP sem estabilização. Mais útil do que o número: ver fotografias tiradas com o próprio modelo em condições parecidas com as suas, de dia e à noite.</p>
<h2>Actualizações e garantia oficial</h2>
<p>Um telemóvel com garantia oficial em Angola é reparado cá, com peças originais. Os importados sem garantia local podem ficar semanas parados à espera de uma peça. Veja também quantos anos de actualizações de segurança o fabricante promete: alguns modelos Samsung recentes de gama média têm seis anos, o que ajuda a manter as aplicações bancárias a funcionar.</p>
<h2>As nossas sugestões</h2>
<p><strong>Até 200 000 Kz:</strong> o essencial bem feito, com ecrã grande e bateria para um dia longo.</p>
<p>[produto:samsung-galaxy-a16-128gb]</p>
<p><strong>Entre 300 000 e 400 000 Kz:</strong> o ponto de equilíbrio. Ecrã AMOLED, 5G e câmaras bem mais capazes.</p>
<p>[produto:samsung-galaxy-a56-5g-128gb]</p>
<p>[produto:xiaomi-redmi-note-14-pro-256gb]</p>
<h2>Resumo</h2>
<ol>
<li>Bateria de 5000 mAh e carregamento rápido.</li>
<li>Pelo menos 128 GB de armazenamento.</li>
<li>Garantia oficial em Angola.</li>
<li>Só depois: câmara e desempenho, de acordo com o seu uso.</li>
</ol>
<p>Ainda com dúvidas? Diga-nos o orçamento e o que mais usa no telemóvel, pelo WhatsApp, e sugerimos dois ou três modelos.</p>', '2026-09-22 09:00:00', 'publicado', 1
 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM blog_posts WHERE slug = 'como-escolher-telemovel-ate-400-mil-kz');

INSERT INTO blog_posts (categoria_id, autor_id, titulo, slug, resumo, conteudo, publicado_em, estado, destaque)
SELECT (SELECT id FROM blog_categorias WHERE slug = 'dicas'), (SELECT MIN(id) FROM admins), 'Ar condicionado Inverter: vale a pena em Luanda?', 'ar-condicionado-inverter-vale-a-pena', 'Custa mais na compra, mas gasta menos todos os meses e aguenta melhor as variações de corrente. Explicamos quando compensa e como escolher a potência certa.', '<p>Num clima quente como o de Luanda, o ar condicionado trabalha muitas horas por dia durante boa parte do ano. É por isso que a tecnologia Inverter, que parece um pormenor na etiqueta, faz diferença na factura da luz e na vida útil do aparelho.</p>
<h2>Qual é a diferença</h2>
<p>Um aparelho convencional liga o compressor no máximo até a divisão arrefecer, desliga-o, e volta a ligá-lo no máximo quando a temperatura sobe. Esse liga-desliga constante gasta muita energia nos arranques.</p>
<p>Um aparelho <strong>Inverter</strong> ajusta a velocidade do compressor: arrefece depressa no início e depois mantém a temperatura a trabalhar a baixa potência, sem desligar. O resultado é um consumo bastante menor em uso contínuo, menos ruído e uma temperatura mais estável.</p>
<h2>Quando compensa</h2>
<ul>
<li><strong>Uso diário de várias horas</strong> (quarto à noite, escritório): compensa quase sempre. A diferença de preço é recuperada na factura.</li>
<li><strong>Uso ocasional</strong> (sala de visitas usada ao fim de semana): a poupança é menor e um modelo convencional pode bastar.</li>
<li><strong>Com gerador ou rede instável</strong>: os modelos Inverter arrancam de forma gradual, o que pede menos ao gerador no momento do arranque.</li>
</ul>
<h2>Que potência escolher</h2>
<p>A potência mede-se em BTU. Uma regra prática para divisões com pé-direito normal:</p>
<table>
<thead><tr><th>Área da divisão</th><th>Potência indicativa</th></tr></thead>
<tbody>
<tr><td>Até cerca de 15 m²</td><td>9 000 BTU</td></tr>
<tr><td>15 a 25 m²</td><td>12 000 BTU</td></tr>
<tr><td>25 a 35 m²</td><td>18 000 BTU</td></tr>
<tr><td>Acima de 35 m²</td><td>24 000 BTU ou mais</td></tr>
</tbody>
</table>
<p>Suba um escalão se a divisão tiver muita exposição ao sol da tarde, janelas grandes ou muitas pessoas. Um aparelho pequeno demais trabalha sempre no máximo e nunca chega à temperatura pedida.</p>
<blockquote>Na dúvida, envie-nos as medidas da divisão e a orientação das janelas pelo WhatsApp. Ajudamos a escolher sem custos.</blockquote>
<h2>Instalação e manutenção</h2>
<p>Uma instalação mal feita anula as vantagens de um bom aparelho. Garanta que a unidade exterior fica à sombra e com espaço para o ar circular, e que a tubagem é a mais curta possível.</p>
<p>Limpe os filtros da unidade interior a cada duas a quatro semanas: basta lavá-los com água e deixá-los secar. Filtros sujos aumentam o consumo e pioram a qualidade do ar. Uma revisão técnica anual mantém o gás e a drenagem em ordem.</p>
<h2>A nossa sugestão</h2>
<p>[produto:ar-condicionado-samsung-windfree-12000-btu]</p>
<p>Para quartos e salas até cerca de 25 m², este modelo combina Inverter com o modo WindFree, que arrefece sem soprar ar frio directamente sobre as pessoas, o que torna o uso nocturno mais confortável.</p>', '2026-09-10 10:00:00', 'publicado', 0
 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM blog_posts WHERE slug = 'ar-condicionado-inverter-vale-a-pena');

INSERT INTO blog_posts (categoria_id, autor_id, titulo, slug, resumo, conteudo, publicado_em, estado, destaque)
SELECT (SELECT id FROM blog_categorias WHERE slug = 'guias-de-compra'), (SELECT MIN(id) FROM admins), 'Portátil para a universidade: o que verificar antes de comprar', 'portatil-para-universidade-o-que-verificar', 'SSD, memória, bateria e peso: o guia para escolher um portátil que aguente o curso todo sem ficar lento ao segundo ano.', '<p>Um portátil para a universidade tem de durar quatro ou cinco anos, ir para as aulas todos os dias e não ficar lento quando se abrem vinte separadores do navegador e um trabalho em Word ao mesmo tempo. Estes são os pontos a confirmar antes de comprar.</p>
<h2>SSD, nunca disco rígido</h2>
<p>É a característica que mais influencia a rapidez no dia a dia. Um portátil com SSD liga em segundos e abre os programas de imediato; com disco rígido tradicional, tudo demora. Para estudantes, 512 GB é o tamanho certo: 256 GB enche depressa com materiais das cadeiras e programas.</p>
<h2>Memória: 8 GB chegam, 16 GB duram mais</h2>
<p>Com 8 GB de RAM consegue usar o navegador, o Office e as videochamadas sem problemas. Se o curso usar programas mais exigentes (engenharia, arquitectura, design, programação) ou se quer que o portátil continue rápido daqui a quatro anos, escolha 16 GB.</p>
<ul>
<li><strong>Direito, Economia, Letras, Gestão:</strong> 8 GB e um processador Core i5 ou Ryzen 5 chegam bem.</li>
<li><strong>Engenharias e Informática:</strong> 16 GB e um processador Ryzen 7 ou Core i7.</li>
<li><strong>Arquitectura e Design:</strong> 16 GB e, idealmente, placa gráfica dedicada.</li>
</ul>
<h2>Bateria e peso</h2>
<p>Se o portátil vai para as aulas todos os dias, conte com tomadas ocupadas ou sem energia. Procure modelos que anunciem pelo menos 8 horas de autonomia em uso real e que pesem menos de 1,8 kg. Desconfie dos valores máximos anunciados: em uso normal, a autonomia costuma ser menor.</p>
<h2>Teclado e ligações</h2>
<p>Confirme que o teclado tem a disposição portuguesa (com Ç e acentos nas teclas) se escreve muito. Veja também as portas: pelo menos uma USB-A para pens e uma HDMI dá jeito para apresentações nas aulas.</p>
<h2>As nossas sugestões</h2>
<p><strong>O mais vendido para estudantes:</strong> robusto, com teclado numérico e um preço acessível.</p>
<p>[produto:hp-250-g10-core-i5-8gb]</p>
<p><strong>Para cursos mais exigentes:</strong> 16 GB de memória e Ryzen 7, num corpo leve.</p>
<p>[produto:lenovo-ideapad-slim-3-ryzen-7]</p>
<p><strong>Para quem quer a melhor bateria:</strong> silencioso, muito leve e com autonomia para um dia inteiro de aulas.</p>
<p>[produto:macbook-air-13-m3-256gb]</p>
<blockquote>Guarde a factura com NIF: além da garantia, pode ser necessária para despesas de educação.</blockquote>', '2026-08-28 09:30:00', 'publicado', 0
 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM blog_posts WHERE slug = 'portatil-para-universidade-o-que-verificar');
