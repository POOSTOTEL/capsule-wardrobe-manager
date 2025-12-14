<?php
// app/Controllers/TagController.php

namespace App\Controllers;

use App\Models\Tag;
use App\Middleware\AuthMiddleware;

class TagController extends Controller
{
    protected $tagModel;
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

        $this->tagModel = new Tag();
        $this->userId = $_SESSION['user_id'] ?? null;
    }

    // Получить все теги пользователя (JSON)
    public function index(): void
    {
        $tags = $this->tagModel->getByUser($this->userId);

        $this->json([
            'success' => true,
            'data' => $tags,
            'count' => count($tags)
        ]);
    }

    // Получить теги с группировкой
    public function grouped(): void
    {
        $groupedTags = $this->tagModel->getForSelectGrouped($this->userId);

        $this->json([
            'success' => true,
            'data' => $groupedTags
        ]);
    }

    // Поиск тегов (для автодополнения)
    public function search(): void
    {
        $query = $this->input('query', '');

        if (strlen($query) < 2) {
            $this->json([
                'success' => true,
                'data' => [],
                'message' => 'Минимум 2 символа для поиска'
            ]);
            return;
        }

        $tags = $this->tagModel->searchByName($query, $this->userId);

        $this->json([
            'success' => true,
            'data' => $tags,
            'query' => $query
        ]);
    }

    // Создать новый тег
    public function store(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method !== 'POST') {
            $this->json([
                'success' => false,
                'message' => 'Метод не разрешен'
            ], 405);
            return;
        }

        $name = trim($this->input('name', ''));
        $color = trim($this->input('color', ''));

        // Валидация
        if (empty($name)) {
            $this->json([
                'success' => false,
                'message' => 'Название тега не может быть пустым'
            ], 400);
            return;
        }

        if (strlen($name) > 50) {
            $this->json([
                'success' => false,
                'message' => 'Название тега не должно превышать 50 символов'
            ], 400);
            return;
        }

        try {
            $tagId = $this->tagModel->createUserTag($this->userId, [
                'name' => $name,
                'color' => $color
            ]);

            if ($tagId === null || $tagId === 0) {
                $this->json([
                    'success' => false,
                    'message' => 'Тег с таким названием уже существует'
                ], 409);
                return;
            }

            $newTag = $this->tagModel->find($tagId);

            if (!$newTag) {
                $this->json([
                    'success' => false,
                    'message' => 'Тег создан, но не найден'
                ], 500);
                return;
            }

            $this->json([
                'success' => true,
                'message' => 'Тег успешно создан',
                'data' => $newTag
            ], 201);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Ошибка при создании тега: ' . $e->getMessage()
            ], 500);
        }
    }

    // Обновить тег
    public function update(int $id): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method !== 'POST' && $method !== 'PUT' && $method !== 'PATCH') {
            $this->json([
                'success' => false,
                'message' => 'Метод не разрешен'
            ], 405);
            return;
        }

        $tag = $this->tagModel->find($id);

        if (!$tag) {
            $this->json([
                'success' => false,
                'message' => 'Тег не найден'
            ], 404);
            return;
        }

        // Проверяем права доступа
        if ($tag['is_system'] || ($tag['user_id'] != $this->userId && !$tag['is_system'])) {
            $this->json([
                'success' => false,
                'message' => 'Нет прав для редактирования этого тега'
            ], 403);
            return;
        }

        // Получаем данные из запроса (поддерживает JSON и form-data)
        $name = trim($this->input('name', ''));
        $color = trim($this->input('color', ''));

        if (empty($name)) {
            $this->json([
                'success' => false,
                'message' => 'Название тега не может быть пустым'
            ], 400);
            return;
        }

        if (strlen($name) > 50) {
            $this->json([
                'success' => false,
                'message' => 'Название тега не должно превышать 50 символов'
            ], 400);
            return;
        }

        // Подготавливаем данные для обновления
        $updateData = ['name' => $name];
        if (!empty($color)) {
            $updateData['color'] = $color;
        }

        $success = $this->tagModel->updateTag($id, $updateData);

        if (!$success) {
            $this->json([
                'success' => false,
                'message' => 'Ошибка при обновлении тега. Возможно, тег с таким названием уже существует'
            ], 400);
            return;
        }

        $updatedTag = $this->tagModel->find($id);

        if (!$updatedTag) {
            $this->json([
                'success' => false,
                'message' => 'Тег не найден после обновления'
            ], 500);
            return;
        }

        $this->json([
            'success' => true,
            'message' => 'Тег успешно обновлен',
            'data' => $updatedTag
        ]);
    }

    // Удалить тег
    public function destroy(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->json([
                'success' => false,
                'message' => 'Метод не разрешен'
            ], 405);
            return;
        }

        $success = $this->tagModel->deleteUserTag($id, $this->userId);

        if (!$success) {
            $this->json([
                'success' => false,
                'message' => 'Не удалось удалить тег. Возможно, тег не существует или у вас нет прав'
            ], 400);
            return;
        }

        $this->json([
            'success' => true,
            'message' => 'Тег успешно удален'
        ]);
    }

    // Получить популярные теги
    public function popular(): void
    {
        $limit = (int) $this->input('limit', 10);
        $tags = $this->tagModel->getPopularTags($this->userId, $limit);

        $this->json([
            'success' => true,
            'data' => $tags
        ]);
    }

    // Получить теги для вещи
    public function forItem(int $itemId): void
    {
        $tags = $this->tagModel->getForItem($itemId);

        $this->json([
            'success' => true,
            'data' => $tags,
            'item_id' => $itemId
        ]);
    }

    // Привязать тег к вещи
    public function attachToItem(int $itemId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json([
                'success' => false,
                'message' => 'Метод не разрешен'
            ], 405);
            return;
        }

        $tagId = (int) $this->input('tag_id');

        if (!$tagId) {
            $this->json([
                'success' => false,
                'message' => 'Не указан ID тега'
            ], 400);
            return;
        }

        $success = $this->tagModel->attachToItem($tagId, $itemId);

        if (!$success) {
            $this->json([
                'success' => false,
                'message' => 'Не удалось привязать тег'
            ], 500);
            return;
        }

        $this->json([
            'success' => true,
            'message' => 'Тег успешно привязан к вещи'
        ]);
    }

    // Отвязать тег от вещи
    public function detachFromItem(int $itemId, int $tagId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->json([
                'success' => false,
                'message' => 'Метод не разрешен'
            ], 405);
            return;
        }

        $success = $this->tagModel->detachFromItem($tagId, $itemId);

        if (!$success) {
            $this->json([
                'success' => false,
                'message' => 'Не удалось отвязать тег'
            ], 500);
            return;
        }

        $this->json([
            'success' => true,
            'message' => 'Тег успешно отвязан от вещи'
        ]);
    }

    // HTML страница управления тегами
    public function manage(): void
    {
        $tags = $this->tagModel->getByUser($this->userId);

        $data = [
            'title' => 'Управление тегами - Капсульный Гардероб',
            'tags' => $tags,
            'styles' => ['/assets/css/tags.css']
        ];

        $this->render('tags/manage', $data);
    }
}