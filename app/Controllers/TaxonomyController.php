<?php
// app/Controllers/TaxonomyController.php

namespace App\Controllers;

use App\Services\TaxonomyService;

class TaxonomyController extends Controller
{
    protected $taxonomyService;

    public function __construct()
    {
        $this->taxonomyService = new TaxonomyService();
    }

    // Получить все справочники (страница для просмотра)
    public function index(): void
    {
        if ($this->isAjax()) {
            $this->json($this->taxonomyService->getForApi());
            return;
        }

        $data = [
            'title' => 'Справочники - Капсульный Гардероб',
            'taxonomies' => $this->taxonomyService->getAllWithDetails(),
        ];

        $this->render('taxonomies/index', $data);
    }

    // Получить справочники для форм (API)
    public function forForms(): void
    {
        $this->json([
            'success' => true,
            'data' => $this->taxonomyService->getFormData()
        ]);
    }

    // Получить категории (API)
    public function categories(): void
    {
        $categoryModel = new \App\Models\Category();

        $this->json([
            'success' => true,
            'data' => $categoryModel->getAllSorted(),
            'count' => $categoryModel->count()
        ]);
    }

    // Получить цвета (API)
    public function colors(): void
    {
        $colorModel = new \App\Models\Color();

        $this->json([
            'success' => true,
            'data' => $colorModel->getWithHexCodes(),
            'count' => $colorModel->count()
        ]);
    }

    // Получить сезоны (API)
    public function seasons(): void
    {
        $seasonModel = new \App\Models\Season();

        $this->json([
            'success' => true,
            'data' => $seasonModel->getAllSorted(),
            'count' => $seasonModel->count()
        ]);
    }
}