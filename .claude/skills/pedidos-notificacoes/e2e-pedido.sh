#!/bin/bash
# Teste ponta a ponta do fluxo de pedido + emails (só desenvolvimento: cria pedidos reais na BD).
# Uso: bash .claude/skills/pedidos-notificacoes/e2e-pedido.sh [URL_BASE]
set -u
B=${1:-http://localhost/loja-online}
RAIZ=$(cd "$(dirname "$0")/../../.." && pwd)
PHP=${PHP:-$(command -v php || echo /c/xampp/php/php.exe)}
TMP=$(mktemp -d); C=$TMP/cli.txt; A=$TMP/adm.txt
LOG=$RAIZ/storage/logs/emails.log; ERR=$RAIZ/storage/logs/emails-erros.log
csrf() { grep -o 'name="_csrf" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//;s/"//'; }
sql() { (cd "$RAIZ" && "$PHP" -r '$_SERVER["SCRIPT_NAME"]="/index.php";$_SERVER["HTTP_HOST"]="localhost";require "config/config.php";require "config/db.php";require "core/autoload.php";'"$1"); }
ANTES=$(grep -c '^\[' "$LOG" 2>/dev/null); ANTES=${ANTES:-0}
ERR_ANTES=$(grep -c '' "$ERR" 2>/dev/null); ERR_ANTES=${ERR_ANTES:-0}

T=$(curl -s -c $C -b $C $B/entrar | csrf)
curl -s -o /dev/null -c $C -b $C -d "_csrf=$T&email=kay.edson@gmail.com&senha=123456" $B/entrar
PROD=$(sql 'echo Core\Database::ligacao()->query("SELECT id FROM produtos WHERE estado=\"activo\" AND stock>5 ORDER BY id LIMIT 1")->fetchColumn();')

encomendar() {
  T=$(curl -s -c $C -b $C $B/carrinho | csrf)
  curl -s -o /dev/null -c $C -b $C -d "_csrf=$T&produto_id=$PROD&quantidade=1" $B/carrinho/adicionar
  P=$(curl -s -c $C -b $C $B/checkout); T=$(echo "$P" | csrf)
  K=$(echo "$P" | grep -o 'name="chave" value="[a-f0-9]*"' | sed 's/.*value="//;s/"//')
  curl -s -o /dev/null -w '%{redirect_url}' -c $C -b $C \
    -d "_csrf=$T&chave=$K&metodo_entrega=levantamento&metodo_pagamento=transferencia&aceita_termos=1" \
    $B/checkout/confirmar | grep -o 'DTK-[0-9-]*'
}

N1=$(encomendar); echo "Pedido completo:  ${N1:-FALHOU}"
T=$(curl -s -c $C -b $C $B/conta/pedidos/$N1 | csrf)
printf '%%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%%%EOF\n' > $TMP/comp.pdf
# Caminho relativo: no Git Bash (Windows) "@/tmp/...;type=..." é convertido como lista de caminhos e o curl falha
(cd $TMP && curl -s -o /dev/null -c $C -b $C -F "_csrf=$T" -F "comprovativo=@comp.pdf;type=application/pdf" $B/conta/pedidos/$N1/comprovativo)

T=$(curl -s -c $A -b $A $B/admin/entrar | csrf)
curl -s -o /dev/null -c $A -b $A -d "_csrf=$T&email=admin@gmail.com&senha=123456" $B/admin/entrar
adm() { T=$(curl -s -c $A -b $A $B/admin/pedidos/$N1 | csrf); curl -s -o /dev/null -c $A -b $A -d "_csrf=$T&$2" $B/admin/pedidos/$N1/$1; }
adm confirmar-pagamento "verificado=1"
adm estado "estado=em_preparacao"
adm estado "estado=enviado"
adm estado "estado=entregue"

N2=$(encomendar); echo "Pedido cancelado: ${N2:-FALHOU}"
T=$(curl -s -c $C -b $C $B/conta/pedidos/$N2 | csrf)
curl -s -o /dev/null -c $C -b $C -d "_csrf=$T" $B/conta/pedidos/$N2/cancelar

echo "--- Histórico:"
sql 'foreach (Core\Database::ligacao()->query("SELECT p.numero, h.estado, h.nota FROM pedido_historico h JOIN pedidos p ON p.id = h.pedido_id WHERE p.numero IN (\"'"$N1"'\", \"'"$N2"'\") ORDER BY h.id") as $r) echo "  $r[numero] | $r[estado] | $r[nota]\n";'
echo "--- Emails gerados (esperado: 11):"
awk -v a="$ANTES" '/^\[/{n++} n>a && /^(\[|Assunto)/' "$LOG" | paste - - | sed 's/^\[[^]]*\] /  /'
NOVOS=$(( $(grep -c '' "$ERR" 2>/dev/null || echo 0) - ERR_ANTES ))
[ "$NOVOS" -gt 0 ] && { echo "--- ERROS de envio:"; tail -n $NOVOS "$ERR"; }
rm -rf $TMP
