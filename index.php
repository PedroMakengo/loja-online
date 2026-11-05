<?php

// =====================================================================
// index.php — Front controller da Loja DIOTEKNO.
// O projecto abre directamente na raiz do domínio (sem pasta /public),
// mesmo padrão dos projectos Adapec / Centro de Formação.
// =====================================================================

require __DIR__ . '/config/config.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/core/autoload.php';

use Core\Router;
use App\Controllers\HomeController;
use App\Controllers\CatalogoController;
use App\Controllers\ProdutoController;
use App\Controllers\PesquisaController;
use App\Controllers\CarrinhoController;
use App\Controllers\PaginaController;
use App\Controllers\ContactoController;
use App\Controllers\SitemapController;
use App\Controllers\AuthController;
use App\Controllers\ContaController;
use App\Controllers\FavoritoController;
use App\Controllers\CheckoutController;
use App\Controllers\PedidoController;
use App\Controllers\PagamentoController;
use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\PainelController;
use App\Controllers\Admin\PedidosController as AdminPedidos;
use App\Controllers\Admin\ProdutosController as AdminProdutos;
use App\Controllers\Admin\ClientesController as AdminClientes;
use App\Controllers\Admin\CrudController;
use App\Controllers\Admin\RelatoriosController;
use App\Controllers\Admin\AnaliseController;
use App\Controllers\Admin\MensagensController;
use App\Controllers\Admin\AdminsController;
use App\Controllers\Admin\AvaliacoesController as AdminAvaliacoes;
use App\Controllers\Admin\MarketingController as AdminMarketing;
use App\Controllers\AvaliacaoController;
use App\Controllers\ComparadorController;
use App\Controllers\MarketingController;
use App\Controllers\BlogController;
use App\Controllers\Admin\BlogController as AdminBlog;

$router = new Router();

// ----- Loja -----------------------------------------------------------
$router->get('/', [HomeController::class, 'index']);
$router->get('/produtos', [CatalogoController::class, 'produtos']);
$router->get('/categorias', [CatalogoController::class, 'categorias']);
$router->get('/categoria/{slug}', [CatalogoController::class, 'categoria']);
$router->get('/marca/{slug}', [CatalogoController::class, 'marca']);
$router->get('/promocoes', [CatalogoController::class, 'promocoes']);
$router->get('/novidades', [CatalogoController::class, 'novidades']);
$router->get('/pesquisa', [CatalogoController::class, 'pesquisa']);
$router->get('/produto/{slug}', [ProdutoController::class, 'mostrar']);
$router->get('/marcas', [PaginaController::class, 'marcas']);

// ----- Pesquisa inteligente (AJAX) -------------------------------------
$router->get('/api/pesquisa', [PesquisaController::class, 'sugestoes']);

// ----- Carrinho (página + AJAX) -----------------------------------------
$router->get('/carrinho', [CarrinhoController::class, 'index']);
$router->get('/carrinho/mini', [CarrinhoController::class, 'mini']);
$router->post('/carrinho/adicionar', [CarrinhoController::class, 'adicionar']);
$router->post('/carrinho/actualizar', [CarrinhoController::class, 'actualizar']);
$router->post('/carrinho/remover', [CarrinhoController::class, 'remover']);
$router->post('/carrinho/cupao', [CarrinhoController::class, 'aplicarCupao']);
$router->post('/carrinho/cupao/remover', [CarrinhoController::class, 'removerCupao']);

// ----- Institucional ------------------------------------------------------
$router->get('/sobre', [PaginaController::class, 'sobre']);
$router->get('/faq', [PaginaController::class, 'faq']);
$router->get('/entregas', [PaginaController::class, 'entregas']);
$router->get('/privacidade', [PaginaController::class, 'privacidade']);
$router->get('/termos', [PaginaController::class, 'termos']);
$router->get('/contactos', [ContactoController::class, 'index']);
$router->post('/contactos/enviar', [ContactoController::class, 'enviar']);
$router->post('/newsletter', [ContactoController::class, 'newsletter']);

// ----- Autenticação (Fase 2) ---------------------------------------------
$router->get('/entrar', [AuthController::class, 'entrar']);
$router->post('/entrar', [AuthController::class, 'autenticar']);
$router->get('/registar', [AuthController::class, 'registar']);
$router->post('/registar', [AuthController::class, 'criarConta']);
$router->post('/sair', [AuthController::class, 'sair']);
$router->get('/recuperar-senha', [AuthController::class, 'recuperar']);
$router->post('/recuperar-senha', [AuthController::class, 'enviarRecuperacao']);
$router->get('/redefinir-senha/{token}', [AuthController::class, 'redefinir']);
$router->post('/redefinir-senha/{token}', [AuthController::class, 'guardarNovaSenha']);

// ----- A minha conta (Fase 2) ---------------------------------------------
$router->get('/conta', [ContaController::class, 'painel']);
$router->get('/conta/pedidos', [ContaController::class, 'pedidos']);
$router->get('/conta/dados', [ContaController::class, 'dados']);
$router->post('/conta/dados', [ContaController::class, 'guardarDados']);
$router->post('/conta/senha', [ContaController::class, 'alterarSenha']);
$router->get('/conta/enderecos', [ContaController::class, 'enderecos']);
$router->post('/conta/enderecos/guardar', [ContaController::class, 'guardarEndereco']);
$router->post('/conta/enderecos/{id}/remover', [ContaController::class, 'removerEndereco']);
$router->post('/conta/enderecos/{id}/principal', [ContaController::class, 'enderecoPrincipal']);
$router->get('/favoritos', [ContaController::class, 'favoritos']);
$router->post('/favoritos/alternar', [FavoritoController::class, 'alternar']);

// ----- Checkout e pagamentos (Fase 3) --------------------------------------
$router->get('/checkout', [CheckoutController::class, 'index']);
$router->post('/checkout/calcular', [CheckoutController::class, 'calcular']);
$router->post('/checkout/confirmar', [CheckoutController::class, 'confirmar']);
$router->get('/conta/pedidos/{numero}', [PedidoController::class, 'mostrar']);
$router->get('/conta/pedidos/{numero}/estado', [PedidoController::class, 'estado']);
$router->post('/conta/pedidos/{numero}/comprovativo', [PedidoController::class, 'comprovativo']);
$router->post('/conta/pedidos/{numero}/pagar', [PedidoController::class, 'repetirPagamento']);
$router->post('/conta/pedidos/{numero}/cancelar', [PedidoController::class, 'cancelar']);
$router->post('/conta/pedidos/{numero}/simular', [PedidoController::class, 'simular']);
// Webhook dos operadores: sem CSRF, validado por assinatura HMAC
$router->post('/pagamentos/notificacao', [PagamentoController::class, 'notificacao'], false);

// ----- Avaliações, comparador e pontos (Fase 5) -----------------------------
$router->post('/avaliacoes', [AvaliacaoController::class, 'criar']);
$router->get('/produto/{slug}/avaliacoes', [AvaliacaoController::class, 'mais']);
$router->get('/comparar', [ComparadorController::class, 'index']);
$router->post('/comparar/alternar', [ComparadorController::class, 'alternar']);
$router->post('/comparar/limpar', [ComparadorController::class, 'limpar']);
$router->get('/conta/pontos', [ContaController::class, 'pontos']);
$router->get('/admin/avaliacoes', [AdminAvaliacoes::class, 'index']);
$router->post('/admin/avaliacoes/{id}', [AdminAvaliacoes::class, 'moderar']);
$router->post('/admin/clientes/{id}/pontos', [AdminClientes::class, 'ajustarPontos']);

// ----- Carrinho abandonado e newsletter (Fase 6) ----------------------------
$router->get('/carrinho/recuperar/{token}', [MarketingController::class, 'recuperar']);
$router->get('/newsletter/sair/{email}/{assinatura}', [MarketingController::class, 'sairNewsletter']);
// Sem CSRF: o link é assinado e o Gmail faz o pedido "um clique" directamente (RFC 8058)
$router->post('/newsletter/sair/{email}/{assinatura}', [MarketingController::class, 'confirmarSairNewsletter'], false);
$router->get('/lembretes/sair/{id}/{assinatura}', [MarketingController::class, 'sairLembretes']);
$router->post('/lembretes/sair/{id}/{assinatura}', [MarketingController::class, 'confirmarSairLembretes'], false);

$router->get('/admin/marketing/carrinhos', [AdminMarketing::class, 'carrinhos']);
$router->post('/admin/marketing/carrinhos/processar', [AdminMarketing::class, 'processarLembretes']);
$router->get('/admin/newsletter', [AdminMarketing::class, 'newsletter']);
$router->get('/admin/newsletter/nova', [AdminMarketing::class, 'campanha']);
$router->get('/admin/newsletter/exportar', [AdminMarketing::class, 'exportar']);
$router->post('/admin/newsletter/guardar', [AdminMarketing::class, 'guardar']);
$router->post('/admin/newsletter/processar', [AdminMarketing::class, 'processarFila']);
$router->get('/admin/newsletter/{id}', [AdminMarketing::class, 'editar']);
$router->get('/admin/newsletter/{id}/pre-visualizar', [AdminMarketing::class, 'previsualizar']);
$router->post('/admin/newsletter/{id}/teste', [AdminMarketing::class, 'teste']);
$router->post('/admin/newsletter/{id}/enviar', [AdminMarketing::class, 'enviar']);
$router->post('/admin/newsletter/{id}/duplicar', [AdminMarketing::class, 'duplicar']);

// ----- Blog (Fase 7) ----------------------------------------------------------
$router->get('/blog', [BlogController::class, 'index']);
$router->get('/blog/feed.xml', [BlogController::class, 'feed']);
$router->get('/blog/categoria/{slug}', [BlogController::class, 'categoria']);
$router->get('/blog/{slug}', [BlogController::class, 'mostrar']);
$router->get('/admin/blog', [AdminBlog::class, 'index']);
$router->get('/admin/blog/novo', [AdminBlog::class, 'novo']);
$router->post('/admin/blog/guardar', [AdminBlog::class, 'guardar']);
$router->get('/admin/blog/{id}', [AdminBlog::class, 'editar']);
$router->post('/admin/blog/{id}/apagar', [AdminBlog::class, 'apagar']);

// ----- Painel administrativo (Fase 4) ----------------------------------------
$router->get('/admin/entrar', [AdminAuthController::class, 'entrar']);
$router->post('/admin/entrar', [AdminAuthController::class, 'autenticar']);
$router->post('/admin/sair', [AdminAuthController::class, 'sair']);
$router->get('/admin/perfil', [AdminAuthController::class, 'perfil']);
$router->post('/admin/perfil/senha', [AdminAuthController::class, 'guardarSenha']);
$router->get('/admin', [PainelController::class, 'index']);

$router->get('/admin/pedidos', [AdminPedidos::class, 'index']);
$router->get('/admin/pedidos/{numero}', [AdminPedidos::class, 'mostrar']);
$router->post('/admin/pedidos/{numero}/confirmar-pagamento', [AdminPedidos::class, 'confirmarPagamento']);
$router->post('/admin/pedidos/{numero}/rejeitar-comprovativo', [AdminPedidos::class, 'rejeitarComprovativo']);
$router->post('/admin/pedidos/{numero}/estado', [AdminPedidos::class, 'mudarEstado']);
$router->post('/admin/pedidos/{numero}/cancelar', [AdminPedidos::class, 'cancelar']);
$router->post('/admin/pedidos/{numero}/nota', [AdminPedidos::class, 'nota']);
$router->get('/admin/comprovativos/{ficheiro}', [AdminPedidos::class, 'comprovativo']);

$router->get('/admin/produtos', [AdminProdutos::class, 'index']);
$router->get('/admin/produtos/novo', [AdminProdutos::class, 'novo']);
$router->post('/admin/produtos/guardar', [AdminProdutos::class, 'guardar']);
$router->get('/admin/produtos/{id}', [AdminProdutos::class, 'editar']);
$router->post('/admin/produtos/{id}/stock', [AdminProdutos::class, 'stock']);

$router->get('/admin/clientes', [AdminClientes::class, 'index']);
$router->get('/admin/clientes/{id}', [AdminClientes::class, 'mostrar']);
$router->post('/admin/clientes/{id}/estado', [AdminClientes::class, 'alternarEstado']);

$router->get('/admin/relatorios', [RelatoriosController::class, 'index']);
$router->get('/admin/analise', [AnaliseController::class, 'index']);
$router->get('/admin/relatorios/csv', [RelatoriosController::class, 'csv']);
$router->get('/admin/mensagens', [MensagensController::class, 'index']);
$router->post('/admin/mensagens/{id}/lida', [MensagensController::class, 'lida']);
$router->get('/admin/administradores', [AdminsController::class, 'index']);
$router->post('/admin/administradores', [AdminsController::class, 'criar']);
$router->post('/admin/administradores/{id}/alternar', [AdminsController::class, 'alternar']);

// Motor genérico (categorias, marcas, cupões, banners, zonas de entrega)
$router->get('/admin/c/{entidade}', [CrudController::class, 'index']);
$router->get('/admin/c/{entidade}/novo', [CrudController::class, 'novo']);
$router->post('/admin/c/{entidade}/guardar', [CrudController::class, 'guardar']);
$router->get('/admin/c/{entidade}/{id}', [CrudController::class, 'editar']);
$router->post('/admin/c/{entidade}/{id}/apagar', [CrudController::class, 'apagar']);

// ----- Próximas fases (páginas provisórias) -------------------------------

// ----- SEO -----------------------------------------------------------------
$router->get('/sitemap.xml', [SitemapController::class, 'index']);
$router->get('/robots.txt', [SitemapController::class, 'robots']);

$router->despachar($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
