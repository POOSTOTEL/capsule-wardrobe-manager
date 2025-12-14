<?php


namespace App\Controllers;

use App\Services\TaxonomyService;

class TaxonomyController extends Controller
{
    protected $taxonomyService;

    public function __construct()
    {
        $this->taxonomyService = new TaxonomyService();
    }

    
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

    
    public function forForms(): void
    {
        $this->json([
            'success' => true,
            'data' => $this->taxonomyService->getFormData()
        ]);
    }

    
    public function categories(): void
    {
        $categoryModel = new \App\Models\Category();

        $this->json([
            'success' => true,
            'data' => $categoryModel->getAllSorted(),
            'count' => $categoryModel->count()
        ]);
    }

    
    public function colors(): void
    {
        $colorModel = new \App\Models\Color();

        $this->json([
            'success' => true,
            'data' => $colorModel->getWithHexCodes(),
            'count' => $colorModel->count()
        ]);
    }

    
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