<?php


namespace App\Controllers;

class HomeController extends Controller
{
    public function index(): void
    {
        
        $stats = [];
        if (isset($_SESSION['user_id'])) {
            $itemModel = new \App\Models\Item();
            $outfitModel = new \App\Models\Outfit();
            $capsuleModel = new \App\Models\Capsule();
            $analyticsModel = new \App\Models\Analytics();
            
            
            $usageStats = $analyticsModel->getUsageStatistics($_SESSION['user_id']);
            $wardrobeUsagePercentage = $usageStats['used_percentage'] ?? 0;
            
            $stats = [
                'total_items' => $itemModel->getTotalCount($_SESSION['user_id']),
                'total_outfits' => $outfitModel->getTotalCount($_SESSION['user_id']),
                'total_capsules' => $capsuleModel->getTotalCount($_SESSION['user_id']),
                'wardrobe_usage_percentage' => $wardrobeUsagePercentage
            ];
        }
        
        $data = [
            'title' => 'Капсульный Гардероб - Главная',
            'styles' => ['/assets/css/dashboard.css'],
            'stats' => $stats,
            'content' => $this->renderView('home/index', ['stats' => $stats])
        ];

        $this->renderLayout('main', $data);
    }

    
    protected function renderView(string $view, array $data = []): string
    {
        extract($data, EXTR_SKIP);

        $viewPath = VIEWS_PATH . '/' . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View file not found: {$viewPath}");
        }

        ob_start();
        require $viewPath;
        return ob_get_clean();
    }

    
    protected function renderLayout(string $layout, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $layoutPath = VIEWS_PATH . '/layouts/' . $layout . '.php';

        if (!file_exists($layoutPath)) {
            throw new \RuntimeException("Layout file not found: {$layoutPath}");
        }

        require $layoutPath;
    }
}