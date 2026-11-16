---
name: categorias-subcategorias
description: Árvore de categorias da loja DIOTEKNO (categoria › subcategoria › sub-subcategoria, até 3 níveis, ao estilo NCR) — gestão no painel /admin/categorias, mega-menu da loja, acordeão no mobile, página /categorias, listagem com produtos de todo o ramo, subcategorias em botões e breadcrumbs. Usar ao criar/mover/apagar categorias, mexer no menu de categorias, nos filtros por categoria, no formulário de produto (campo categoria), ou quando pedirem "subcategorias", "mega menu", "menu como a NCR".
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

- Tabela `categorias` com `parent_id` (FK para `categorias.id`, `ON DELETE SET NULL`). **Não há migração**: a coluna já existia.
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
| Mega-menu desktop (hover e foco, sem JS) | `partials/header.php` (barra) + [partials/mega-menu.php](../../../app/Views/partials/mega-menu.php) (painel) |
| Menu mobile (acordeão `<details>`) | `partials/header.php`, bloco "Menu lateral" |
| Página de categoria com produtos do ramo + botões de subcategorias | `CatalogoController::categoria()` + `catalogo/lista.php` |
| Página `/categorias` (cartões com subcategorias) | `paginas/categorias.php` |
| Breadcrumb do produto (Início › Informática › Portáteis › Produto) | `ProdutoController` |
| `<select>` de categorias indentado (form e filtro de produtos) | `Categoria::opcoesIndentadas()`, `Admin\ProdutosController::categoriasIndentadas()` |

### API do modelo

| Método | Para quê |
|---|---|
| `arvore()` | Categorias de topo **visíveis**, cada uma com `filhos` (recursivo), `nivel`, `produtos_directos` e `total_produtos` (soma do ramo). É o `$menuCategorias` dos layouts. |
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

- **Barra desktop:** até 7 categorias de topo. As que têm filhas abrem o mega-menu com `group-hover/mega` e `group-focus-within/mega` (teclado: Tab entra no painel). O contentor de cada item ocupa a altura toda da barra (`self-stretch`), para o painel não fechar ao descer o rato. O painel é `absolute inset-x-0 top-full` em relação à `<nav class="relative">`: não pôr `relative` no item.
- **Colunas do mega-menu:** subcategorias **com** filhas viram colunas (título + links + "Ver todos os produtos"). As **sem** filhas juntam-se, a negrito, na última coluna (como "Armazenamento", "Impressão" na NCR). Até 4 colunas por linha.
- **"Todas as categorias":** grelha das categorias de topo, com as 4 primeiras subcategorias em links pequenos.
- **Mobile:** categoria sem filhas = link; com filhas = `<details>` com "Ver tudo em X", as subcategorias e as sub-subcategorias indentadas.
- **Página de categoria:** lista os produtos de **todo o ramo** (`categoria_ids`). Por baixo do título aparecem os botões das filhas; numa categoria sem filhas, os das irmãs, com a actual marcada (`aria-current`). As facetas (marca, preço) respeitam o ramo.
- **Imagem da categoria:** `assets/img/categorias/<slug>.webp` (sem ficheiro → imagem de `acessorios`). Não há upload no painel: ao criar uma categoria de topo nova, acrescentar a imagem com esse nome.
- Classes Tailwind novas em views exigem `npm run css` (o `assets/css/app.css` vai no Git).

## 5. Verificar (depois de mexer nesta área)

Local (XAMPP, admin `admin@gmail.com` / `123456`):

1. `/admin/categorias`: árvore indentada, níveis com etiqueta, produtos "directos / ramo", "+ subcategoria" só até ao nível 2.
2. Criar uma subcategoria com `?pai=ID`; tentar: ciclo (mãe = descendente), 4.º nível, slug repetido, apagar uma categoria com filhas ou produtos → todos devem voltar com mensagem de erro.
3. "Mover produtos" de uma categoria para outra e voltar: as contagens batem certo.
4. Loja: a barra mostra a seta nas categorias com filhas; ao passar o rato ou focar, o painel em colunas; `/categoria/<topo>` soma os produtos das filhas; os botões de subcategorias e o breadcrumb Início › Produtos › Pai › Filha; o breadcrumb do produto com o caminho completo; `/categorias` e o menu mobile com acordeão.
5. Desactivar a categoria-mãe → o ramo desaparece do menu e `/categoria/<filha>` dá 404; reactivar.
6. `/admin/produtos?categoria=<topo>` inclui os produtos das subcategorias; o `<select>` do form de produto aparece indentado (`— Sub`, `— — Sub-sub`).
7. Sem avisos PHP nas páginas (procurar `Warning|Deprecated|Fatal` no HTML).

Para capturas sem browser aberto: `chrome --headless=new --screenshot` sobre o HTML guardado com `<base href="http://localhost/">`. Para ver o mega-menu aberto, injectar `<style>[class~="group/mega"]>div{visibility:visible!important;opacity:1!important}</style>`.
