# DIOTEKNO — Loja Virtual (Fases 1 a 7)

PHP MVC sem Composer · PDO · CSRF · AJAX · Tailwind compilado localmente · sem CDN.
Abre directamente na raiz do domínio (sem `/public`).

## Deploy no Coolify (Docker)
1. No Coolify: **New Resource → Public/Private Repository** → `PedroMakengo/loja-online`, branch `main`,
   **Build Pack: Docker Compose** (ficheiro `docker-compose.yml`).
2. No serviço `app`, definir o domínio (ex.: `https://loja.exemplo.ao`). As senhas do MySQL e o segredo
   dos webhooks são geradas pelo Coolify (`SERVICE_PASSWORD_*`) — não é preciso escrever nenhuma.
3. **Deploy.** No arranque o contentor espera pelo MySQL e corre `php database/migrar.php`, que aplica
   os ficheiros `database/NNN_*.sql` em falta (registados na tabela `migracoes`; seguro em cada deploy).
4. (Opcional) **Scheduled Tasks** no serviço `app`, a cada 10 minutos:
   `php /var/www/html/cron/expirar-pagamentos.php`

Contas criadas por `database/011_utilizadores_demo.sql`:

| Área | Email | Senha |
|---|---|---|
| Loja (cliente) — `/entrar` | `kay.edson@gmail.com` | `123456` |
| Backoffice — `/admin` | `admin@gmail.com` | `123456` |

Variáveis de ambiente (já com valores por omissão no `docker-compose.yml`):

| Variável | Omissão | Para quê |
|---|---|---|
| `AMBIENTE` | `prod` | `dev` mostra erros PHP no browser |
| `PAGAMENTOS_SIMULADOS` | `1` | botões "Simular pagamento" com o driver `simulado`; pôr `0` quando houver operadores reais |
| `EMAIL_DRIVER` | `log` | `log` grava em `storage/logs/emails.log`; `mail` usa `mail()` (o contentor não tem sendmail) |
| `EMAIL_LOJA` | `geral@diotekno.ao` | remetente dos emails |
| `DB_HOST` / `DB_PORT` / `DB_NOME` / `DB_USER` / `DB_PASS` | serviço `mysql` | ligação à BD (dá para apontar a uma BD gerida pelo Coolify) |
| `SEGREDO_WEBHOOK` | gerado | assinatura HMAC dos webhooks de pagamento |

Volumes persistentes: `storage/` (logs e comprovativos), `assets/img/produtos/uploads`,
`assets/img/blog/uploads` e os dados do MySQL. O CSS é compilado na build (Tailwind).

Teste local com Docker: `docker compose up --build` → http://localhost (expor a porta 80 se necessário).

## Instalação (cPanel)
1. Enviar todos os ficheiros para `public_html/` (ou uma subpasta — funciona sem alterações).
   Não é preciso enviar `node_modules/`.
2. Criar a base de dados (utf8mb4) e importar por ordem:
   `database/001_schema_base.sql` → `database/002_dados_iniciais.sql` → `database/003_conta_cliente.sql` → `database/004_checkout_pagamentos.sql` → `database/005_painel_admin.sql` → `database/006_avaliacoes_fidelidade.sql` → `database/007_marketing.sql` → `database/008_blog.sql` → `database/009_blog_artigos.sql` (opcional: 3 guias iniciais) → `database/010_imagens_produtos.sql` (fotografias dos produtos; enviar também `assets/img/produtos/uploads/`)
   (instalação existente: importar só os ficheiros que faltam)
3. Editar `config/db.php` (credenciais) e `config/config.php` (contactos, WhatsApp, NIF, `AMBIENTE = 'prod'`).
4. PHP 8.1+ com `pdo_mysql` e `mbstring`; Apache com `mod_rewrite`.

Painel em `/admin`. Acesso inicial: `admin@diotekno.ao` / `Diotekno@2026` — o painel obriga a definir uma senha nova no primeiro acesso.

## Desenvolvimento local
    php -S localhost:8000 servidor-dev.php

## CSS
Editar `src/app.css` / classes nas views e recompilar **a partir da pasta do projecto** (os caminhos
de `content` no `tailwind.config.js` são relativos à pasta actual; noutra pasta o CSS sai vazio):

    npm install
    npm run css          # ou npm run css:watch

Depois de recompilar CSS/JS em produção, aumentar `VERSAO_ASSETS` em `config/config.php`.

## Fase 2 — Conta do cliente
- `/entrar`, `/registar`, `/recuperar-senha`, `/redefinir-senha/{token}`, `POST /sair`
- `/conta` (resumo), `/conta/pedidos`, `/conta/dados` (+ alterar senha), `/conta/enderecos`, `/favoritos`
- Segurança: senhas com `password_hash`, regeneração da sessão no login, limite de
  6 tentativas por email em 15 minutos, "manter sessão" com seletor/validador rotativo,
  tokens de recuperação guardados só como hash (válidos 60 min, uso único),
  protecção contra redirecção externa no parâmetro `voltar`.
- O carrinho do visitante junta-se ao da conta no login; ao sair, o dispositivo fica sem carrinho.
- Emails: em `AMBIENTE = 'dev'` ficam em `storage/logs/emails.log`; em produção usam `mail()`.
  A pasta `storage/` tem de ter permissão de escrita.

## Fase 3 — Checkout e pagamentos
- `/checkout`: morada guardada, entrega (domicílio / expressa em Luanda / levantamento),
  6 métodos de pagamento, NIF na factura e observações. Totais recalculados por AJAX.
- Criação do pedido numa única transacção: preços lidos da BD, stock bloqueado
  (`SELECT … FOR UPDATE`) e reservado, cupão revalidado, chave de idempotência
  contra pedidos duplicados.
- `/conta/pedidos/{numero}`: instruções de pagamento por método, contagem até expirar,
  actualização automática quando o pagamento é confirmado, envio de comprovativo
  (PDF/JPG/PNG verificados pelo conteúdo, guardados fora da web em `storage/comprovativos`),
  nova tentativa / mudança de método, cancelamento com devolução de stock, rastreamento por etapas.
- Webhook `POST /pagamentos/notificacao` com assinatura HMAC-SHA256 (`X-Assinatura`), idempotente,
  com registo em `pagamento_notificacoes`.
- Prazos: referência 48 h, push 15 min (pode repetir), transferência 72 h; pedidos pendentes há
  mais de 72 h são cancelados. A limpeza corre sozinha nas páginas e, opcionalmente, por cron:
  `*/10 * * * * php /caminho/cron/expirar-pagamentos.php`

### Antes de ir para produção
1. `config/pagamentos.php`: entidade, IBAN e titular reais; `segredo_webhook` aleatório e longo.
2. Ligar cada operador em `Pagamentos::pedirAoOperador()` e mudar `driver` para outro valor que não
   `simulado`. Manter em `metodos_activos` só os métodos com contrato activo
   (a transferência funciona sem operador).
3. `AMBIENTE = 'prod'` — os botões "Simular pagamento" desaparecem.

## Fase 4 — Painel administrativo (`/admin`)
- **Resumo:** o que precisa de acção (transferências a verificar, pedidos por preparar, stock baixo,
  mensagens), vendas do dia e do mês com comparação, gráfico de 30 dias, mais vendidos.
- **Pedidos:** fila por estado (os mais antigos primeiro nos que exigem acção), pesquisa por nº, nome
  ou telefone; confirmação manual de transferências com o comprovativo (servido só a quem tem permissão),
  pedido de novo comprovativo, passos Preparação → Enviado (com código de rastreamento fora de Luanda)
  → Entregue com email ao cliente, cancelamento com devolução de stock (pagos ficam "reembolsados"),
  notas internas, histórico e guia de preparação para imprimir.
- **Produtos:** filtros (categoria, marca, estado, stock baixo/esgotado), stock editável na própria lista,
  formulário com galeria de imagens (validadas pelo conteúdo), especificações por secção, preço riscado
  e "promoção do dia", custo com cálculo de margem, campos para o Google. Descrições passam por um
  filtro de HTML que remove scripts e atributos perigosos.
- **Clientes:** valor gasto, pedidos, moradas, favoritos, bloqueio de conta.
- **Motor genérico** (`config/crud.php`): categorias, marcas, cupões, banners e zonas de entrega.
  Para acrescentar uma entidade simples basta descrevê-la nesse ficheiro.
- **Relatórios:** por período, forma de pagamento, categoria, província e produto; margem bruta
  estimada; exportação CSV que abre directamente no Excel em português.
- **Administradores e papéis:** Administrador geral, Gestor, Stock, Atendimento. Contas novas recebem
  senha temporária que tem de ser trocada; sessão termina após 2 h sem actividade; registo de auditoria
  de todas as acções.

## Fase 5 — Avaliações, comparador e programa de pontos
- **Avaliações:** só clientes com o produto num pedido *entregue*, uma por produto, com selo
  "Compra verificada". Ficam pendentes até serem publicadas em `/admin/avaliacoes`, onde a loja pode
  responder publicamente. A média e o total do produto (e o JSON-LD para o Google) são calculados a
  partir das avaliações publicadas. **O 006 repõe a zero as classificações fictícias da seed.**
- **Comparador:** até 4 produtos (sem conta), botão nos cartões e na página de produto, barra flutuante,
  tabela com as diferenças a negrito e opção "só diferenças".
- **Pontos** (`config/fidelidade.php`): 1 ponto por cada 1 000 Kz pagos, 200 de bónus na 1.ª compra,
  50 por avaliação publicada; 1 ponto = 10 Kz, a partir de 500 pontos, até 20% dos produtos.
  Cancelamentos devolvem os pontos usados; reembolsos retiram os ganhos. Histórico em `/conta/pontos`;
  ajustes manuais no painel (ficam registados com o autor).

## Fase 6 — Carrinho abandonado e newsletter
Regras em `config/marketing.php`. **Requer o cron** (cPanel > Tarefas Cron, a cada 15 minutos),
passando o domínio para os links dos emails ficarem correctos:

    */15 * * * * /usr/local/bin/php /home/UTILIZADOR/public_html/cron/marketing.php diotekno.ao

- **Carrinho abandonado:** clientes com conta recebem um email 1 hora depois de deixarem o carrinho
  e outro 24 horas depois, com um cupão pessoal de 5% (uso único, 48 h) já aplicado ao carrinho.
  Pára se comprarem, mexerem no carrinho ou desligarem os lembretes (link no email ou em
  "Dados pessoais"). Ignora carrinhos abaixo do mínimo ou só com produtos esgotados.
  No painel: `/admin/marketing/carrinhos` com valor parado, cliques, compras e receita recuperada,
  e botão para contactar o cliente no WhatsApp.
- **Newsletter:** `/admin/newsletter` — campanhas com produtos automáticos (promoções, novidades,
  mais vendidos) ou escolhidos, pré-visualização, email de teste, envio em fila respeitando o limite
  por hora do alojamento, exportação dos contactos. Destinatários: subscritores do rodapé + clientes
  que aceitaram promoções, sem repetidos.
- Todos os emails de marketing levam link de cancelamento assinado e o cabeçalho
  `List-Unsubscribe` com "um clique", exigido pelo Gmail e pelo Yahoo para envios em massa.
- **Antes de produção:** mudar `segredo_links`; confirmar o limite de envios por hora do alojamento;
  configurar SPF, DKIM e DMARC no domínio (sem isto os emails vão para o spam). Para volumes maiores,
  trocar `Email::enviar()` por SMTP de um serviço de envio.
- Em desenvolvimento os emails ficam em `storage/logs/emails.log`, com os links visíveis.

## Fase 7 — Blog (`/blog`)
- Listagem com artigo em destaque e filtro por categoria, artigo com índice automático (a partir dos
  títulos de secção, marca a secção em leitura), tempo de leitura, partilha por WhatsApp/Facebook,
  "Produtos neste artigo" e "Continue a ler".
- **Cartões de produto no texto:** `[produto:endereco-do-produto]` numa linha mostra o produto com
  preço e stock actuais e botão de compra — os guias nunca ficam com preços desactualizados.
  O painel recusa publicar se um produto citado não existir.
- Sem fotografia, cada artigo recebe uma capa tipográfica na cor da categoria.
- Editor no painel (`/admin/blog`) com barra de formatação e inserção de produtos, rascunho,
  publicação imediata ou **agendada**, pré-visualização (só administradores), capa e campos para o Google.
  Categorias em `/admin/c/blog_categorias`.
- SEO: JSON-LD `BlogPosting` e breadcrumb, artigos no `sitemap.xml`, feed RSS em `/blog/feed.xml`.

## Estrutura
    index.php              front controller + rotas
    config/                config.php, db.php
    core/                  Router, Controller, Model, Database, Csrf, Seo, helpers
    app/Controllers/       loja, conta, checkout, pedidos, pagamentos (webhook)
    app/Controllers/Admin/ painel administrativo
    app/Servicos/          Checkout, Pagamentos, Entrega
    app/Models/            Produto, Categoria, Marca, Banner, Carrinho, ZonaEntrega, Newsletter, Contacto
    app/Views/             layouts, partials, home, catalogo, produto, carrinho, paginas, erros
    assets/                css (compilado), js/app.js, fonts, img
    database/              SQL sequencial
    src/app.css            fonte Tailwind (tokens claro/escuro)

## Imagens de produto
Colocar em `assets/img/produtos/` e indicar o nome do ficheiro em `produtos.imagem`
(galeria: tabela `produto_imagens`). Sem imagem, é usada a ilustração SVG da categoria.

## Próximas fases
1. IA: recomendações de produtos e assistente de compras
2. Afiliados e marketplace
3. Apps Android/iPhone (PWA ou Capacitor)
