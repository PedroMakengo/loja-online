---
name: ux-loja
description: Padrões de UX da loja DIOTEKNO (PHP MVC + Tailwind + JS vanilla) — cartões inteiramente clicáveis, painel "Adicionado ao carrinho" com aumentar quantidade / Ver carrinho / Pagar agora, e o checklist para aplicar e verificar. Usar ao criar ou corrigir cartões (produto, promoção, banner, blog, pedido), ao mexer no fluxo de adicionar ao carrinho, ou quando o utilizador diz que "só o texto é clicável" ou pede para sugerir o próximo passo depois de adicionar.
---

# UX da loja — cartões clicáveis e fluxo do carrinho

## 1. Cartões inteiramente clicáveis

**Problema típico:** só o título/texto abre o produto; clicar na imagem, no preço ou no espaço vazio
não faz nada (ex.: "Promoções do dia", banners de promoção, cartões do blog).

**Padrão (obrigatório em qualquer cartão que leva a uma página):**

1. No elemento raiz do cartão: `data-card="<?= $link ?>"` + `cursor-pointer`.
2. Manter um `<a href>` real no título (acessibilidade, SEO, Ctrl+clique, leitores de ecrã).
3. A imagem também com link, fora do foco do teclado:
   `<a href="<?= $link ?>" tabindex="-1" aria-hidden="true" class="block h-full w-full"><img …></a>`
4. Botões dentro do cartão (carrinho, favorito, comparar) continuam `<button>` — o handler ignora-os.
5. **Nunca** pôr `<button>` ou outro `<a>` dentro de um `<a>`; por isso o cartão é `article/div/li` com
   `data-card`, e não um `<a>` gigante, quando tem acções internas.

O handler global está em `assets/js/app.js` (secção "Cartões inteiramente clicáveis"):
ignora cliques em `a, button, input, select, textarea, label, summary, [role=button]`, ignora quando há
texto seleccionado, e Ctrl/Cmd/Shift+clique abre noutro separador. Não duplicar — basta o atributo.

Não confiar só no "link esticado" (`after:absolute after:inset-0`): falha quando um ancestral tem
`position`, `overflow`, `transform` ou quando há camadas por cima (banners, carrosséis).

Onde já está aplicado: `partials/produto-card.php`, banners em `home/index.php` (quando têm `link`),
`blog/_cartao.php`, destaque em `blog/index.php`, listas de pedidos em `conta/painel.php` e `conta/pedidos.php`.

## 2. Depois de adicionar ao carrinho: sugerir o próximo passo

Em vez de um toast que desaparece, abre o painel **"Adicionado ao carrinho"** com:
- fotografia, nome, preço por unidade e total da linha;
- seletor **− / +** para aumentar (ou reduzir) a quantidade ali mesmo;
- quanto falta para a entrega grátis em Luanda;
- subtotal do carrinho;
- acções: **Pagar agora** (→ `/checkout`, botão principal), **Ver carrinho**, **Continuar a comprar**.

Peças:
| Ficheiro | Papel |
|---|---|
| `app/Views/partials/carrinho-adicionado-painel.php` | casca do diálogo (`#adicionado`, `data-gaveta`), incluída em `layouts/main.php` |
| `app/Views/partials/carrinho-adicionado.php` | conteúdo (espera `$item`, `$resumo`, `$aviso`) |
| `CarrinhoController::responder($ok, $msg, $destaque)` | com `$destaque` = id do produto, o JSON traz `adicionado` (HTML; `''` = produto saiu → JS fecha) |
| `assets/js/app.js` | após `/carrinho/adicionar` abre `#adicionado`; o − / + dentro do painel envia `painel=1` para receber o painel actualizado |

Regras:
- `adicionar` devolve sempre `adicionado` quando correu bem (incluindo o aviso "Atingiu o máximo…", mostrado no painel).
- `actualizar`/`remover` só devolvem `adicionado` quando recebem `painel=1`.
- Botões `data-comprar-ja` ("Comprar já") continuam a ir directos para o checkout, sem painel.
- O painel reutiliza `partials/quantidade.php` e o handler `[data-qtd]` existente — não criar outro seletor.
- Mobile: folha a subir do fundo (`rounded-t-3xl`, `pb-seguro`); desktop: cartão no canto superior direito.

## 3. "Pagar" — acção principal

Sempre que o cliente tem algo no carrinho, a acção mais visível deve levar a pagar:
- painel pós-adição: **Pagar agora** (`btn-primario w-full h-12`);
- gaveta do carrinho: "Finalizar compra" → `/checkout`;
- o checkout já trata login/morada/pagamento.

## Checklist ao aplicar

1. Editar views/JS seguindo os padrões acima.
2. Classes Tailwind novas → `npm run css` **na pasta do projecto** (senão o CSS sai vazio).
3. Mudou CSS/JS → aumentar `VERSAO_ASSETS` em `config/config.php`.
4. `php -l` nos PHP alterados e `node --check assets/js/app.js`.
5. Verificar os endpoints (servidor local `php -S 127.0.0.1:8099 servidor-dev.php`):
   GET `/` para obter `csrf-token` e um `data-adicionar`, depois POST com cabeçalhos
   `X-CSRF-Token`, `X-Requested-With: fetch`, `Accept: application/json`:
   - `/carrinho/adicionar` → `adicionado` com "Pagar agora" e qtd 1;
   - `/carrinho/actualizar` com `painel=1` → `adicionado` com a nova qtd;
   - `/carrinho/remover` com `painel=1` → `adicionado: ''`.
6. No browser: clicar na imagem, preço e espaço vazio de um cartão das "Promoções do dia" → abre o produto;
   o botão do carrinho no mesmo cartão adiciona sem navegar e abre o painel.
