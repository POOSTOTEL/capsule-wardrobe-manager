<?php
// app/Models/Tag.php

namespace App\Models;

use App\Core\Database;

class Tag extends BaseModel
{
    protected $table = 'tags';
    protected $fillable = ['user_id', 'name', 'color', 'is_system'];

    // Получить все теги пользователя
    public function getByUser(int $userId): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE (user_id = :user_id OR is_system = true) 
                ORDER BY is_system DESC, name ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Создать пользовательский тег
    public function createUserTag(int $userId, array $data): ?int
    {
        // Проверяем, существует ли уже такой тег у пользователя
        // Учитываем уникальность по (user_id, name) согласно схеме БД
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id AND LOWER(name) = LOWER(:name)
                LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'name' => trim($data['name'])
            ]);
            $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($existing) {
                return null; // Тег уже существует у этого пользователя
            }
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }

        $data['user_id'] = $userId;
        $data['is_system'] = false;
        $data['name'] = trim($data['name']);

        // Если цвет не указан, генерируем случайный
        if (empty($data['color'])) {
            $data['color'] = $this->generateRandomColor();
        }

        try {
            return $this->create($data);
        } catch (\PDOException $e) {
            // Если ошибка уникальности - тег уже существует
            if (strpos($e->getMessage(), 'unique') !== false || 
                strpos($e->getMessage(), 'duplicate') !== false) {
                return null;
            }
            throw $e;
        }
    }

    // Получить системные теги
    public function getSystemTags(): array
    {
        return $this->where('is_system', true);
    }

    // Получить пользовательские теги
    public function getUserTags(int $userId): array
    {
        return $this->where('user_id', $userId);
    }

    // Получить теги для определенной вещи
    public function getForItem(int $itemId): array
    {
        $sql = "SELECT t.* FROM tags t
                JOIN item_tags it ON t.id = it.tag_id
                WHERE it.item_id = :item_id
                ORDER BY t.name";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['item_id' => $itemId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Получить теги для определенного образа
    public function getForOutfit(int $outfitId): array
    {
        $sql = "SELECT t.* FROM tags t
                JOIN outfit_tags ot ON t.id = ot.tag_id
                WHERE ot.outfit_id = :outfit_id
                ORDER BY t.name";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['outfit_id' => $outfitId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Получить теги для формы выбора (с группировкой)
    public function getForSelectGrouped(int $userId): array
    {
        $allTags = $this->getByUser($userId);
        $grouped = [
            'system' => [],
            'user' => []
        ];

        foreach ($allTags as $tag) {
            if ($tag['is_system']) {
                $grouped['system'][$tag['id']] = $tag['name'];
            } else {
                $grouped['user'][$tag['id']] = $tag['name'];
            }
        }

        return $grouped;
    }

    // Обновить тег
    public function updateTag(int $tagId, array $data): bool
    {
        // Проверяем, не конфликтует ли новое имя с существующим тегом того же пользователя
        if (isset($data['name'])) {
            $tag = $this->find($tagId);
            if (!$tag) {
                return false;
            }
            
            $userId = $tag['user_id'];
            $newName = trim($data['name']);
            
            // Проверяем, не существует ли уже тег с таким именем у этого пользователя
            // Для системных тегов user_id может быть NULL
            if ($userId === null) {
                $sql = "SELECT * FROM {$this->table} 
                        WHERE user_id IS NULL 
                        AND LOWER(name) = LOWER(:name)
                        AND id != :id
                        LIMIT 1";
                $params = [
                    'name' => $newName,
                    'id' => $tagId
                ];
            } else {
                $sql = "SELECT * FROM {$this->table} 
                        WHERE user_id = :user_id 
                        AND LOWER(name) = LOWER(:name)
                        AND id != :id
                        LIMIT 1";
                $params = [
                    'user_id' => $userId,
                    'name' => $newName,
                    'id' => $tagId
                ];
            }
            
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($existing) {
                    return false; // Тег с таким именем уже существует
                }
            } catch (\PDOException $e) {
                throw new \RuntimeException("Database error: " . $e->getMessage());
            }
            
            $data['name'] = $newName;
        }
        
        try {
            return $this->update($tagId, $data);
        } catch (\PDOException $e) {
            // Если ошибка уникальности - тег уже существует
            if (strpos($e->getMessage(), 'unique') !== false || 
                strpos($e->getMessage(), 'duplicate') !== false) {
                return false;
            }
            throw $e;
        }
    }

    // Удалить тег (только пользовательский)
    public function deleteUserTag(int $tagId, int $userId): bool
    {
        $tag = $this->find($tagId);

        if (!$tag || $tag['is_system'] || $tag['user_id'] != $userId) {
            return false;
        }

        return $this->delete($tagId);
    }

    // Поиск тегов по имени (с автодополнением)
    public function searchByName(string $query, int $userId, int $limit = 10): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE (user_id = :user_id OR is_system = true) 
                AND LOWER(name) LIKE LOWER(:query)
                ORDER BY 
                    CASE 
                        WHEN LOWER(name) = LOWER(:exact_query) THEN 1
                        WHEN LOWER(name) LIKE LOWER(:start_query) THEN 2
                        ELSE 3
                    END,
                    name
                LIMIT :limit";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'query' => "%{$query}%",
                'exact_query' => $query,
                'start_query' => "{$query}%",
                'limit' => $limit
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Получить самые популярные теги пользователя
    public function getPopularTags(int $userId, int $limit = 10): array
    {
        $sql = "SELECT t.*, COUNT(it.item_id) as usage_count 
                FROM tags t
                LEFT JOIN item_tags it ON t.id = it.tag_id
                WHERE t.user_id = :user_id OR t.is_system = true
                GROUP BY t.id
                ORDER BY usage_count DESC, t.name
                LIMIT :limit";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'limit' => $limit
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Привязать тег к вещи
    public function attachToItem(int $tagId, int $itemId): bool
    {
        $sql = "INSERT INTO item_tags (item_id, tag_id) 
                VALUES (:item_id, :tag_id)
                ON CONFLICT (item_id, tag_id) DO NOTHING";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'item_id' => $itemId,
                'tag_id' => $tagId
            ]);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Отвязать тег от вещи
    public function detachFromItem(int $tagId, int $itemId): bool
    {
        $sql = "DELETE FROM item_tags 
                WHERE item_id = :item_id AND tag_id = :tag_id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'item_id' => $itemId,
                'tag_id' => $tagId
            ]);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Получить все вещи с определенным тегом
    public function getItemsWithTag(int $tagId, int $userId): array
    {
        $sql = "SELECT i.* FROM items i
                JOIN item_tags it ON i.id = it.item_id
                WHERE it.tag_id = :tag_id AND i.user_id = :user_id
                ORDER BY i.name";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'tag_id' => $tagId,
                'user_id' => $userId
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Генерация случайного цвета для тега
    private function generateRandomColor(): string
    {
        $colors = [
            '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
            '#EC4899', '#06B6D4', '#84CC16', '#F97316', '#6366F1',
            '#A855F7', '#D946EF', '#0EA5E9', '#22C55E', '#EAB308'
        ];

        return $colors[array_rand($colors)];
    }
}