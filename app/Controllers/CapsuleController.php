<?php
// app/Controllers/CapsuleController.php

namespace App\Controllers;

use App\Models\Capsule;
use App\Models\Item;
use App\Models\Outfit;
use App\Models\Season;
use App\Middleware\AuthMiddleware;
use App\Utils\Logger;

class CapsuleController extends Controller
{
    protected $capsuleModel;
    protected $userId;

    public function __construct()
    {
        // Проверяем аутентификацию
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

        $this->capsuleModel = new Capsule();
        $this->userId = $_SESSION['user_id'] ?? null;
    }

    // Список всех капсул пользователя
    public function index(): void
    {
        // Получаем фильтры из запроса
        $filters = [
            'season_id' => $this->input('season_id'),
            'search' => $this->input('search'),
            'order_by' => $this->input('order_by', 'created_at'),
            'order_dir' => $this->input('order_dir', 'DESC'),
            'limit' => $this->input('limit', 50),
            'offset' => $this->input('offset', 0)
        ];

        // Удаляем пустые фильтры
        $filters = array_filter($filters, function($value) {
            return $value !== '' && $value !== null && $value !== [];
        });

        if ($this->isAjax()) {
            $capsules = $this->capsuleModel->getByUser($this->userId, $filters);
            
            $this->json([
                'success' => true,
                'data' => $capsules,
                'count' => count($capsules)
            ]);
            return;
        }

        // Загружаем справочники для фильтров
        $seasonModel = new Season();

        $data = [
            'title' => 'Мои капсулы - Капсульный Гардероб',
            'capsules' => $this->capsuleModel->getByUser($this->userId, $filters),
            'seasons' => $seasonModel->getAllSorted(),
            'filters' => $filters,
            'styles' => ['/assets/css/capsules.css']
        ];

        $this->render('capsules/index', $data);
    }

    // Показать детальную информацию о капсуле
    public function show(int $id): void
    {
        $capsule = $this->capsuleModel->getWithDetails($id, $this->userId);

        if (!$capsule) {
            $this->setFlash('error', 'Капсула не найдена');
            $this->redirect('/capsules');
            return;
        }

        if ($this->isAjax()) {
            $this->json([
                'success' => true,
                'data' => $capsule
            ]);
            return;
        }

        $data = [
            'title' => htmlspecialchars($capsule['name']) . ' - Капсульный Гардероб',
            'capsule' => $capsule,
            'styles' => ['/assets/css/capsules.css']
        ];

        $this->render('capsules/show', $data);
    }

    // Форма создания новой капсулы
    public function create(): void
    {
        $itemModel = new Item();
        $outfitModel = new Outfit();
        $seasonModel = new Season();

        $data = [
            'title' => 'Создать капсулу - Капсульный Гардероб',
            'items' => $itemModel->getByUser($this->userId, ['limit' => 1000]),
            'outfits' => $outfitModel->getByUser($this->userId, ['limit' => 1000]),
            'seasons' => $seasonModel->getAllSorted(),
            'styles' => ['/assets/css/capsules.css']
        ];

        $this->render('capsules/create', $data);
    }

    // Сохранить новую капсулу
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error('Метод не разрешен', 405);
            return;
        }

        // Валидация
        $errors = $this->validateCapsuleData($this->input());

        if (!empty($errors)) {
            if ($this->isAjax()) {
                $this->error('Ошибки валидации', 400, $errors);
                return;
            }

            foreach ($errors as $field => $fieldErrors) {
                foreach ($fieldErrors as $error) {
                    $this->setFlash('error', $error);
                }
            }
            $this->redirect('/capsules/create');
            return;
        }

        // Подготавливаем данные
        $data = [
            'name' => trim($this->input('name')),
            'description' => trim($this->input('description', '')),
            'season_id' => $this->input('season_id') ? (int) $this->input('season_id') : null,
            'item_ids' => $this->input('item_ids') ? (is_array($this->input('item_ids')) ? $this->input('item_ids') : explode(',', $this->input('item_ids'))) : [],
            'outfit_ids' => $this->input('outfit_ids') ? (is_array($this->input('outfit_ids')) ? $this->input('outfit_ids') : explode(',', $this->input('outfit_ids'))) : []
        ];

        Logger::info('Начало создания капсулы', [
            'user_id' => $this->userId,
            'data' => array_merge($data, [
                'item_ids_count' => count($data['item_ids']),
                'outfit_ids_count' => count($data['outfit_ids'])
            ])
        ]);

        try {
            $capsuleId = $this->capsuleModel->createCapsule($this->userId, $data);

            Logger::info('Капсула успешно создана', [
                'user_id' => $this->userId,
                'capsule_id' => $capsuleId
            ]);

            if ($this->isAjax()) {
                $capsule = $this->capsuleModel->getWithDetails($capsuleId, $this->userId);
                $this->success([
                    'id' => $capsuleId,
                    'capsule' => $capsule,
                    'redirect_url' => '/capsules'
                ], 'Капсула успешно создана', 201);
                return;
            }

            $this->setFlash('success', 'Капсула успешно создана');
            $this->redirect('/capsules');
        } catch (\Exception $e) {
            Logger::error('Ошибка при создании капсулы', [
                'user_id' => $this->userId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);

            $message = 'Ошибка при создании капсулы. Попробуйте еще раз.';
            
            if ($this->isAjax()) {
                $this->error($message, 500);
                return;
            }
            
            $this->setFlash('error', $message);
            $this->redirect('/capsules/create');
        }
    }

    // Форма редактирования капсулы
    public function edit(int $id): void
    {
        $capsule = $this->capsuleModel->getWithDetails($id, $this->userId);

        if (!$capsule) {
            $this->setFlash('error', 'Капсула не найдена');
            $this->redirect('/capsules');
            return;
        }

        $itemModel = new Item();
        $outfitModel = new Outfit();
        $seasonModel = new Season();

        $data = [
            'title' => 'Редактировать капсулу - Капсульный Гардероб',
            'capsule' => $capsule,
            'items' => $itemModel->getByUser($this->userId, ['limit' => 1000]),
            'outfits' => $outfitModel->getByUser($this->userId, ['limit' => 1000]),
            'seasons' => $seasonModel->getAllSorted(),
            'selectedItemIds' => array_column($capsule['items'], 'id'),
            'selectedOutfitIds' => array_column($capsule['outfits'], 'id'),
            'styles' => ['/assets/css/capsules.css']
        ];

        $this->render('capsules/edit', $data);
    }

    // Обновить капсулу
    public function update(int $id): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $this->error('Метод не разрешен', 405);
            return;
        }

        $capsule = $this->capsuleModel->find($id);
        if (!$capsule || $capsule['user_id'] != $this->userId) {
            $this->error('Капсула не найдена', 404);
            return;
        }

        // Валидация
        $inputData = $this->input();
        $errors = $this->validateCapsuleData($inputData, $id);

        if (!empty($errors)) {
            if ($this->isAjax()) {
                $this->error('Ошибки валидации', 400, $errors);
                return;
            }

            foreach ($errors as $field => $fieldErrors) {
                foreach ($fieldErrors as $error) {
                    $this->setFlash('error', $error);
                }
            }
            $this->redirect("/capsules/{$id}/edit");
            return;
        }

        // Подготавливаем данные
        $data = [
            'name' => trim($this->input('name')),
            'description' => trim($this->input('description', '')),
            'season_id' => $this->input('season_id') ? (int) $this->input('season_id') : null
        ];

        // Обновляем вещи и образы только если они переданы
        if ($this->input('item_ids') !== null) {
            $data['item_ids'] = is_array($this->input('item_ids')) 
                ? $this->input('item_ids') 
                : explode(',', $this->input('item_ids'));
        }

        if ($this->input('outfit_ids') !== null) {
            $data['outfit_ids'] = is_array($this->input('outfit_ids')) 
                ? $this->input('outfit_ids') 
                : explode(',', $this->input('outfit_ids'));
        }

        try {
            $success = $this->capsuleModel->updateCapsule($id, $this->userId, $data);

            if (!$success) {
                throw new \RuntimeException('Не удалось обновить капсулу');
            }

            if ($this->isAjax()) {
                $capsule = $this->capsuleModel->getWithDetails($id, $this->userId);
                $this->success($capsule, 'Капсула успешно обновлена');
                return;
            }

            $this->setFlash('success', 'Капсула успешно обновлена');
            $this->redirect("/capsules/{$id}");
        } catch (\Exception $e) {
            $message = 'Ошибка при обновлении капсулы: ' . $e->getMessage();
            if ($this->isAjax()) {
                $this->error($message, 500);
                return;
            }
            $this->setFlash('error', $message);
            $this->redirect("/capsules/{$id}/edit");
        }
    }

    // Удалить капсулу
    public function destroy(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->error('Метод не разрешен', 405);
            return;
        }

        $success = $this->capsuleModel->deleteCapsule($id, $this->userId);

        if (!$success) {
            $this->error('Не удалось удалить капсулу. Возможно, капсула не существует или у вас нет прав', 400);
            return;
        }

        if ($this->isAjax()) {
            $this->success(null, 'Капсула успешно удалена');
            return;
        }

        $this->setFlash('success', 'Капсула успешно удалена');
        $this->redirect('/capsules');
    }

    // Генерация комбинаций внутри капсулы
    public function combinations(int $id): void
    {
        $capsule = $this->capsuleModel->getWithDetails($id, $this->userId);

        if (!$capsule) {
            $this->setFlash('error', 'Капсула не найдена');
            $this->redirect('/capsules');
            return;
        }

        // Генерируем комбинации
        $combinations = $this->capsuleModel->generateCombinations($id, $this->userId);

        $data = [
            'title' => 'Комбинации капсулы - Капсульный Гардероб',
            'capsule' => $capsule,
            'combinations' => $combinations,
            'styles' => ['/assets/css/capsules.css']
        ];

        $this->render('capsules/combinations', $data);
    }

    // Генерация образов из вещей капсулы
    public function generateOutfits(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error('Метод не разрешен', 405);
            return;
        }

        $capsule = $this->capsuleModel->getWithDetails($id, $this->userId);

        if (!$capsule) {
            $this->error('Капсула не найдена', 404);
            return;
        }

        // Проверяем, что в капсуле есть вещи
        if (empty($capsule['items'])) {
            $this->error('В капсуле нет вещей для генерации образов', 400);
            return;
        }

        // Получаем количество образов для генерации
        $count = (int) $this->input('count', 5);
        
        // Валидация количества
        if ($count < 1 || $count > 50) {
            $this->error('Количество образов должно быть от 1 до 50', 400);
            return;
        }

        try {
            Logger::info('Начало генерации образов из капсулы', [
                'user_id' => $this->userId,
                'capsule_id' => $id,
                'count' => $count
            ]);

            // Генерируем образы
            $generatedOutfitIds = $this->capsuleModel->generateOutfits($id, $this->userId, $count);

            Logger::info('Образы успешно сгенерированы', [
                'user_id' => $this->userId,
                'capsule_id' => $id,
                'generated_count' => count($generatedOutfitIds),
                'requested_count' => $count
            ]);

            // Обновляем данные капсулы
            $capsule = $this->capsuleModel->getWithDetails($id, $this->userId);

            if ($this->isAjax()) {
                $this->success([
                    'generated_count' => count($generatedOutfitIds),
                    'requested_count' => $count,
                    'capsule' => $capsule
                ], 'Образы успешно сгенерированы');
                return;
            }

            $this->setFlash('success', 'Успешно сгенерировано ' . count($generatedOutfitIds) . ' образов');
            $this->redirect("/capsules/{$id}");
        } catch (\Exception $e) {
            Logger::error('Ошибка при генерации образов из капсулы', [
                'user_id' => $this->userId,
                'capsule_id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $message = 'Ошибка при генерации образов: ' . $e->getMessage();
            
            if ($this->isAjax()) {
                $this->error($message, 500);
                return;
            }
            
            $this->setFlash('error', $message);
            $this->redirect("/capsules/{$id}");
        }
    }

    // Валидация данных капсулы
    private function validateCapsuleData(array $data, int $capsuleId = null): array
    {
        $errors = [];

        // Название
        if (empty($data['name'])) {
            $errors['name'][] = 'Название капсулы обязательно для заполнения';
        } elseif (strlen($data['name']) > 200) {
            $errors['name'][] = 'Название не должно превышать 200 символов';
        }

        // Описание (опционально)
        if (!empty($data['description']) && strlen($data['description']) > 1000) {
            $errors['description'][] = 'Описание не должно превышать 1000 символов';
        }

        // Сезон (опционально)
        if (!empty($data['season_id'])) {
            $seasonModel = new Season();
            $season = $seasonModel->find((int) $data['season_id']);
            if (!$season) {
                $errors['season_id'][] = 'Выбранный сезон не существует';
            }
        }

        return $errors;
    }
}
