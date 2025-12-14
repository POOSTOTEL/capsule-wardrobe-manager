<?php
// app/Controllers/TagController.php

namespace App\Controllers;

use App\Models\Tag;
use App\Middleware\AuthMiddleware;
use App\Utils\Logger;

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

    /**
     * Получить все теги пользователя (JSON)
     */
    public function index(): void
    {
        Logger::debug('Получение списка тегов', [
            'user_id' => $this->userId
        ]);

        try {
            $tags = $this->tagModel->getByUser($this->userId);

            Logger::info('Список тегов получен', [
                'user_id' => $this->userId,
                'count' => count($tags)
            ]);

            $this->json([
                'success' => true,
                'data' => $tags,
                'count' => count($tags)
            ]);
        } catch (\Exception $e) {
            Logger::error('Ошибка при получении списка тегов', [
                'user_id' => $this->userId,
                'message' => $e->getMessage()
            ]);
            $this->json([
                'success' => false,
                'message' => 'Ошибка при получении тегов'
            ], 500);
        }
    }

    /**
     * Получить теги с группировкой
     */
    public function grouped(): void
    {
        $groupedTags = $this->tagModel->getForSelectGrouped($this->userId);

        $this->json([
            'success' => true,
            'data' => $groupedTags
        ]);
    }

    /**
     * Поиск тегов (для автодополнения)
     */
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

    /**
     * Создать новый пользовательский тег
     */
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

        Logger::info('Создание нового тега', [
            'user_id' => $this->userId,
            'name' => $name,
            'color' => $color
        ]);

        try {
            $tagId = $this->tagModel->createUserTag($this->userId, [
                'name' => $name,
                'color' => $color
            ]);

            if ($tagId === null || $tagId === 0) {
                Logger::warning('Попытка создать дублирующий тег', [
                    'user_id' => $this->userId,
                    'name' => $name
                ]);
                $this->json([
                    'success' => false,
                    'message' => 'Тег с таким названием уже существует'
                ], 409);
                return;
            }

            $newTag = $this->tagModel->find($tagId);

            if (!$newTag) {
                Logger::error('Тег создан, но не найден после создания', [
                    'user_id' => $this->userId,
                    'tag_id' => $tagId
                ]);
                $this->json([
                    'success' => false,
                    'message' => 'Тег создан, но не найден'
                ], 500);
                return;
            }

            Logger::info('Тег успешно создан', [
                'user_id' => $this->userId,
                'tag_id' => $tagId,
                'tag_name' => $name
            ]);

            $this->json([
                'success' => true,
                'message' => 'Тег успешно создан',
                'data' => $newTag
            ], 201);
        } catch (\Exception $e) {
            Logger::error('Ошибка при создании тега', [
                'user_id' => $this->userId,
                'name' => $name,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->json([
                'success' => false,
                'message' => 'Ошибка при создании тега: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Обновить пользовательский тег
     */
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

        // Получаем данные из запроса
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

        Logger::info('Обновление тега', [
            'user_id' => $this->userId,
            'tag_id' => $id,
            'new_name' => $name
        ]);

        $success = $this->tagModel->updateUserTag($id, $this->userId, $updateData);

        if (!$success) {
            Logger::warning('Не удалось обновить тег', [
                'user_id' => $this->userId,
                'tag_id' => $id
            ]);
            $this->json([
                'success' => false,
                'message' => 'Ошибка при обновлении тега. Возможно, тег с таким названием уже существует'
            ], 400);
            return;
        }

        $updatedTag = $this->tagModel->find($id);

        if (!$updatedTag) {
            Logger::error('Тег не найден после обновления', [
                'user_id' => $this->userId,
                'tag_id' => $id
            ]);
            $this->json([
                'success' => false,
                'message' => 'Тег не найден после обновления'
            ], 500);
            return;
        }

        Logger::info('Тег успешно обновлен', [
            'user_id' => $this->userId,
            'tag_id' => $id
        ]);

        $this->json([
            'success' => true,
            'message' => 'Тег успешно обновлен',
            'data' => $updatedTag
        ]);
    }

    /**
     * Удалить пользовательский тег
     */
    public function destroy(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->json([
                'success' => false,
                'message' => 'Метод не разрешен'
            ], 405);
            return;
        }

        Logger::info('Попытка удаления тега', [
            'user_id' => $this->userId,
            'tag_id' => $id
        ]);

        $success = $this->tagModel->deleteUserTag($id, $this->userId);

        if (!$success) {
            Logger::warning('Не удалось удалить тег', [
                'user_id' => $this->userId,
                'tag_id' => $id
            ]);
            $this->json([
                'success' => false,
                'message' => 'Не удалось удалить тег. Возможно, тег не существует или у вас нет прав'
            ], 400);
            return;
        }

        Logger::info('Тег успешно удален', [
            'user_id' => $this->userId,
            'tag_id' => $id
        ]);

        $this->json([
            'success' => true,
            'message' => 'Тег успешно удален'
        ]);
    }

    /**
     * Получить популярные теги
     */
    public function popular(): void
    {
        $limit = (int) $this->input('limit', 10);
        $tags = $this->tagModel->getPopularTags($this->userId, $limit);

        $this->json([
            'success' => true,
            'data' => $tags
        ]);
    }

    /**
     * Получить теги для вещи
     */
    public function forItem(int $itemId): void
    {
        $tags = $this->tagModel->getForItem($itemId);

        $this->json([
            'success' => true,
            'data' => $tags,
            'item_id' => $itemId
        ]);
    }

    /**
     * Получить теги для образа
     */
    public function forOutfit(int $outfitId): void
    {
        $tags = $this->tagModel->getForOutfit($outfitId);

        $this->json([
            'success' => true,
            'data' => $tags,
            'outfit_id' => $outfitId
        ]);
    }

    /**
     * Привязать тег к вещи
     */
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

        Logger::debug('Привязка тега к вещи', [
            'user_id' => $this->userId,
            'tag_id' => $tagId,
            'item_id' => $itemId
        ]);

        $success = $this->tagModel->attachToItem($tagId, $itemId);

        if (!$success) {
            Logger::warning('Не удалось привязать тег к вещи', [
                'user_id' => $this->userId,
                'tag_id' => $tagId,
                'item_id' => $itemId
            ]);
            $this->json([
                'success' => false,
                'message' => 'Не удалось привязать тег'
            ], 500);
            return;
        }

        Logger::info('Тег успешно привязан к вещи', [
            'user_id' => $this->userId,
            'tag_id' => $tagId,
            'item_id' => $itemId
        ]);

        $this->json([
            'success' => true,
            'message' => 'Тег успешно привязан к вещи'
        ]);
    }

    /**
     * Отвязать тег от вещи
     */
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

    /**
     * Привязать тег к образу
     */
    public function attachToOutfit(int $outfitId): void
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

        Logger::debug('Привязка тега к образу', [
            'user_id' => $this->userId,
            'tag_id' => $tagId,
            'outfit_id' => $outfitId
        ]);

        $success = $this->tagModel->attachToOutfit($tagId, $outfitId);

        if (!$success) {
            Logger::warning('Не удалось привязать тег к образу', [
                'user_id' => $this->userId,
                'tag_id' => $tagId,
                'outfit_id' => $outfitId
            ]);
            $this->json([
                'success' => false,
                'message' => 'Не удалось привязать тег'
            ], 500);
            return;
        }

        Logger::info('Тег успешно привязан к образу', [
            'user_id' => $this->userId,
            'tag_id' => $tagId,
            'outfit_id' => $outfitId
        ]);

        $this->json([
            'success' => true,
            'message' => 'Тег успешно привязан к образу'
        ]);
    }

    /**
     * Отвязать тег от образа
     */
    public function detachFromOutfit(int $outfitId, int $tagId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->json([
                'success' => false,
                'message' => 'Метод не разрешен'
            ], 405);
            return;
        }

        $success = $this->tagModel->detachFromOutfit($tagId, $outfitId);

        if (!$success) {
            $this->json([
                'success' => false,
                'message' => 'Не удалось отвязать тег'
            ], 500);
            return;
        }

        $this->json([
            'success' => true,
            'message' => 'Тег успешно отвязан от образа'
        ]);
    }

    /**
     * HTML страница управления тегами
     */
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
