---
name: analise-dados
description: Painel "Análise de Dados" do backoffice DIOTEKNO — gráficos ao estilo shadcn/ui charts (área, barras, donut, mapa de calor, faíscas) feitos em JS puro (assets/js/graficos.js), com paleta validada, comparação com o período anterior e vista em tabela. Usar para acrescentar/alterar gráficos, indicadores (KPIs) ou novas análises tipo Power BI no painel admin, ou quando pedirem "gráficos", "dashboard", "análise", "relatório visual".
---

# Análise de Dados (backoffice)

Página `/admin/analise` (menu **Geral → Análise de Dados**, permissão `relatorios`: super e gestor).
O relatório em tabela continua em `/admin/relatorios`; a Análise é a vista visual.

## Peças

| Ficheiro | Papel |
|---|---|
| `app/Controllers/Admin/AnaliseController.php` | consultas; herda `periodo()` de `RelatoriosController` |
| `app/Views/admin/analise.php` | filtro, KPIs, cartões de gráfico (`$cartao(...)`) |
| `assets/js/graficos.js` | motor de gráficos sem dependências (carregado só nesta página) |
| `src/app.css` (fim) | tokens `--serie-N`, `--anterior`, `--seq-N` + classes `.grafico-*` |

Sem React, sem CDN, sem Chart.js: o projecto é PHP + JS vanilla. Os charts do shadcn são
React/Recharts, por isso o **aspecto** é reproduzido em `graficos.js` (gradiente na área, curva
monótona, barras com 4px arredondados na ponta, donut com total ao centro, tooltip em cartão
com indicador de cor, separadores com totais no cabeçalho).

## Regras de dados

- Vendas = `pedidos.pago_em` no período **e** `estado NOT IN ('cancelado','reembolsado')` (`FILTRO_PAGO`).
- Funil e conversão usam `criado_em` (pedidos criados no período).
- Sempre comparar com o **período anterior de igual duração** (`$antDe`–`$antAte`), alinhado dia a dia.
- Períodos > 92 dias agrupam por mês (`$mensal`); séries preenchem os dias sem vendas com 0.
- Categorias/métodos: no máximo 6–7 fatias; o resto vai para **"Outros"** (`agruparOutros`), nunca uma cor gerada.

## Acrescentar um gráfico

1. No controlador, método privado que devolve linhas prontas:
   - `area`/`barras`: `[['rotulo' => '05 nov', 'chaveA' => 123, ...], ...]`
   - `barras-h`/`donut`: `[['rotulo' => 'Luanda', 'valor' => 123, 'pedidos' => 4], ...]`
   - `calor`: `['colunas' => [...], 'linhas' => [['rotulo' => 'Seg', 'valores' => [...]]]]`
2. Na view, um cartão:
   ```php
   <?= $cartao('Título', 'Descrição curta', [
       'tipo' => 'barras', 'formato' => 'kz', 'altura' => 260, 'empilhado' => true,
       'series' => [['chave' => 'novos', 'nome' => 'Novos', 'cor' => 'serie-1'], ...],
       'dados' => $linhas,
   ], '<span class="font-semibold">Conclusão</span> <span class="text-suave">em texto.</span>', '', [['serie-1','Novos'], ...]) ?>
   ```
   Opções: `formato` `kz|num|pct` · `extras` (campos a mais no tooltip/tabela) · `nomeValor` ·
   `cor`/`cores` · `pctDoPrimeiro` (funil) · `centro` (donut) · `larguraMax` · `tracejado` (série).
3. O rodapé diz **a conclusão** (ex.: "Sáb é o dia mais forte (27%)"), não repete o título.

## Regras visuais (da skill dataviz — não negociáveis)

- Cores por **papel**, em ordem fixa: `serie-1` azul, `serie-2` laranja, `serie-3` aqua, `serie-4` amarelo,
  `serie-5` magenta, `serie-6` violeta. "Outros" = `grafico-outros`; período anterior = `anterior` (cinza, tracejado).
  Cancelados/perdas = `grafico-perda` (sempre com rótulo).
- Paleta validada com `validate_palette.js` contra `#ffffff` e `#141a2e` (superfícies do site). No claro, aqua/amarelo/magenta
  ficam < 3:1 → **todo o cartão tem "Ver tabela"** (botão `data-grafico-tabela`, já incluído em `$cartao`). Não remover.
  Se mudar cores: correr `node <dataviz>/scripts/validate_palette.js "<hex,...>" --mode light --surface "#ffffff"` e `--mode dark --surface "#141a2e"`.
- Um só eixo Y (nunca dois). Duas medidas de escala diferente → separadores (como Receita/Pedidos) ou dois gráficos.
- Legenda sempre que há ≥ 2 séries; com 1 série, o título basta.
- Texto nunca na cor da série (valores e rótulos em `tinta`/`suave`; a cor vai no indicador ao lado).
- Mapa de calor: rampa sequencial de um só tom (`--seq-0..5`), escuro e claro definidos separadamente.
- KPIs: variação com seta + %, cor = direcção × "subir é bom"; valores em `kpi-valor` (encolhe com o cartão).

## Checklist

1. `php -l` nos PHP alterados; `node --check assets/js/graficos.js`.
2. Classes novas → `npm run css` na pasta do projecto; aumentar `VERSAO_ASSETS`.
3. **Ver o resultado** (a validação de cor não vê sobreposições): BD temporária com pedidos fictícios →
   `DB_NOME=<temp> php database/migrar.php`, inserir pedidos, `php -S 127.0.0.1:8099 servidor-dev.php`,
   login `admin@gmail.com`, guardar o HTML com `<base href="http://127.0.0.1:8099/">` e capturar com
   `msedge --headless=new --screenshot` em 1440px (claro e escuro) e num iframe de 375px (telemóvel —
   o headless não desce abaixo de ~500px de janela). Apagar a BD temporária no fim.
4. Confirmar: nenhum valor sai do cartão, eixos sem colisões, "Sem dados no período." quando vazio.
