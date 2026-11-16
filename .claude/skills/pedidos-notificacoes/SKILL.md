---
name: pedidos-notificacoes
description: Fluxo completo do pedido da loja DIOTEKNO (checkout → pagamento → preparação → envio → entrega/cancelamento), as notificações por email de cada passo (cliente e equipa da loja), o envio real por SMTP do Gmail e o login/criação de conta com o Google. Usar ao mexer em estados de pedidos, pagamentos, emails transaccionais, SMTP/Gmail, "o email não chega", "Entrar com o Google", ou para testar o fluxo de compra de ponta a ponta.
---

# Pedidos, notificações por email e login com o Google

Fora do âmbito: **facturação**. A factura certificada (AGT) é emitida fora do sistema; não integrar API de facturação.

## 1. Peças

| Peça | Ficheiro |
|---|---|
| Envio de email (drivers `smtp` / `mail` / `log`, multipart texto+HTML, registo de falhas) | [core/Email.php](../../../core/Email.php) |
| Cliente SMTP sem bibliotecas (STARTTLS 587 / SSL 465, AUTH LOGIN) | [core/Smtp.php](../../../core/Smtp.php) |
| Avisos do pedido: `Notificacoes::cliente()`, `::loja()`, `::canceladoPorFaltaDePagamento()` | [app/Servicos/Notificacoes.php](../../../app/Servicos/Notificacoes.php) |
| Criação do pedido (reserva stock, email "Recebemos") | [app/Servicos/Checkout.php](../../../app/Servicos/Checkout.php) `criarPedido()` |
| Pagamentos (confirmar, expirar, webhooks) | [app/Servicos/Pagamentos.php](../../../app/Servicos/Pagamentos.php), [PagamentoController](../../../app/Controllers/PagamentoController.php) |
| Acções do cliente (comprovativo, repetir pagamento, cancelar) | [app/Controllers/PedidoController.php](../../../app/Controllers/PedidoController.php) |
| Acções da loja (confirmar/rejeitar, mudar estado, cancelar) | [app/Controllers/Admin/PedidosController.php](../../../app/Controllers/Admin/PedidosController.php) |
| Login Google (OAuth 2.0 / OIDC, sem bibliotecas) | [core/Google.php](../../../core/Google.php), `AuthController::google()` / `googleRetorno()` |
| Teste de email | `php cron/testar-email.php destino@exemplo.com` |
| Expiração automática (cron a cada 10 min; também corre ao abrir checkout/pedidos) | `cron/expirar-pagamentos.php` |

## 2. Máquina de estados do pedido

```
pendente ──pagamento confirmado──▶ pago ──▶ em_preparacao ──▶ enviado ──▶ entregue
   │                                 │            │
   ├─ cliente cancela ─▶ cancelado   └─ loja cancela ─▶ reembolsado (stock devolvido; reembolso feito fora)
   ├─ loja cancela ────▶ cancelado
   └─ prazo termina / 72 h sem pagar ─▶ cancelado (stock, cupão e pontos devolvidos)
```

- Transições da loja: `PedidosController::TRANSICOES` (só avança um passo). `enviado` fora de Luanda exige código de rastreio.
- Cada mudança grava uma linha em `pedido_historico` (é o que o cliente vê na timeline de `/conta/pedidos/{numero}`).
- Cancelar um pedido `pendente` passa **sempre** por `Pedido::cancelarComStock()` (devolve stock, cupão, pontos e expira pagamentos). Só devolve `true` se cancelou agora: enviar emails **só** nesse caso, para não duplicar.
- `Pagamentos::confirmar()` é idempotente (webhook repetido, clique duplo): envia emails só quando devolve `true`.

## 3. Mapa de emails (manter actualizado ao acrescentar passos)

| Momento | Cliente | Loja (`EMAIL_NOTIFICACOES`) | Onde |
|---|---|---|---|
| Conta criada (formulário ou Google) | Boas-vindas | — | `AuthController::emailBoasVindas()` |
| Recuperar senha | Link (60 min, uso único) | — | `AuthController::enviarRecuperacao()` |
| Pedido criado | "Recebemos o seu pedido" + link para pagar | "Novo pedido" + total + método | `Checkout::criarPedido()` |
| Comprovativo de transferência enviado | — | "Comprovativo para verificar" | `PedidoController::comprovativo()` |
| Comprovativo rejeitado | "Precisamos de um novo comprovativo" (+24 h de reserva) | — | `Admin\PedidosController::rejeitarComprovativo()` |
| Pagamento confirmado (loja ou webhook) | "Pagamento confirmado" + pontos ganhos | "Pagamento confirmado — preparar" | `Pagamentos::confirmar()` |
| Pagamento recusado pelo operador (webhook) | "Pagamento não concluído" + link para pagar | — | `PagamentoController::notificacao()` |
| Em preparação | — (o email de pagamento já diz "estamos a preparar") | — | `mudarEstado()` |
| Enviado / pronto a levantar | "A caminho" / "Pronto para levantar" + rastreio | — | `mudarEstado()` |
| Entregue / levantado | "Encomenda entregue" | — | `mudarEstado()` |
| Cancelado pelo cliente | "Pedido cancelado" | "Cancelado pelo cliente" | `PedidoController::cancelar()` |
| Cancelado pela loja | Motivo (+ aviso de devolução se já pago) | — (foi a loja) | `Admin\PedidosController::cancelar()` |
| Cancelado por falta de pagamento | "Cancelado por falta de pagamento" | — | `Pagamentos::expirarPendentes()` |

**Regras para um novo email**
1. Cliente → `Notificacoes::cliente($clienteId, $assunto, $html)` (já põe "Olá <primeiro nome>,"). Loja → `Notificacoes::loja()` (prefixo `[Loja]`, inclui `Notificacoes::linkAdmin($numero)`).
2. Enviar **depois** do `commit()` da transacção, nunca dentro.
3. Escapar tudo o que vem do utilizador com `e()`; valores com `kz()`; assunto sem HTML (`strip_tags(kz(...))`).
4. Terminar com um link de acção (`/conta/pedidos/{numero}`, `#pagamento` quando é para pagar).
5. `Email::enviar()` nunca lança excepção: devolve `false` e escreve em `storage/logs/emails-erros.log`. Não bloquear o fluxo por causa de um email.
6. Acrescentar a linha à tabela acima.

## 4. Email por SMTP do Gmail

Variáveis (`.env` no XAMPP; *Environment Variables* no Coolify; ver `.env.example`):

```
SMTP_HOST=smtp.gmail.com
SMTP_PORTA=587                 # 465 = SSL directo (SMTP_SEGURANCA automático)
SMTP_UTILIZADOR=conta@gmail.com
SMTP_SENHA=abcd efgh ijkl mnop # senha de app; os espaços são removidos
EMAIL_LOJA=geral@diotekno.ao   # Reply-To
# EMAIL_REMETENTE=             # padrão = SMTP_UTILIZADOR (o Gmail reescreve um From que não seja da conta ou alias verificado)
# EMAIL_NOTIFICACOES=          # padrão = EMAIL_LOJA; "nao" desliga os avisos internos
URL_SITE=https://diotekno.ao   # links correctos nos emails enviados pelo cron
```

- Com `SMTP_HOST` definido o driver passa a `smtp` sozinho; sem ele fica `log` (ou `mail` em prod fora do Docker). O contentor Docker **não tem sendmail**, por isso em produção é obrigatório SMTP.
- Senha de app: Conta Google → Segurança → Verificação em 2 passos (ligar) → *Senhas de app*. A senha normal dá `535 5.7.8`.
- Limites: Gmail ~500 destinatários/dia, Workspace ~2000. Newsletters grandes → outro SMTP (Brevo, SES…) nas mesmas variáveis.
- Para não cair no spam com domínio próprio: SPF, DKIM e DMARC no DNS (Workspace: gerar a chave DKIM na consola de administração).
- O envio é síncrono (~1–2 s por email). Se ficar lento, passar os emails para uma fila processada pelo cron.

**Diagnóstico** (`php cron/testar-email.php destino@...`; no Coolify: terminal do contentor):

| Erro | Causa |
|---|---|
| `[senha] → 535 5.7.8 … BadCredentials` | senha de app errada ou verificação em 2 passos desligada |
| `Sem ligação a smtp.gmail.com:587` | porta de saída bloqueada pelo alojamento → testar 465 |
| `Não foi possível activar TLS` | CA do sistema em falta (`ca-certificates`) |
| `mail() devolveu false` | driver `mail` sem sendmail → configurar SMTP |
| Chega mas vai para o spam | SPF/DKIM/DMARC, ou From diferente da conta SMTP |

## 5. Entrar / criar conta com o Google

- Variáveis: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` (sem elas o botão não aparece), `GOOGLE_REDIRECT_URI` opcional.
- Google Cloud Console → *Credenciais* → ID do cliente OAuth **Aplicação Web** → URI de redireccionamento `https://<domínio>/entrar/google/retorno` (e o de `localhost` para testes). Ecrã de consentimento publicado, âmbitos `openid email profile`.
- Fluxo: `/entrar/google` guarda `state` + `nonce` na sessão (10 min, uso único) → Google → `/entrar/google/retorno` troca o `code` no servidor e valida `iss`, `aud`, `exp`, `nonce`.
- Ligação de contas: procura por `google_id`; se não existir e o email Google estiver **verificado**, liga à conta com o mesmo email; senão cria conta nova (`senha_definida = 0`, pode definir senha em *Os meus dados* sem a actual) e envia boas-vindas. O carrinho do visitante junta-se à conta.
- **Nunca** pôr o Client ID/Secret reais no `.env.example` nem em nenhum ficheiro do Git: o GitHub bloqueia o push (push protection). Só no `.env` (ignorado) ou no Coolify. Se escapar num commit ainda não enviado, juntar/refazer os commits locais antes do push.

## 6. Verificar de ponta a ponta (obrigatório depois de mexer no fluxo)

Local (XAMPP, sem `SMTP_HOST` → emails em `storage/logs/emails.log`), contas demo `kay.edson@gmail.com` / `admin@gmail.com`, senha `123456`:

```
bash .claude/skills/pedidos-notificacoes/e2e-pedido.sh http://localhost/loja-online
```

O script entra como cliente, compra (levantamento + transferência), envia comprovativo, entra no painel, confirma o pagamento, avança `em_preparacao → enviado → entregue`, faz um segundo pedido e cancela-o, e lista o histórico e os emails gerados. Esperado: **11 emails**, na ordem da tabela da secção 3, sem novas linhas em `storage/logs/emails-erros.log`.

Também verificar à mão quando relevante:
- Cancelamento automático: pôr `expira_em` no passado num pedido de teste e correr `php cron/expirar-pagamentos.php` → email "cancelado por falta de pagamento".
- SMTP real: `php cron/testar-email.php <email pessoal>` e confirmar a recepção, os acentos e os links.
- Google: botão em `/entrar` e `/registar`; conta nova, conta existente com o mesmo email e cancelar no ecrã do Google (mensagem amigável).

Cria pedidos reais na BD local: usar só em desenvolvimento.
