<?php
// app/Controllers/OutfitController.php

namespace App\Controllers;

use App\Models\Outfit;
use App\Models\Item;
use App\Models\Season;
use App\Models\Tag;
use App\Models\Capsule;
use App\Middleware\AuthMiddleware;
use App\Utils\Logger;

class OutfitController extends Controller
{
    protected $outfitModel;
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

        $this->outfitModel = new Outfit();
        $this->userId = $_SESSION['user_id'] ?? null;
    }

    /**
     * Список всех образов пользователя с фильтрацией, поиском и сортировкой
     */
    public function index(): void
    {
        // Получаем фильтры из запроса
        $filters = [
            'season_id' => $this->input('season_id'),
            'formality_level' => $this->input('formality_level'),
            'is_favorite' => $this->input('is_favorite'),
            'search' => $this->input('search'),
            'tag_ids' => $this->input('tag_ids') ? explode(',', $this->input('tag_ids')) : [],
            'order_by' => $this->input('order_by', 'created_at'),
            'order_dir' => $this->input('order_dir', 'DESC'),
            'limit' => $this->input('limit', 50),
            'offset' => $this->input('offset', 0)
        ];

        // Удаляем пустые фильтры
        $filters = array_filter($filters, function($value) {
            return $value !== '' && $value !== null && $value !== [];
        });

        Logger::debug('Получение списка образов', [
            'user_id' => $this->userId,
            'filters' => $filters,
            'is_ajax' => $this->isAjax()
        ]);

        if ($this->isAjax()) {
            try {
                $outfits = $this->outfitModel->getByUser($this->userId, $filters);
                
                Logger::info('Список образов получен', [
                    'user_id' => $this->userId,
                    'count' => count($outfits)
                ]);
                
                $this->json([
                    'success' => true,
                    'data' => $outfits,
                    'count' => count($outfits)
                ]);
                return;
            } catch (\Exception $e) {
                Logger::error('Ошибка при получении списка образов', [
                    'user_id' => $this->userId,
                    'message' => $e->getMessage()
                ]);
                $this->error('Ошибка при получении списка образов', 500);
                return;
            }
        }

        // Загружаем справочники для фильтров
        $seasonModel = new Season();
        $tagModel = new Tag();

        $data = [
            'title' => 'Мои образы - Капсульный Гардероб',
            'outfits' => $this->outfitModel->getByUser($this->userId, $filters),
            'seasons' => $seasonModel->getAllSorted(),
            'tags' => $tagModel->getByUser($this->userId),
            'filters' => $filters,
            'styles' => ['/assets/css/outfits.css']
        ];

        $this->render('outfits/index', $data);
    }

    /**
     * Показать детальную информацию об образе
     */
    public function show(int $id): void
    {
        Logger::debug('Просмотр образа', [
            'user_id' => $this->userId,
            'outfit_id' => $id
        ]);

        $outfit = $this->outfitModel->getWithDetails($id, $this->userId);

        if (!$outfit) {
            Logger::warning('Попытка просмотра несуществующего образа', [
                'user_id' => $this->userId,
                'outfit_id' => $id
            ]);
            $this->setFlash('error', 'Образ не найден');
            $this->redirect('/outfits');
            return;
        }

        Logger::info('Образ просмотрен', [
            'user_id' => $this->userId,
            'outfit_id' => $id,
            'outfit_name' => $outfit['name']
        ]);

        if ($this->isAjax()) {
            $this->json([
                'success' => true,
                'data' => $outfit
            ]);
            return;
        }

        $data = [
            'title' => htmlspecialchars($outfit['name']) . ' - Капсульный Гардероб',
            'outfit' => $outfit,
            'styles' => ['/assets/css/outfits.css']
        ];

        $this->render('outfits/show', $data);
    }

    /**
     * Форма создания нового образа
     */
    public function create(): void
    {
        $itemModel = new Item();
        $seasonModel = new Season();
        $tagModel = new Tag();

        $data = [
            'title' => 'Создать образ - Капсульный Гардероб',
            'items' => $itemModel->getByUser($this->userId, ['limit' => 1000]),
            'seasons' => $seasonModel->getAllSorted(),
            'tags' => $tagModel->getByUser($this->userId),
            'styles' => ['/assets/css/outfits.css']
        ];

        $this->render('outfits/create', $data);
    }

    /**
     * Сохранить новый образ
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error('Метод не разрешен', 405);
            return;
        }

        // Валидация
        $errors = $this->validateOutfitData($this->input());

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
            $this->redirect('/outfits/create');
            return;
        }

        // Подготавливаем данные
        $data = [
            'name' => trim($this->input('name')),
            'description' => trim($this->input('description', '')),
            'formality_level' => $this->input('formality_level') ? (int) $this->input('formality_level') : null,
            'season_id' => $this->input('season_id') ? (int) $this->input('season_id') : null,
            'is_favorite' => $this->input('is_favorite') === '1' || $this->input('is_favorite') === true,
            'item_ids' => $this->input('item_ids') ? (is_array($this->input('item_ids')) ? $this->input('item_ids') : explode(',', $this->input('item_ids'))) : [],
            'tag_ids' => $this->input('tag_ids') ? (is_array($this->input('tag_ids')) ? array_map('intval', $this->input('tag_ids')) : array_map('intval', explode(',', $this->input('tag_ids')))) : []
        ];

        Logger::info('Начало создания образа', [
            'user_id' => $this->userId,
            'data' => array_merge($data, [
                'item_ids_count' => count($data['item_ids']),
                'tag_ids_count' => count($data['tag_ids'])
            ])
        ]);

        try {
            $outfitId = $this->outfitModel->createOutfit($this->userId, $data);

            Logger::info('Образ успешно создан', [
                'user_id' => $this->userId,
                'outfit_id' => $outfitId,
                'outfit_name' => $data['name'],
                'items_count' => count($data['item_ids'] ?? []),
                'tags_count' => count($data['tag_ids'] ?? [])
            ]);

            if ($this->isAjax()) {
                $outfit = $this->outfitModel->getWithDetails($outfitId, $this->userId);
                $this->success([
                    'id' => $outfitId,
                    'outfit' => $outfit,
                    'redirect_url' => '/outfits'
                ], 'Образ успешно создан', 201);
                return;
            }

            $this->setFlash('success', 'Образ успешно создан');
            $this->redirect('/outfits');
        } catch (\Exception $e) {
            Logger::error('Ошибка при создании образа', [
                'user_id' => $this->userId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'data' => array_merge($data, [
                    'item_ids_count' => count($data['item_ids'] ?? []),
                    'tag_ids_count' => count($data['tag_ids'] ?? [])
                ])
            ]);

            $message = 'Ошибка при создании образа. Попробуйте еще раз.';
            
            if ($this->isAjax()) {
                $this->error($message, 500);
                return;
            }
            
            $this->setFlash('error', $message);
            $this->redirect('/outfits/create');
        }
    }

    /**
     * Форма редактирования образа
     */
    public function edit(int $id): void
    {
        $outfit = $this->outfitModel->getWithDetails($id, $this->userId);

        if (!$outfit) {
            $this->setFlash('error', 'Образ не найден');
            $this->redirect('/outfits');
            return;
        }

        $itemModel = new Item();
        $seasonModel = new Season();
        $tagModel = new Tag();

        $data = [
            'title' => 'Редактировать образ - Капсульный Гардероб',
            'outfit' => $outfit,
            'items' => $itemModel->getByUser($this->userId, ['limit' => 1000]),
            'seasons' => $seasonModel->getAllSorted(),
            'tags' => $tagModel->getByUser($this->userId),
            'selectedItemIds' => array_column($outfit['items'], 'id'),
            'selectedTagIds' => array_column($outfit['tags'], 'id'),
            'styles' => ['/assets/css/outfits.css']
        ];

        $this->render('outfits/edit', $data);
    }

    /**
     * Обновить образ
     */
    public function update(int $id): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $this->error('Метод не разрешен', 405);
            return;
        }

        $outfit = $this->outfitModel->find($id);
        if (!$outfit || $outfit['user_id'] != $this->userId) {
            $this->error('Образ не найден', 404);
            return;
        }

        // Валидация
        $inputData = $this->input();
        $errors = $this->validateOutfitData($inputData, $id);

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
            $this->redirect("/outfits/{$id}/edit");
            return;
        }

        // Подготавливаем данные
        $data = [
            'name' => trim($this->input('name')),
            'description' => trim($this->input('description', '')),
            'formality_level' => $this->input('formality_level') ? (int) $this->input('formality_level') : null,
            'season_id' => $this->input('season_id') ? (int) $this->input('season_id') : null,
            'is_favorite' => $this->input('is_favorite') === '1' || $this->input('is_favorite') === true
        ];

        // Обновляем вещи и теги только если они переданы
        if ($this->input('item_ids') !== null) {
            $data['item_ids'] = is_array($this->input('item_ids')) 
                ? $this->input('item_ids') 
                : explode(',', $this->input('item_ids'));
        }

        if ($this->input('tag_ids') !== null) {
            $data['tag_ids'] = is_array($this->input('tag_ids')) 
                ? array_map('intval', $this->input('tag_ids'))
                : array_map('intval', explode(',', $this->input('tag_ids')));
        }

        try {
            Logger::info('Начало обновления образа', [
                'user_id' => $this->userId,
                'outfit_id' => $id,
                'data_keys' => array_keys($data)
            ]);

            $success = $this->outfitModel->updateOutfit($id, $this->userId, $data);

            if (!$success) {
                Logger::warning('Не удалось обновить образ', [
                    'user_id' => $this->userId,
                    'outfit_id' => $id
                ]);
                throw new \RuntimeException('Не удалось обновить образ');
            }

            Logger::info('Образ успешно обновлен', [
                'user_id' => $this->userId,
                'outfit_id' => $id
            ]);

            if ($this->isAjax()) {
                $outfit = $this->outfitModel->getWithDetails($id, $this->userId);
                $this->success($outfit, 'Образ успешно обновлен');
                return;
            }

            $this->setFlash('success', 'Образ успешно обновлен');
            $this->redirect("/outfits/{$id}");
        } catch (\Exception $e) {
            Logger::error('Ошибка при обновлении образа', [
                'user_id' => $this->userId,
                'outfit_id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $message = 'Ошибка при обновлении образа: ' . $e->getMessage();
            if ($this->isAjax()) {
                $this->error($message, 500);
                return;
            }
            $this->setFlash('error', $message);
            $this->redirect("/outfits/{$id}/edit");
        }
    }

    /**
     * Удалить образ
     */
    public function destroy(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->error('Метод не разрешен', 405);
            return;
        }

        Logger::info('Попытка удаления образа', [
            'user_id' => $this->userId,
            'outfit_id' => $id
        ]);

        $success = $this->outfitModel->deleteOutfit($id, $this->userId);

        if (!$success) {
            Logger::warning('Не удалось удалить образ', [
                'user_id' => $this->userId,
                'outfit_id' => $id
            ]);
            $this->error('Не удалось удалить образ. Возможно, образ не существует или у вас нет прав', 400);
            return;
        }

        Logger::info('Образ успешно удален', [
            'user_id' => $this->userId,
            'outfit_id' => $id
        ]);

        if ($this->isAjax()) {
            $this->success(null, 'Образ успешно удален');
            return;
        }

        $this->setFlash('success', 'Образ успешно удален');
        $this->redirect('/outfits');
    }

    /**
     * Переключить избранное
     */
    public function toggleFavorite(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error('Метод не разрешен', 405);
            return;
        }

        Logger::debug('Переключение избранного образа', [
            'user_id' => $this->userId,
            'outfit_id' => $id
        ]);

        $success = $this->outfitModel->toggleFavorite($id, $this->userId);

        if (!$success) {
            Logger::warning('Не удалось переключить избранное образа', [
                'user_id' => $this->userId,
                'outfit_id' => $id
            ]);
            $this->error('Не удалось обновить статус избранного', 400);
            return;
        }

        $outfit = $this->outfitModel->find($id);
        Logger::info('Статус избранного образа обновлен', [
            'user_id' => $this->userId,
            'outfit_id' => $id,
            'is_favorite' => $outfit['is_favorite']
        ]);

        $this->success([
            'is_favorite' => $outfit['is_favorite']
        ], 'Статус избранного обновлен');
    }

    /**
     * Добавить образ в капсулу (добавить вещи из образа в капсулу)
     */
    public function addToCapsule(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error('Метод не разрешен', 405);
            return;
        }

        $capsuleId = (int) $this->input('capsule_id');
        if (!$capsuleId) {
            $this->error('ID капсулы обязателен', 400);
            return;
        }

        Logger::info('Добавление образа в капсулу', [
            'user_id' => $this->userId,
            'outfit_id' => $id,
            'capsule_id' => $capsuleId
        ]);

        $success = $this->outfitModel->addToCapsule($id, $capsuleId, $this->userId);

        if (!$success) {
            Logger::warning('Не удалось добавить образ в капсулу', [
                'user_id' => $this->userId,
                'outfit_id' => $id,
                'capsule_id' => $capsuleId
            ]);
            $this->error('Не удалось добавить образ в капсулу', 400);
            return;
        }

        Logger::info('Образ успешно добавлен в капсулу', [
            'user_id' => $this->userId,
            'outfit_id' => $id,
            'capsule_id' => $capsuleId
        ]);

        $this->success(null, 'Вещи из образа успешно добавлены в капсулу');
    }

    /**
     * Генерировать образы из капсулы (без сохранения)
     */
    public function generateFromCapsule(int $capsuleId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error('Метод не разрешен', 405);
            return;
        }

        $count = (int) $this->input('count', 10);
        if ($count < 1 || $count > 50) {
            $this->error('Количество образов должно быть от 1 до 50', 400);
            return;
        }

        Logger::info('Генерация образов из капсулы', [
            'user_id' => $this->userId,
            'capsule_id' => $capsuleId,
            'count' => $count
        ]);

        try {
            $generatedOutfits = $this->outfitModel->generateFromCapsule($capsuleId, $this->userId, $count);
            
            Logger::info('Образы успешно сгенерированы из капсулы', [
                'user_id' => $this->userId,
                'capsule_id' => $capsuleId,
                'requested_count' => $count,
                'generated_count' => count($generatedOutfits)
            ]);
            
            $this->success([
                'outfits' => $generatedOutfits,
                'count' => count($generatedOutfits)
            ], 'Образы успешно сгенерированы');
        } catch (\Exception $e) {
            Logger::error('Ошибка при генерации образов из капсулы', [
                'user_id' => $this->userId,
                'capsule_id' => $capsuleId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->error('Ошибка при генерации образов: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Сохранить сгенерированный образ
     */
    public function saveGenerated(int $capsuleId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error('Метод не разрешен', 405);
            return;
        }

        $outfitData = [
            'name' => trim($this->input('name', '')),
            'description' => trim($this->input('description', '')),
            'formality_level' => $this->input('formality_level') ? (int) $this->input('formality_level') : null,
            'season_id' => $this->input('season_id') ? (int) $this->input('season_id') : null,
            'is_favorite' => $this->input('is_favorite') === '1' || $this->input('is_favorite') === true,
            'item_ids' => $this->input('item_ids') ? (is_array($this->input('item_ids')) ? $this->input('item_ids') : explode(',', $this->input('item_ids'))) : [],
            'tag_ids' => $this->input('tag_ids') ? (is_array($this->input('tag_ids')) ? array_map('intval', $this->input('tag_ids')) : array_map('intval', explode(',', $this->input('tag_ids')))) : []
        ];

        if (empty($outfitData['name'])) {
            $this->error('Название образа обязательно', 400);
            return;
        }

        Logger::info('Сохранение сгенерированного образа', [
            'user_id' => $this->userId,
            'capsule_id' => $capsuleId,
            'outfit_name' => $outfitData['name'],
            'items_count' => count($outfitData['item_ids'] ?? [])
        ]);

        try {
            $outfitId = $this->outfitModel->saveGeneratedOutfit($this->userId, $outfitData);
            
            // Связываем образ с капсулой
            $capsuleModel = new Capsule();
            $capsuleModel->linkOutfitToCapsule($capsuleId, $outfitId);

            Logger::info('Сгенерированный образ успешно сохранен', [
                'user_id' => $this->userId,
                'capsule_id' => $capsuleId,
                'outfit_id' => $outfitId
            ]);

            $outfit = $this->outfitModel->getWithDetails($outfitId, $this->userId);
            
            $this->success([
                'id' => $outfitId,
                'outfit' => $outfit
            ], 'Образ успешно сохранен', 201);
        } catch (\Exception $e) {
            Logger::error('Ошибка при сохранении сгенерированного образа', [
                'user_id' => $this->userId,
                'capsule_id' => $capsuleId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->error('Ошибка при сохранении образа: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Добавить вещь в образ
     */
    public function addItem(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error('Метод не разрешен', 405);
            return;
        }

        $itemId = (int) $this->input('item_id');
        $position = $this->input('position') !== null ? (int) $this->input('position') : null;

        if (!$itemId) {
            $this->error('ID вещи обязателен', 400);
            return;
        }

        // Проверяем права на образ
        $outfit = $this->outfitModel->find($id);
        if (!$outfit || $outfit['user_id'] != $this->userId) {
            $this->error('Образ не найден', 404);
            return;
        }

        // Проверяем права на вещь
        $itemModel = new Item();
        $item = $itemModel->find($itemId);
        if (!$item || $item['user_id'] != $this->userId) {
            $this->error('Вещь не найдена', 404);
            return;
        }

        try {
            $success = $this->outfitModel->addItem($id, $itemId, $position);

            if (!$success) {
                $this->error('Вещь уже добавлена в образ', 400);
                return;
            }

            $outfit = $this->outfitModel->getWithDetails($id, $this->userId);
            $this->success($outfit, 'Вещь успешно добавлена в образ');
        } catch (\Exception $e) {
            $this->error('Ошибка при добавлении вещи: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Удалить вещь из образа
     */
    public function removeItem(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->error('Метод не разрешен', 405);
            return;
        }

        $itemId = (int) $this->input('item_id');

        if (!$itemId) {
            $this->error('ID вещи обязателен', 400);
            return;
        }

        // Проверяем права на образ
        $outfit = $this->outfitModel->find($id);
        if (!$outfit || $outfit['user_id'] != $this->userId) {
            $this->error('Образ не найден', 404);
            return;
        }

        try {
            $success = $this->outfitModel->removeItem($id, $itemId);

            if (!$success) {
                $this->error('Не удалось удалить вещь из образа', 400);
                return;
            }

            $outfit = $this->outfitModel->getWithDetails($id, $this->userId);
            $this->success($outfit, 'Вещь успешно удалена из образа');
        } catch (\Exception $e) {
            $this->error('Ошибка при удалении вещи: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Валидация данных образа
     */
    private function validateOutfitData(array $data, int $outfitId = null): array
    {
        $errors = [];

        // Название
        if (empty($data['name'])) {
            $errors['name'][] = 'Название образа обязательно для заполнения';
        } elseif (strlen($data['name']) > 200) {
            $errors['name'][] = 'Название не должно превышать 200 символов';
        }

        // Описание (опционально)
        if (!empty($data['description']) && strlen($data['description']) > 1000) {
            $errors['description'][] = 'Описание не должно превышать 1000 символов';
        }

        // Уровень формальности (опционально)
        if (!empty($data['formality_level'])) {
            $formalityLevel = (int) $data['formality_level'];
            if ($formalityLevel < 1 || $formalityLevel > 5) {
                $errors['formality_level'][] = 'Уровень формальности должен быть от 1 до 5';
            }
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
