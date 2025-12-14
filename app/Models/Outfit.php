<?php
// app/Models/Outfit.php

namespace App\Models;

use App\Core\Database;
use App\Utils\Logger;
use PDO;
use PDOException;

class Outfit extends BaseModel
{
    protected $table = 'outfits';
    protected $fillable = [
        'user_id', 'name', 'description', 'formality_level', 
        'season_id', 'is_favorite'
    ];

    // Получить все образы пользователя с дополнительной информацией
    public function getByUser(int $userId, array $filters = []): array
    {
        $sql = "SELECT o.*, 
                       s.name as season_name,
                       COUNT(DISTINCT oi.item_id) as items_count
                FROM {$this->table} o
                LEFT JOIN seasons s ON o.season_id = s.id
                LEFT JOIN outfit_items oi ON o.id = oi.outfit_id
                WHERE o.user_id = :user_id";

        $params = ['user_id' => $userId];

        // Применяем фильтры
        if (!empty($filters['season_id'])) {
            $sql .= " AND o.season_id = :season_id";
            $params['season_id'] = $filters['season_id'];
        }

        if (!empty($filters['formality_level'])) {
            $sql .= " AND o.formality_level = :formality_level";
            $params['formality_level'] = $filters['formality_level'];
        }

        if (isset($filters['is_favorite']) && $filters['is_favorite'] !== '') {
            $sql .= " AND o.is_favorite = :is_favorite";
            $params['is_favorite'] = $filters['is_favorite'] === '1' || $filters['is_favorite'] === true;
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (LOWER(o.name) LIKE LOWER(:search) OR LOWER(o.description) LIKE LOWER(:search))";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        // Фильтр по тегам
        if (!empty($filters['tag_ids']) && is_array($filters['tag_ids'])) {
            $tagIds = array_filter(array_map('intval', $filters['tag_ids']));
            if (!empty($tagIds)) {
                $placeholders = implode(',', array_fill(0, count($tagIds), '?'));
                $sql .= " AND o.id IN (
                    SELECT outfit_id FROM outfit_tags 
                    WHERE tag_id IN ({$placeholders})
                    GROUP BY outfit_id
                    HAVING COUNT(DISTINCT tag_id) = ?
                )";
                $params = array_merge($params, $tagIds, [count($tagIds)]);
            }
        }

        // Группировка для подсчета вещей
        $sql .= " GROUP BY o.id, s.name";

        // Сортировка
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = strtoupper($filters['order_dir'] ?? 'DESC');
        
        // Безопасная сортировка
        $allowedOrderBy = ['created_at', 'updated_at', 'name', 'formality_level', 'is_favorite'];
        if (in_array($orderBy, $allowedOrderBy)) {
            $sql .= " ORDER BY o.{$orderBy} {$orderDir}";
        } else {
            $sql .= " ORDER BY o.created_at {$orderDir}";
        }

        // Пагинация
        if (!empty($filters['limit'])) {
            $limit = (int) $filters['limit'];
            $offset = (int) ($filters['offset'] ?? 0);
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $outfits = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Загружаем теги и вещи для каждого образа
            foreach ($outfits as &$outfit) {
                $outfit['tags'] = $this->getTags($outfit['id']);
                $outfit['items'] = $this->getItems($outfit['id']);
            }

            return $outfits;
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Получить образ с полной информацией
    public function getWithDetails(int $id, int $userId = null): ?array
    {
        $sql = "SELECT o.*, 
                       s.name as season_name
                FROM {$this->table} o
                LEFT JOIN seasons s ON o.season_id = s.id
                WHERE o.id = :id";

        $params = ['id' => $id];

        if ($userId !== null) {
            $sql .= " AND o.user_id = :user_id";
            $params['user_id'] = $userId;
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $outfit = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$outfit) {
                return null;
            }

            // Загружаем теги и вещи
            $outfit['tags'] = $this->getTags($id);
            $outfit['items'] = $this->getItems($id);

            return $outfit;
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Создать образ с вещами и тегами
    public function createOutfit(int $userId, array $data): int
    {
        Logger::debug('createOutfit: начало', [
            'user_id' => $userId,
            'data_keys' => array_keys($data)
        ]);

        // Подготавливаем данные для вставки
        $outfitData = [
            'user_id' => $userId,
            'name' => trim($data['name'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'formality_level' => isset($data['formality_level']) ? (int) $data['formality_level'] : null,
            'season_id' => !empty($data['season_id']) ? (int) $data['season_id'] : null,
            'is_favorite' => isset($data['is_favorite']) ? (bool) $data['is_favorite'] : false
        ];

        // Валидация formality_level (должен быть от 1 до 5)
        if ($outfitData['formality_level'] !== null) {
            if ($outfitData['formality_level'] < 1 || $outfitData['formality_level'] > 5) {
                throw new \RuntimeException("formality_level должен быть от 1 до 5");
            }
        }

        // Создаем образ
        $outfitId = $this->create($outfitData);

        Logger::info('createOutfit: образ создан в БД', ['outfit_id' => $outfitId]);

        // Привязываем вещи, если указаны
        if (!empty($data['item_ids']) && is_array($data['item_ids'])) {
            Logger::debug('createOutfit: добавление вещей', ['item_ids' => $data['item_ids']]);
            $this->syncItems($outfitId, $data['item_ids']);
        }

        // Привязываем теги, если указаны
        if (!empty($data['tag_ids']) && is_array($data['tag_ids'])) {
            Logger::debug('createOutfit: синхронизация тегов', ['tag_ids' => $data['tag_ids']]);
            $this->syncTags($outfitId, $data['tag_ids']);
        }

        return $outfitId;
    }

    // Обновить образ
    public function updateOutfit(int $id, int $userId, array $data): bool
    {
        // Проверяем права доступа
        $outfit = $this->find($id);
        if (!$outfit || $outfit['user_id'] != $userId) {
            return false;
        }

        // Подготавливаем данные для обновления
        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = trim($data['name']);
        }

        if (isset($data['description'])) {
            $updateData['description'] = trim($data['description']);
        }

        if (isset($data['formality_level'])) {
            $formalityLevel = (int) $data['formality_level'];
            if ($formalityLevel < 1 || $formalityLevel > 5) {
                throw new \RuntimeException("formality_level должен быть от 1 до 5");
            }
            $updateData['formality_level'] = $formalityLevel;
        }

        if (isset($data['season_id'])) {
            $updateData['season_id'] = !empty($data['season_id']) ? (int) $data['season_id'] : null;
        }

        if (isset($data['is_favorite'])) {
            $updateData['is_favorite'] = (bool) $data['is_favorite'];
        }

        // Обновляем образ
        $success = false;
        if (!empty($updateData)) {
            $success = $this->update($id, $updateData);
        }

        // Синхронизируем вещи, если указаны
        if (isset($data['item_ids']) && is_array($data['item_ids'])) {
            $this->syncItems($id, $data['item_ids']);
        }

        // Синхронизируем теги, если указаны
        if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
            $this->syncTags($id, $data['tag_ids']);
        }

        return $success;
    }

    // Удалить образ
    public function deleteOutfit(int $id, int $userId): bool
    {
        $outfit = $this->find($id);
        if (!$outfit || $outfit['user_id'] != $userId) {
            return false;
        }

        return $this->delete($id);
    }

    // Получить вещи образа
    public function getItems(int $outfitId): array
    {
        $sql = "SELECT i.*, 
                       oi.position,
                       c.name as category_name,
                       s.name as season_name,
                       cl.name as color_name,
                       cl.hex_code as color_hex
                FROM outfit_items oi
                JOIN items i ON oi.item_id = i.id
                LEFT JOIN categories c ON i.category_id = c.id
                LEFT JOIN seasons s ON i.season_id = s.id
                LEFT JOIN colors cl ON i.color_id = cl.id
                WHERE oi.outfit_id = :outfit_id
                ORDER BY oi.position ASC, i.category_id ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['outfit_id' => $outfitId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Загружаем теги для каждой вещи
            foreach ($items as &$item) {
                $item['tags'] = $this->getItemTags($item['id']);
                // Удаляем бинарные данные изображения
                if (isset($item['image_data'])) {
                    unset($item['image_data']);
                    $item['image_url'] = "/api/items/{$item['id']}/image";
                }
            }

            return $items;
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Получить теги образа
    public function getTags(int $outfitId): array
    {
        $sql = "SELECT t.* FROM tags t
                JOIN outfit_tags ot ON t.id = ot.tag_id
                WHERE ot.outfit_id = :outfit_id
                ORDER BY t.is_system DESC, t.name ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['outfit_id' => $outfitId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Получить теги вещи (вспомогательный метод)
    private function getItemTags(int $itemId): array
    {
        $sql = "SELECT t.* FROM tags t
                JOIN item_tags it ON t.id = it.tag_id
                WHERE it.item_id = :item_id
                ORDER BY t.is_system DESC, t.name ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['item_id' => $itemId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Синхронизировать вещи образа
    public function syncItems(int $outfitId, array $itemIds): void
    {
        // Удаляем все существующие связи
        $sql = "DELETE FROM outfit_items WHERE outfit_id = :outfit_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['outfit_id' => $outfitId]);

        // Добавляем новые связи с позициями
        if (!empty($itemIds)) {
            $itemIds = array_filter(array_map('intval', $itemIds));
            if (!empty($itemIds)) {
                $sql = "INSERT INTO outfit_items (outfit_id, item_id, position) VALUES ";
                $values = [];
                $params = ['outfit_id' => $outfitId];

                foreach ($itemIds as $index => $itemId) {
                    $key = "item_{$index}";
                    $values[] = "(:outfit_id, :{$key}, :pos_{$index})";
                    $params[$key] = $itemId;
                    $params["pos_{$index}"] = $index;
                }

                $sql .= implode(', ', $values);
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }
        }
    }

    // Синхронизировать теги образа
    public function syncTags(int $outfitId, array $tagIds): void
    {
        // Удаляем все существующие связи
        $sql = "DELETE FROM outfit_tags WHERE outfit_id = :outfit_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['outfit_id' => $outfitId]);

        // Добавляем новые связи
        if (!empty($tagIds)) {
            $tagIds = array_filter(array_map('intval', $tagIds));
            if (!empty($tagIds)) {
                $sql = "INSERT INTO outfit_tags (outfit_id, tag_id) VALUES ";
                $values = [];
                $params = ['outfit_id' => $outfitId];

                foreach ($tagIds as $index => $tagId) {
                    $key = "tag_{$index}";
                    $values[] = "(:outfit_id, :{$key})";
                    $params[$key] = $tagId;
                }

                $sql .= implode(', ', $values);
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }
        }
    }

    // Добавить вещь в образ
    public function addItem(int $outfitId, int $itemId, int $position = null): bool
    {
        // Проверяем, не добавлена ли уже эта вещь
        $sql = "SELECT * FROM outfit_items 
                WHERE outfit_id = :outfit_id AND item_id = :item_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['outfit_id' => $outfitId, 'item_id' => $itemId]);
        
        if ($stmt->fetch()) {
            return false; // Вещь уже добавлена
        }

        // Если позиция не указана, добавляем в конец
        if ($position === null) {
            $sql = "SELECT COALESCE(MAX(position), -1) + 1 as next_position 
                    FROM outfit_items WHERE outfit_id = :outfit_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['outfit_id' => $outfitId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $position = (int) $result['next_position'];
        }

        $sql = "INSERT INTO outfit_items (outfit_id, item_id, position) 
                VALUES (:outfit_id, :item_id, :position)";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'outfit_id' => $outfitId,
                'item_id' => $itemId,
                'position' => $position
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Удалить вещь из образа
    public function removeItem(int $outfitId, int $itemId): bool
    {
        $sql = "DELETE FROM outfit_items 
                WHERE outfit_id = :outfit_id AND item_id = :item_id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'outfit_id' => $outfitId,
                'item_id' => $itemId
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Переключить избранное
    public function toggleFavorite(int $id, int $userId): bool
    {
        $outfit = $this->find($id);
        if (!$outfit || $outfit['user_id'] != $userId) {
            return false;
        }

        $newValue = !$outfit['is_favorite'];
        return $this->update($id, ['is_favorite' => $newValue]);
    }

    // Получить статистику образов пользователя
    public function getStatistics(int $userId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_outfits,
                    COUNT(DISTINCT season_id) as seasons_count,
                    COUNT(CASE WHEN is_favorite = true THEN 1 END) as favorites_count,
                    AVG(formality_level) as avg_formality,
                    COUNT(DISTINCT oi.item_id) as unique_items_used
                FROM {$this->table} o
                LEFT JOIN outfit_items oi ON o.id = oi.outfit_id
                WHERE o.user_id = :user_id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Получить образы по сезонам (для аналитики)
    public function getBySeasons(int $userId): array
    {
        $sql = "SELECT s.id, s.name, COUNT(o.id) as count
                FROM seasons s
                LEFT JOIN {$this->table} o ON s.id = o.season_id AND o.user_id = :user_id
                GROUP BY s.id, s.name
                HAVING COUNT(o.id) > 0
                ORDER BY count DESC, s.name ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Получить образы по уровню формальности (для аналитики)
    public function getByFormalityLevel(int $userId): array
    {
        $sql = "SELECT formality_level, COUNT(*) as count
                FROM {$this->table}
                WHERE user_id = :user_id AND formality_level IS NOT NULL
                GROUP BY formality_level
                ORDER BY formality_level ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Получить общее количество образов пользователя
    public function getTotalCount(int $userId): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE user_id = :user_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) ($result['total'] ?? 0);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }
}
