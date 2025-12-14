<?php
// app/Controllers/ItemController.php

namespace App\Controllers;

use App\Models\Item;
use App\Models\Category;
use App\Models\Color;
use App\Models\Season;
use App\Models\Tag;
use App\Middleware\AuthMiddleware;
use App\Utils\Logger;

class ItemController extends Controller
{
    protected $itemModel;
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

        $this->itemModel = new Item();
        $this->userId = $_SESSION['user_id'] ?? null;
    }

    // Список всех вещей пользователя
    public function index(): void
    {
        // Получаем фильтры из запроса
        $filters = [
            'category_id' => $this->input('category_id'),
            'color_id' => $this->input('color_id'),
            'season_id' => $this->input('season_id'),
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

        if ($this->isAjax()) {
            $items = $this->itemModel->getByUser($this->userId, $filters);
            
            // Удаляем бинарные данные изображений из ответа для всех вещей
            foreach ($items as &$item) {
                if (isset($item['image_data'])) {
                    unset($item['image_data']);
                    $item['image_url'] = "/api/items/{$item['id']}/image";
                }
            }
            unset($item); // Сбрасываем ссылку
            
            $this->json([
                'success' => true,
                'data' => $items,
                'count' => count($items)
            ]);
            return;
        }

        // Загружаем справочники для фильтров
        $categoryModel = new Category();
        $colorModel = new Color();
        $seasonModel = new Season();
        $tagModel = new Tag();

        $data = [
            'title' => 'Мой гардероб - Капсульный Гардероб',
            'items' => $this->itemModel->getByUser($this->userId, $filters),
            'categories' => $categoryModel->getAllSorted(),
            'colors' => $colorModel->getWithHexCodes(),
            'seasons' => $seasonModel->getAllSorted(),
            'tags' => $tagModel->getByUser($this->userId),
            'filters' => $filters,
            'styles' => ['/assets/css/items.css']
        ];

        $this->render('items/index', $data);
    }

    // Показать детальную информацию о вещи
    public function show(int $id): void
    {
        $item = $this->itemModel->getWithDetails($id, $this->userId);

        if (!$item) {
            $this->setFlash('error', 'Вещь не найдена');
            $this->redirect('/items');
            return;
        }

        if ($this->isAjax()) {
            // Удаляем бинарные данные изображения из ответа
            if ($item && isset($item['image_data'])) {
                unset($item['image_data']);
                $item['image_url'] = "/api/items/{$id}/image";
            }
            
            $this->json([
                'success' => true,
                'data' => $item
            ]);
            return;
        }

        $data = [
            'title' => htmlspecialchars($item['name']) . ' - Капсульный Гардероб',
            'item' => $item,
            'styles' => ['/assets/css/items.css']
        ];

        $this->render('items/show', $data);
    }

    // Форма создания новой вещи
    public function create(): void
    {
        $categoryModel = new Category();
        $colorModel = new Color();
        $seasonModel = new Season();
        $tagModel = new Tag();

        $data = [
            'title' => 'Добавить вещь - Капсульный Гардероб',
            'categories' => $categoryModel->getAllSorted(),
            'colors' => $colorModel->getWithHexCodes(),
            'seasons' => $seasonModel->getAllSorted(),
            'tags' => $tagModel->getByUser($this->userId),
            'styles' => ['/assets/css/items.css']
        ];

        $this->render('items/create', $data);
    }

    // Сохранить новую вещь
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error('Метод не разрешен', 405);
            return;
        }

        // Валидация
        $errors = $this->validateItemData($this->input());

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
            $this->redirect('/items/create');
            return;
        }

        // Проверяем загрузку изображения
        $imageFile = $this->file('image');
        if (!$imageFile || $imageFile['error'] !== UPLOAD_ERR_OK) {
            $message = 'Ошибка загрузки изображения';
            if ($this->isAjax()) {
                $this->error($message, 400);
                return;
            }
            $this->setFlash('error', $message);
            $this->redirect('/items/create');
            return;
        }

        // Валидация изображения
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        // Определяем MIME тип
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $imageFile['tmp_name']);
        finfo_close($finfo);
        
        // Fallback на mime_content_type если finfo недоступен
        if (!$mimeType) {
            $mimeType = mime_content_type($imageFile['tmp_name']);
        }
        
        if (!in_array($mimeType, $allowedTypes)) {
            $message = 'Неподдерживаемый тип изображения. Разрешены: JPEG, PNG, GIF, WebP';
            if ($this->isAjax()) {
                $this->error($message, 400);
                return;
            }
            $this->setFlash('error', $message);
            $this->redirect('/items/create');
            return;
        }

        // Подготавливаем данные
        $data = [
            'name' => trim($this->input('name')),
            'category_id' => (int) $this->input('category_id'),
            'color_id' => $this->input('color_id') ? (int) $this->input('color_id') : null,
            'season_id' => $this->input('season_id') ? (int) $this->input('season_id') : null,
            'notes' => trim($this->input('notes', '')),
            'tag_ids' => $this->input('tags') ? explode(',', $this->input('tags')) : []
        ];

        // Логируем начало создания вещи
        Logger::info('Начало создания вещи', [
            'user_id' => $this->userId,
            'data' => array_merge($data, ['tag_ids_count' => count($data['tag_ids'])]),
            'image_size' => $imageFile['size'] ?? 0,
            'image_type' => $mimeType ?? 'unknown'
        ]);

        try {
            $itemId = $this->itemModel->createWithImage($this->userId, $data, $imageFile['tmp_name']);

            Logger::info('Вещь успешно создана', [
                'user_id' => $this->userId,
                'item_id' => $itemId
            ]);

            if ($this->isAjax()) {
                // Упрощенный ответ - только ID и сообщение
                // Это предотвращает проблемы с сериализацией больших объектов
                $this->success([
                    'id' => $itemId,
                    'redirect_url' => '/items'
                ], 'Вещь успешно добавлена', 201);
                return;
            }

            $this->setFlash('success', 'Вещь успешно добавлена');
            $this->redirect('/items');
        } catch (\Exception $e) {
            // Детальное логирование ошибки
            Logger::error('Ошибка при создании вещи', [
                'user_id' => $this->userId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);

            // Упрощенное сообщение об ошибке для пользователя
            $message = 'Ошибка при создании вещи. Попробуйте еще раз.';
            
            if ($this->isAjax()) {
                // Всегда возвращаем JSON для AJAX запросов
                $this->error($message, 500);
                return;
            }
            
            $this->setFlash('error', $message);
            $this->redirect('/items/create');
        }
    }

    // Форма редактирования вещи
    public function edit(int $id): void
    {
        $item = $this->itemModel->getWithDetails($id, $this->userId);

        if (!$item) {
            $this->setFlash('error', 'Вещь не найдена');
            $this->redirect('/items');
            return;
        }

        $categoryModel = new Category();
        $colorModel = new Color();
        $seasonModel = new Season();
        $tagModel = new Tag();

        $data = [
            'title' => 'Редактировать вещь - Капсульный Гардероб',
            'item' => $item,
            'categories' => $categoryModel->getAllSorted(),
            'colors' => $colorModel->getWithHexCodes(),
            'seasons' => $seasonModel->getAllSorted(),
            'tags' => $tagModel->getByUser($this->userId),
            'selectedTagIds' => array_column($item['tags'], 'id'),
            'styles' => ['/assets/css/items.css']
        ];

        $this->render('items/edit', $data);
    }

    // Обновить вещь
    public function update(int $id): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $this->error('Метод не разрешен', 405);
            return;
        }

        $item = $this->itemModel->find($id);
        if (!$item || $item['user_id'] != $this->userId) {
            $this->error('Вещь не найдена', 404);
            return;
        }

        // Валидация
        $inputData = $this->input();
        $errors = $this->validateItemData($inputData, $id);

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
            $this->redirect("/items/{$id}/edit");
            return;
        }

        // Подготавливаем данные
        $data = [
            'name' => trim($this->input('name')),
            'category_id' => (int) $this->input('category_id'),
            'color_id' => $this->input('color_id') ? (int) $this->input('color_id') : null,
            'season_id' => $this->input('season_id') ? (int) $this->input('season_id') : null,
            'notes' => trim($this->input('notes', '')),
            'tag_ids' => $this->input('tags') ? explode(',', $this->input('tags')) : []
        ];

        // Обрабатываем новое изображение, если загружено
        $imageFile = $this->file('image');
        $imagePath = null;

        if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            // Определяем MIME тип
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $imageFile['tmp_name']);
            finfo_close($finfo);
            
            // Fallback на mime_content_type если finfo недоступен
            if (!$mimeType) {
                $mimeType = mime_content_type($imageFile['tmp_name']);
            }
            
            if (!in_array($mimeType, $allowedTypes)) {
                $message = 'Неподдерживаемый тип изображения. Разрешены: JPEG, PNG, GIF, WebP';
                if ($this->isAjax()) {
                    $this->error($message, 400);
                    return;
                }
                $this->setFlash('error', $message);
                $this->redirect("/items/{$id}/edit");
                return;
            }
            $imagePath = $imageFile['tmp_name'];
        }

        try {
            $success = $this->itemModel->updateItem($id, $this->userId, $data, $imagePath);

            if (!$success) {
                throw new \RuntimeException('Не удалось обновить вещь');
            }

            if ($this->isAjax()) {
                $item = $this->itemModel->getWithDetails($id, $this->userId);
                
                // Удаляем бинарные данные изображения из ответа
                if ($item && isset($item['image_data'])) {
                    unset($item['image_data']);
                    $item['image_url'] = "/api/items/{$id}/image";
                }
                
                $this->success($item, 'Вещь успешно обновлена');
                return;
            }

            $this->setFlash('success', 'Вещь успешно обновлена');
            $this->redirect("/items/{$id}");
        } catch (\Exception $e) {
            $message = 'Ошибка при обновлении вещи: ' . $e->getMessage();
            if ($this->isAjax()) {
                $this->error($message, 500);
                return;
            }
            $this->setFlash('error', $message);
            $this->redirect("/items/{$id}/edit");
        }
    }

    // Удалить вещь
    public function destroy(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->error('Метод не разрешен', 405);
            return;
        }

        $success = $this->itemModel->deleteItem($id, $this->userId);

        if (!$success) {
            $this->error('Не удалось удалить вещь. Возможно, вещь не существует или у вас нет прав', 400);
            return;
        }

        if ($this->isAjax()) {
            $this->success(null, 'Вещь успешно удалена');
            return;
        }

        $this->setFlash('success', 'Вещь успешно удалена');
        $this->redirect('/items');
    }

    // Получить изображение вещи (API endpoint)
    public function getImage(int $id): void
    {
        try {
            // Используем SQL функцию encode для получения BYTEA в hex-формате
            // Это гарантирует правильную обработку бинарных данных
            $sql = "SELECT encode(image_data, 'hex') as image_data_hex, 
                           image_mime_type, 
                           user_id 
                    FROM items 
                    WHERE id = :id AND user_id = :user_id";
            
            $db = \App\Core\Database::getInstance();
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $id, 'user_id' => $this->userId]);
            $item = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$item || empty($item['image_data_hex'])) {
                http_response_code(404);
                exit();
            }

            // Конвертируем hex-строку в бинарные данные
            $imageData = hex2bin($item['image_data_hex']);
            
            // Проверяем, что данные не пустые после обработки
            if (empty($imageData)) {
                Logger::error('getImage: пустые данные после обработки', [
                    'item_id' => $id,
                    'user_id' => $this->userId,
                    'hex_length' => strlen($item['image_data_hex'] ?? '')
                ]);
                http_response_code(404);
                exit();
            }

            // Устанавливаем заголовки
            header('Content-Type: ' . ($item['image_mime_type'] ?? 'image/jpeg'));
            header('Content-Length: ' . strlen($imageData));
            header('Cache-Control: public, max-age=31536000');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
            
            echo $imageData;
            exit();
        } catch (\Exception $e) {
            Logger::error('getImage: ошибка при получении изображения', [
                'item_id' => $id,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            http_response_code(500);
            exit();
        }
    }

    // Валидация данных вещи
    private function validateItemData(array $data, int $itemId = null): array
    {
        $errors = [];

        // Название
        if (empty($data['name'])) {
            $errors['name'][] = 'Название вещи обязательно для заполнения';
        } elseif (strlen($data['name']) > 200) {
            $errors['name'][] = 'Название не должно превышать 200 символов';
        }

        // Категория
        if (empty($data['category_id'])) {
            $errors['category_id'][] = 'Категория обязательна для выбора';
        } else {
            $categoryModel = new Category();
            $category = $categoryModel->find((int) $data['category_id']);
            if (!$category) {
                $errors['category_id'][] = 'Выбранная категория не существует';
            }
        }

        // Цвет (опционально)
        if (!empty($data['color_id'])) {
            $colorModel = new Color();
            $color = $colorModel->find((int) $data['color_id']);
            if (!$color) {
                $errors['color_id'][] = 'Выбранный цвет не существует';
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

        // Заметки (опционально)
        if (!empty($data['notes']) && strlen($data['notes']) > 1000) {
            $errors['notes'][] = 'Заметки не должны превышать 1000 символов';
        }

        return $errors;
    }
}
