<?php


namespace App\Controllers;

use App\Models\Analytics;
use App\Models\Item;
use App\Models\Outfit;
use App\Models\Capsule;
use App\Middleware\AuthMiddleware;

class AnalyticsController extends Controller
{
    protected $analyticsModel;
    protected $userId;

    public function __construct()
    {
        
        $authMiddleware = new AuthMiddleware();
        if (!$authMiddleware->handle()) {
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'message' => 'Требуется авторизация'
                ], 401);
                exit();
            } else {
                $this->redirect('/login');
                exit();
            }
        }

        $this->analyticsModel = new Analytics();
        $this->userId = $_SESSION['user_id'] ?? null;
    }

    
    public function index(): void
    {
        $itemModel = new Item();
        $outfitModel = new Outfit();
        $capsuleModel = new Capsule();

        
        $totalItems = $itemModel->getTotalCount($this->userId);
        $totalOutfits = $outfitModel->getTotalCount($this->userId);
        $totalCapsules = $capsuleModel->getTotalCount($this->userId);

        
        $categoryDistribution = $this->analyticsModel->getCategoryDistribution($this->userId, 5);
        
        
        $colorDistribution = $this->analyticsModel->getColorDistribution($this->userId, 5);

        
        $topUsedItems = $this->analyticsModel->getTopUsedItems($this->userId, 5);

        
        $seasonStats = $this->analyticsModel->getSeasonStatistics($this->userId);

        
        $usageStats = $this->analyticsModel->getUsageStatistics($this->userId);

        if ($this->isAjax()) {
            $this->json([
                'success' => true,
                'data' => [
                    'total_items' => $totalItems,
                    'total_outfits' => $totalOutfits,
                    'total_capsules' => $totalCapsules,
                    'category_distribution' => $categoryDistribution,
                    'color_distribution' => $colorDistribution,
                    'top_used_items' => $topUsedItems,
                    'season_stats' => $seasonStats,
                    'usage_stats' => $usageStats
                ]
            ]);
            return;
        }

        $data = [
            'title' => 'Аналитика - Капсульный Гардероб',
            'total_items' => $totalItems,
            'total_outfits' => $totalOutfits,
            'total_capsules' => $totalCapsules,
            'category_distribution' => $categoryDistribution,
            'color_distribution' => $colorDistribution,
            'top_used_items' => $topUsedItems,
            'season_stats' => $seasonStats,
            'usage_stats' => $usageStats,
            'styles' => ['/assets/css/analytics.css']
        ];

        $this->render('analytics/dashboard', $data);
    }

    
    public function categories(): void
    {
        $categoryDistribution = $this->analyticsModel->getCategoryDistribution($this->userId);

        if ($this->isAjax()) {
            $this->json([
                'success' => true,
                'data' => $categoryDistribution
            ]);
            return;
        }

        $data = [
            'title' => 'Распределение по категориям - Аналитика',
            'category_distribution' => $categoryDistribution,
            'styles' => ['/assets/css/analytics.css']
        ];

        $this->render('analytics/categories', $data);
    }

    
    public function colors(): void
    {
        $colorDistribution = $this->analyticsModel->getColorDistribution($this->userId);

        if ($this->isAjax()) {
            $this->json([
                'success' => true,
                'data' => $colorDistribution
            ]);
            return;
        }

        $data = [
            'title' => 'Распределение по цветам - Аналитика',
            'color_distribution' => $colorDistribution,
            'styles' => ['/assets/css/analytics.css']
        ];

        $this->render('analytics/colors', $data);
    }

    
    public function usage(): void
    {
        $filter = $this->input('filter', 'all'); 

        $items = $this->analyticsModel->getItemsUsageIndex($this->userId, $filter);

        if ($this->isAjax()) {
            $this->json([
                'success' => true,
                'data' => $items
            ]);
            return;
        }

        $data = [
            'title' => 'Индекс использования вещей - Аналитика',
            'items' => $items,
            'filter' => $filter,
            'styles' => ['/assets/css/analytics.css']
        ];

        $this->render('analytics/usage', $data);
    }

    
    public function compatibility(): void
    {
        $limit = (int) $this->input('limit', 20);
        $compatibility = $this->analyticsModel->getCompatibilityMap($this->userId, $limit);

        if ($this->isAjax()) {
            $this->json([
                'success' => true,
                'data' => $compatibility
            ]);
            return;
        }

        $data = [
            'title' => 'Карта сочетаемости - Аналитика',
            'compatibility' => $compatibility,
            'limit' => $limit,
            'styles' => ['/assets/css/analytics.css']
        ];

        $this->render('analytics/compatibility', $data);
    }
}
