<?php
// =====================================================================
// config/crud.php — Entidades simples geridas pelo motor genérico
// (app/Controllers/Admin/CrudController.php).
//
// Tipos de campo: texto, textarea, numero, dinheiro, booleano, data,
// datahora, select (opcoes => [...] ou tabela => [tabela, coluna]),
// slug (gerado a partir de 'de' se ficar vazio), imagem (pasta).
// Opções: obrigatorio, unico, max, ajuda, lista (mostra na tabela).
// =====================================================================

$provincias = ['Bengo', 'Benguela', 'Bié', 'Cabinda', 'Cuando', 'Cuanza Norte', 'Cuanza Sul', 'Cubango', 'Cunene', 'Huambo', 'Huíla',
               'Icolo e Bengo', 'Luanda', 'Lunda Norte', 'Lunda Sul', 'Malanje', 'Moxico', 'Moxico Leste', 'Namibe', 'Uíge', 'Zaire'];

return [
    'categorias' => [
        'titulo' => 'Categorias', 'singular' => 'categoria', 'area' => 'catalogo', 'ordem' => 'ordem, nome',
        'pesquisa' => ['nome'],
        'contagem' => ["SELECT COUNT(*) FROM produtos WHERE categoria_id = ?", 'produtos'],
        'campos' => [
            'nome'           => ['tipo' => 'texto', 'rotulo' => 'Nome', 'obrigatorio' => true, 'max' => 120, 'lista' => true],
            'slug'           => ['tipo' => 'slug', 'rotulo' => 'Endereço (URL)', 'de' => 'nome', 'unico' => true, 'ajuda' => 'Fica em /categoria/endereco. Gerado a partir do nome se ficar vazio.', 'lista' => true],
            'descricao'      => ['tipo' => 'textarea', 'rotulo' => 'Descrição curta', 'max' => 255],
            'ordem'          => ['tipo' => 'numero', 'rotulo' => 'Ordem no menu', 'lista' => true],
            'destaque'       => ['tipo' => 'booleano', 'rotulo' => 'Mostrar na página inicial', 'lista' => true],
            'activo'         => ['tipo' => 'booleano', 'rotulo' => 'Activa', 'padrao' => 1, 'lista' => true],
            'meta_titulo'    => ['tipo' => 'texto', 'rotulo' => 'Título para o Google', 'max' => 160, 'ajuda' => 'Opcional. Até 60 caracteres aparecem completos.'],
            'meta_descricao' => ['tipo' => 'textarea', 'rotulo' => 'Descrição para o Google', 'max' => 255],
        ],
    ],

    'marcas' => [
        'titulo' => 'Marcas', 'singular' => 'marca', 'area' => 'catalogo', 'ordem' => 'nome',
        'pesquisa' => ['nome'],
        'contagem' => ["SELECT COUNT(*) FROM produtos WHERE marca_id = ?", 'produtos'],
        'campos' => [
            'nome'      => ['tipo' => 'texto', 'rotulo' => 'Nome', 'obrigatorio' => true, 'max' => 80, 'lista' => true],
            'slug'      => ['tipo' => 'slug', 'rotulo' => 'Endereço (URL)', 'de' => 'nome', 'unico' => true, 'lista' => true],
            'descricao' => ['tipo' => 'textarea', 'rotulo' => 'Descrição', 'max' => 255],
            'destaque'  => ['tipo' => 'booleano', 'rotulo' => 'Marca oficial na página inicial', 'lista' => true],
            'activo'    => ['tipo' => 'booleano', 'rotulo' => 'Activa', 'padrao' => 1, 'lista' => true],
        ],
    ],

    'cupoes' => [
        'titulo' => 'Cupões de desconto', 'singular' => 'cupão', 'area' => 'marketing', 'ordem' => 'activo DESC, criado_em DESC',
        'pesquisa' => ['codigo', 'descricao'],
        'campos' => [
            'codigo'          => ['tipo' => 'texto', 'rotulo' => 'Código', 'obrigatorio' => true, 'max' => 40, 'unico' => true, 'maiusculas' => true, 'lista' => true, 'ajuda' => 'O que o cliente escreve no carrinho. Sem espaços.'],
            'descricao'       => ['tipo' => 'texto', 'rotulo' => 'Descrição', 'max' => 160, 'lista' => true],
            'tipo'            => ['tipo' => 'select', 'rotulo' => 'Tipo de desconto', 'opcoes' => ['percentual' => 'Percentagem (%)', 'fixo' => 'Valor fixo (Kz)'], 'obrigatorio' => true, 'lista' => true],
            'valor'           => ['tipo' => 'dinheiro', 'rotulo' => 'Valor', 'obrigatorio' => true, 'lista' => true, 'ajuda' => 'Ex.: 10 para 10%, ou 5000 para 5 000 Kz.'],
            'minimo_compra'   => ['tipo' => 'dinheiro', 'rotulo' => 'Compra mínima (Kz)'],
            'utilizacoes_max' => ['tipo' => 'numero', 'rotulo' => 'Limite de utilizações', 'ajuda' => 'Vazio = sem limite.'],
            'utilizacoes'     => ['tipo' => 'numero', 'rotulo' => 'Já utilizado', 'so_leitura' => true, 'lista' => true],
            'inicio'          => ['tipo' => 'datahora', 'rotulo' => 'Válido a partir de'],
            'fim'             => ['tipo' => 'datahora', 'rotulo' => 'Válido até', 'lista' => true],
            'activo'          => ['tipo' => 'booleano', 'rotulo' => 'Activo', 'padrao' => 1, 'lista' => true],
        ],
    ],

    'banners' => [
        'titulo' => 'Banners da página inicial', 'singular' => 'banner', 'area' => 'marketing', 'ordem' => 'ordem',
        'pesquisa' => ['titulo'],
        'campos' => [
            'titulo'      => ['tipo' => 'texto', 'rotulo' => 'Título', 'obrigatorio' => true, 'max' => 140, 'lista' => true, 'ajuda' => 'Curto e directo: 3 a 6 palavras.'],
            'subtitulo'   => ['tipo' => 'textarea', 'rotulo' => 'Texto', 'max' => 255],
            'texto_botao' => ['tipo' => 'texto', 'rotulo' => 'Texto do botão', 'max' => 40],
            'link'        => ['tipo' => 'texto', 'rotulo' => 'Ligação do botão', 'max' => 255, 'ajuda' => 'Caminho interno, ex.: /categoria/gaming ou /produto/iphone-16-128gb'],
            'imagem'      => ['tipo' => 'imagem', 'rotulo' => 'Fotografia do banner', 'pasta' => 'banners/uploads', 'lista' => true, 'ajuda' => 'Fotografia real que ocupa todo o banner (JPG/WEBP, ideal 2400 × 900 px, até 4 MB). Deixe o lado esquerdo mais limpo: é onde fica o texto. Sem fotografia, o banner usa a cor de fundo e a imagem do produto.'],
            'produto_id'  => ['tipo' => 'select', 'rotulo' => 'Produto em destaque', 'tabela' => ['produtos', 'nome'], 'vazio' => 'Nenhum', 'ajuda' => 'Mostra o preço actual do produto na etiqueta (e a imagem do produto, se o banner não tiver fotografia).'],
            'tema'        => ['tipo' => 'select', 'rotulo' => 'Cor de fundo', 'opcoes' => ['grafite' => 'Noite', 'azul' => 'Azul DIOTEKNO', 'verde' => 'Verde', 'coral' => 'Coral'], 'lista' => true],
            'ordem'       => ['tipo' => 'numero', 'rotulo' => 'Ordem', 'lista' => true],
            'inicio'      => ['tipo' => 'datahora', 'rotulo' => 'Mostrar a partir de'],
            'fim'         => ['tipo' => 'datahora', 'rotulo' => 'Mostrar até', 'lista' => true],
            'activo'      => ['tipo' => 'booleano', 'rotulo' => 'Activo', 'padrao' => 1, 'lista' => true],
        ],
    ],

    'blog_categorias' => [
        'titulo' => 'Categorias do blog', 'singular' => 'categoria', 'area' => 'marketing', 'ordem' => 'ordem, nome',
        'pesquisa' => ['nome'],
        'contagem' => ["SELECT COUNT(*) FROM blog_posts WHERE categoria_id = ?", 'artigos'],
        'campos' => [
            'nome'      => ['tipo' => 'texto', 'rotulo' => 'Nome', 'obrigatorio' => true, 'max' => 80, 'lista' => true],
            'slug'      => ['tipo' => 'slug', 'rotulo' => 'Endereço (URL)', 'de' => 'nome', 'unico' => true, 'lista' => true],
            'descricao' => ['tipo' => 'texto', 'rotulo' => 'Descrição', 'max' => 255],
            'ordem'     => ['tipo' => 'numero', 'rotulo' => 'Ordem', 'lista' => true],
        ],
    ],

    'zonas_entrega' => [
        'titulo' => 'Zonas de entrega', 'singular' => 'zona', 'area' => 'marketing', 'ordem' => "provincia = 'Luanda' DESC, provincia, expressa",
        'pesquisa' => ['provincia', 'transportadora'],
        'campos' => [
            'provincia'      => ['tipo' => 'select', 'rotulo' => 'Província', 'opcoes' => array_combine($provincias, $provincias), 'obrigatorio' => true, 'lista' => true],
            'expressa'       => ['tipo' => 'booleano', 'rotulo' => 'Entrega expressa (no próprio dia)', 'lista' => true],
            'preco'          => ['tipo' => 'dinheiro', 'rotulo' => 'Preço (Kz)', 'obrigatorio' => true, 'lista' => true],
            'prazo_min'      => ['tipo' => 'numero', 'rotulo' => 'Prazo mínimo (dias úteis)', 'padrao' => 1, 'lista' => true],
            'prazo_max'      => ['tipo' => 'numero', 'rotulo' => 'Prazo máximo (dias úteis)', 'padrao' => 3, 'lista' => true],
            'transportadora' => ['tipo' => 'texto', 'rotulo' => 'Transportadora', 'max' => 80, 'lista' => true],
            'activo'         => ['tipo' => 'booleano', 'rotulo' => 'Activa', 'padrao' => 1, 'lista' => true],
        ],
    ],
];
