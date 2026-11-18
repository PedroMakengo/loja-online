---
name: categorias-subcategorias
description: Árvore de categorias da loja DIOTEKNO (categoria › subcategoria › sub-subcategoria, até 3 níveis, ao estilo NCR) — gestão no painel /admin/categorias, mega-menu da loja, menu mobile por níveis, página /categorias, listagem com produtos de todo o ramo, subcategorias em botões e breadcrumbs. Usar ao criar/mover/apagar categorias, mexer no menu de categorias, nos filtros por categoria, no formulário de produto (campo categoria), ou quando pedirem "subcategorias", "mega menu", "menu como a NCR".
---

# Categorias e subcategorias

```
Informática                         ← nível 1: Categoria (barra do menu)
├─ Computadores Desktops            ← nível 2: Subcategoria (coluna do mega-menu)
│  ├─ All-In-One                    ← nível 3: Sub-subcategoria (link na coluna)
│  └─ Mini PC
├─ Computadores Portáteis
│  └─ Macbook
└─ Impressão                        ← subcategoria sem filhas: lista a negrito na última coluna
```

## 1. Modelo de dados

- Tabela `categorias` com `parent_id` (FK para `categorias.id`, `ON DELETE SET NULL`). A coluna já existia desde o 001.
- Dados de demonstração: `database/013_subcategorias_informatica.sql` cria Informática › Desktops (StandAlone, All-In-One, Mini PC) / Portáteis (Casa e Escritório, Gaming, Macbook) / Impressão / Redes e Internet, e põe os produtos demo na sub-subcategoria certa. É conservadora (só mexe no que ainda está como o 002 deixou) e corre sozinha no deploy (`migrar.php`). **Sem migração, a produção não tem subcategorias**: o que se cria no XAMPP fica só na BD local.
- `014_categorias_estilo_ncr.sql` agrupa o topo como a NCR (Smartphones e Tablets, Imagem e Som; Gaming e Electrodomésticos com subcategorias) e move os produtos demo para as sub-subcategorias (iPhone, Samsung Galaxy, iPad…). `015_mais_categorias_menu.sql` junta Escritório e Papelaria, Energia, Informática › Monitores/Armazenamento/Software, Imagem e Som › Soundbars/Projectores, com 18 produtos demo **sem fotografia** (usam a imagem da categoria). Ambas mantêm o `destaque` das antigas, para a secção "Compre por categoria" da home não mudar.
- Os produtos têm **uma** categoria (`produtos.categoria_id`), de qualquer nível. Boa prática: pôr o produto no nível mais específico (ex.: Macbook, não Informática).
- Limite: `Categoria::NIVEIS = 3`; nomes dos níveis em `Categoria::NOMES_NIVEL`.
- Visibilidade na loja: uma categoria só aparece se ela **e todos os antepassados** estiverem `activo = 1`. Desactivar "Informática" esconde todo o ramo, incluindo `/categoria/<slug>` das filhas (dá 404).
- Ordem: `ordem`, depois `nome`, entre irmãs.

## 2. Peças

| O quê | Onde |
|---|---|
| Árvore, ramo, caminho, breadcrumb, validações de nível | [app/Models/Categoria.php](../../../app/Models/Categoria.php) |
| Gestor no painel (lista em árvore, form, apagar, mover produtos) | [Admin/CategoriasController.php](../../../app/Controllers/Admin/CategoriasController.php), views `admin/categorias/lista.php` e `form.php` |
| Rotas | `index.php`: `/admin/categorias`, `/novo?pai=ID`, `/guardar`, `/{id}`, `/{id}/apagar`, `/{id}/mover-produtos` (o antigo `/admin/c/categorias` abre a lista nova) |
| Mega-menu desktop (abre ao clicar, `data-dropdown`) | `partials/header.php` (barra) + [partials/mega-menu.php](../../../app/Views/partials/mega-menu.php) (painel) |
| Menu mobile por níveis (`data-sub-abrir` → `data-sub`, com "Voltar") | `partials/header.php`, bloco "Menu lateral" |
| Página de categoria com produtos do ramo + botões de subcategorias | `CatalogoController::categoria()` + `catalogo/lista.php` |
| Página `/categorias` (cartões com subcategorias) | `paginas/categorias.php` |
| Breadcrumb do produto (Início › Informática › Portáteis › Produto) | `ProdutoController` |
| `<select>` de categorias indentado (form e filtro de produtos) | `Categoria::opcoesIndentadas()`, `Admin\ProdutosController::categoriasIndentadas()` |

### API do modelo

| Método | Para quê |
|---|---|
| `arvore()` | Categorias de topo **visíveis**, cada uma com `filhos` (recursivo), `nivel`, `produtos_directos`, `total_produtos` (soma do ramo) e `imagem` (slug da imagem a usar). |
| `arvoreMenu()` | `arvore()` **sem os ramos vazios** (`total_produtos = 0`). É o `$menuCategorias` dos layouts e a base de `/categorias`. Usar sempre esta na navegação. |
| `todasActivas()` | As mesmas, em lista plana (pai antes dos filhos): sitemap, pesquisa e destaques da home. |
| `porSlug($slug)` | Só devolve categorias visíveis (o nó traz `filhos`). |
| `idsDoRamo($id)` | Ids da categoria e das subcategorias visíveis → filtro `categoria_ids` do `Produto::listar()`. |
| `caminho($id)` / `breadcrumb($id, $comProdutos)` | Antepassados até à categoria. |
| `arvoreAdmin()` | Árvore completa (inclui inactivas e órfãs) em lista plana, para o painel. |
| `descendentes($id)`, `nivel($id)`, `alturaDoRamo($id)` | Validações do painel e filtro de produtos no painel (incluem inactivas). |

Cache: `visiveis()` faz **uma** consulta por pedido (estática). Não chamar SQL por categoria em ciclos de views.

## 3. Regras do painel (já implementadas: não as contornar)

1. **Sem ciclos:** a mãe não pode ser a própria nem uma descendente (`descendentes()`).
2. **Máx. 3 níveis:** `nivel(mae) + alturaDoRamo(categoria) ≤ 3`. Mover um ramo com filhas também conta. O `<select>` da mãe já só mostra opções válidas, e o servidor volta a validar.
3. **Endereço (slug) único**, gerado do nome se vier vazio (`slugify`, que trata os acentos: "Informática" → `informatica`).
4. **Apagar** só sem subcategorias **e** sem produtos. Senão: "Mover produtos" (no form de edição) ou desactivar.
5. Texto com UTF-8 inválido é rejeitado com erro de campo (não deixar chegar `NULL` à BD).
6. Todas as acções ficam no registo de actividade (`AdminAuth::registar`): `criou`, `editou`, `apagou`, `moveu_produtos`.

## 4. Loja — padrões de navegação

- **Barra desktop (como a NCR):** "☰ Todos os Produtos" e até 7 categorias de topo espalhadas (`justify-between`), cada uma com a seta `seta-baixo` que roda 180° quando o painel está aberto (`group-aria-expanded/botao`); abaixo de `xl` só cabem 5 (as outras ficam em "Todos os Produtos"). Promoções, Novidades, Marcas e Guias passaram para a faixa escura do topo. Sem filhas = link. Com filhas = `<button data-dropdown-botao>` que, **ao clicar** (como na NCR), abre o painel `data-dropdown-painel` (mega-menu); clicar outra vez, clicar fora, abrir outro ou Escape fecha. A seta roda com `aria-expanded`. O painel é `absolute inset-x-0 top-full` em relação à `<nav class="relative">`: não pôr `relative` no item (`data-dropdown`).
- **Colunas do mega-menu:** subcategorias **com** filhas viram colunas (título + links + "Ver todos os produtos"). As **sem** filhas juntam-se, a negrito, na última coluna (como "Armazenamento", "Impressão" na NCR). Até 4 colunas por linha. Se **nenhuma** subcategoria tiver filhas (Energia, Gaming), cada uma vira uma coluna com "N produtos".
- **"Todos os Produtos" (antes "Todas as categorias"):** lista das categorias de topo à esquerda (`data-cat-raiz`) e painel à direita (`data-cat-painel`) com as subcategorias em 3 colunas (`mega-menu.php` com `$compacto = true`). As categorias da esquerda são `<button>`: **clicar** numa (ou setas ↑/↓) troca o painel (`assets/js/app.js`). Escape fecha. A primeira categoria começa aberta.
- **Categorias vazias** (sem produtos no ramo) não aparecem em nenhum menu, em `/categorias` nem nos botões; no painel ficam com a etiqueta "Vazia · fora do menu". A página `/categoria/<slug>` continua a abrir.
- **Mobile (como na NCR):** categoria sem filhas = link; com filhas = botão que abre um ecrã só dela (`data-sub`, absoluto sobre a lista) com "‹ Menu", o título, "Ver todos os N produtos" e as subcategorias; as que têm sub-subcategorias abrem outro ecrã ("‹ <Categoria>" volta ao anterior). Fechar a gaveta repõe o 1.º nível (`fecharSubmenus`).
- **Página de categoria:** lista os produtos de **todo o ramo** (`categoria_ids`). Por baixo do título aparecem os botões das filhas; numa categoria sem filhas, os das irmãs, com a actual marcada (`aria-current`). As facetas (marca, preço) respeitam o ramo.
- **Imagem da categoria:** `assets/img/categorias/<slug>.webp`. Sem ficheiro, usa a da primeira subcategoria que tenha (Informática → `computadores.webp`); em último caso, `acessorios`. Nas views usar `imagemCategoria($c['imagem'] ?? $c['slug'])`. Não há upload no painel.
- Classes Tailwind novas em views exigem `npm run css` (o `assets/css/app.css` vai no Git).
- **Cache do browser:** CSS e JS ficam 1 ano em cache (`.htaccess`). `asset()` junta a data do ficheiro à versão (`?v=VERSAO_ASSETS.<mtime>`), por isso um `npm run css` chega para os clientes receberem o CSS novo. Se alguém vir "o menu desformatado" depois de uma alteração, a primeira suspeita é CSS antigo em cache: confirmar o `?v=` no HTML.

## 5. Verificar (depois de mexer nesta área)

Local (XAMPP, admin `admin@gmail.com` / `123456`):

1. `/admin/categorias`: árvore indentada, níveis com etiqueta, produtos "directos / ramo", "+ subcategoria" só até ao nível 2.
2. Criar uma subcategoria com `?pai=ID`; tentar: ciclo (mãe = descendente), 4.º nível, slug repetido, apagar uma categoria com filhas ou produtos → todos devem voltar com mensagem de erro.
3. "Mover produtos" de uma categoria para outra e voltar: as contagens batem certo.
4. Loja: a barra mostra a seta nas categorias com filhas; ao clicar, o painel em colunas (e "Todas as categorias" troca o painel ao clicar à esquerda); `/categoria/<topo>` soma os produtos das filhas; os botões de subcategorias e o breadcrumb Início › Produtos › Pai › Filha; o breadcrumb do produto com o caminho completo; `/categorias` e o menu mobile por níveis (entrar e "Voltar").
5. Desactivar a categoria-mãe → o ramo desaparece do menu e `/categoria/<filha>` dá 404; reactivar.
6. `/admin/produtos?categoria=<topo>` inclui os produtos das subcategorias; o `<select>` do form de produto aparece indentado (`— Sub`, `— — Sub-sub`).
7. Sem avisos PHP nas páginas (procurar `Warning|Deprecated|Fatal` no HTML).

8. Uma subcategoria sem produtos não aparece no menu nem nos botões, e aparece no painel com "Vazia · fora do menu".

Para capturas sem browser aberto: `chrome --headless=new --virtual-time-budget=3000 --screenshot` sobre o HTML guardado com `<base href="http://localhost/">` (`--blink-settings=preferredColorScheme=1` = modo claro). Mega-menu aberto: injectar antes de `</body>` um script que, no `load`, faz `click()` em `[aria-controls=mega-<id>]`. "Todas as categorias" numa categoria: `click()` em `[data-dropdown-botao]` e depois no `[data-cat-raiz]` pretendido. Mobile: `--window-size=390,800`, `click()` em `[data-abrir=menu-mobile]` e, 100 ms depois, no `[data-sub-abrir]`.
