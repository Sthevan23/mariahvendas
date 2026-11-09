<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;
use App\Models\Product;

class CatalogController extends Controller
{
    public function index(?string $slug = null): void
    {
        $selectedCategory = $slug ?: ($this->input('categoria') ?: null);
        $page = max(1, (int) $this->input('page', 1));
        $perPage = 24;

        $filters = [
            'active_only' => true,
            'category_slug' => $selectedCategory,
            'search' => trim((string) $this->input('q', '')),
            'size' => $this->input('tamanho') ?: null,
            'color' => $this->input('cor') ?: null,
            'min_price' => $this->input('min'),
            'max_price' => $this->input('max'),
            'in_stock' => $this->input('disponivel') ? true : false,
            'sort' => $this->input('ordenar', 'newest'),
        ];

        if ($this->input('promocoes')) {
            $filters['is_sale'] = true;
        }
        if ($this->input('novidades') || $selectedCategory === 'novidades') {
            $filters['is_new'] = true;
            unset($filters['category_slug']);
        }
        if ($selectedCategory === 'promocoes') {
            $filters['is_sale'] = true;
            unset($filters['category_slug']);
        }

        $total = Product::count($filters);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $filters['limit'] = $perPage;
        $filters['offset'] = ($page - 1) * $perPage;

        $category = ($selectedCategory && !in_array($selectedCategory, ['novidades', 'promocoes'], true))
            ? Category::findBySlug($selectedCategory)
            : null;

        if ($selectedCategory === 'colecao-atelier' && !$category) {
            $category = ['name' => 'Coleção Festa', 'slug' => 'colecao-atelier'];
        } elseif ($selectedCategory === 'colecao-atelier') {
            $category['name'] = $category['name'] ?: 'Coleção Festa';
        }

        $products = Product::query($filters);

        $title = 'Vestidos';
        if ($filters['search']) {
            $title = 'Busca';
        } elseif (!empty($filters['is_new'])) {
            $title = 'Novidades';
        } elseif (!empty($filters['is_sale']) && empty($filters['category_slug'])) {
            $title = 'Promoções';
        } elseif ($category) {
            $title = $category['name'];
        }

        $query = $_GET;
        unset($query['page']);

        $this->render('catalog/index', [
            'title' => $title,
            'meta_description' => 'Explore a coleção Ateliê Mariah de vestidos de festa.',
            'products' => $products,
            'categories' => Category::allActive(),
            'sizes' => Product::sizes(),
            'colors' => Product::colors(),
            'filters' => $filters,
            'category' => $category,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'query' => $query,
        ]);
    }

    public function search(): void
    {
        $this->index();
    }
}
