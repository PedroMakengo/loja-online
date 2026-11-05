<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\Marca;
use App\Models\ZonaEntrega;

class PaginaController extends Controller
{
    public function sobre(): void
    {
        $this->view('paginas/sobre', ['seo' => ['titulo' => 'Sobre nós', 'descricao' => 'Loja de tecnologia angolana com garantia oficial, factura com NIF e entrega em todo o país.']]);
    }

    public function faq(): void
    {
        $this->view('paginas/faq', ['seo' => ['titulo' => 'Perguntas frequentes', 'descricao' => 'Pagamentos com Multicaixa Express, prazos de entrega, garantia e devoluções na DIOTEKNO.']]);
    }

    public function entregas(): void
    {
        $this->view('paginas/entregas', [
            'zonas' => (new ZonaEntrega())->todas(),
            'seo'   => ['titulo' => 'Entregas e prazos', 'descricao' => 'Preços e prazos de entrega em Luanda e nas 21 províncias de Angola.'],
        ]);
    }

    public function marcas(): void
    {
        $this->view('paginas/marcas', [
            'marcas' => (new Marca())->todasActivas(),
            'seo'    => ['titulo' => 'Marcas', 'descricao' => 'Samsung, Apple, HP, Dell, Lenovo, LG, Sony, Huawei e mais, com garantia oficial em Angola.'],
        ]);
    }

    public function privacidade(): void
    {
        $this->view('paginas/legal', ['tipo' => 'privacidade', 'seo' => ['titulo' => 'Política de privacidade']]);
    }

    public function termos(): void
    {
        $this->view('paginas/legal', ['tipo' => 'termos', 'seo' => ['titulo' => 'Termos e condições']]);
    }

    // ----- Módulos das fases seguintes ------------------------------------

    private function emBreve(string $titulo, string $intro): void
    {
        $this->view('paginas/em-breve', [
            'tituloPagina' => $titulo,
            'introPagina'  => $intro,
            'seo'          => ['titulo' => $titulo, 'noindex' => true],
        ]);
    }
}
