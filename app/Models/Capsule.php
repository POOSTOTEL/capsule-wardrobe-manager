<?php
// app/Models/Capsule.php

namespace App\Models;

use App\Core\Database;
use App\Utils\Logger;
use PDO;
use PDOException;

class Capsule extends BaseModel
{
    protected $table = 'capsules';
    protected $fillable = [
        'user_id', 'name', 'description', 'season_id'
    ];

    // Получить все капсулы пользователя с дополнительной информацией
    public function getByUser(int $userId, array $filters = []): array
    {
        $sql = "SELECT c.*, 
                       s.name as season_name,
                       COUNT(DISTINCT ci.item_id) as items_count,
                       COUNT(DISTINCT co.outfit_id) as outfits_count
                FROM {$this->table} c
                LEFT JOIN seasons s ON c.season_id = s.id
                LEFT JOIN capsule_items ci ON c.id = ci.capsule_id
                LEFT JOIN capsule_outfits co ON c.id = co.capsule_id
                WHERE c.user_id = :user_id";

        $params = ['user_id' => $userId];

        // Применяем фильтры
        if (!empty($filters['season_id'])) {
            $sql .= " AND c.season_id = :season_id";
            $params['season_id'] = $filters['season_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (LOWER(c.name) LIKE LOWER(:search) OR LOWER(c.description) LIKE LOWER(:search))";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        // Группировка для подсчета
        $sql .= " GROUP BY c.id, s.name";

        // Сортировка
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = strtoupper($filters['order_dir'] ?? 'DESC');
        
        // Безопасная сортировка
        $allowedOrderBy = ['created_at', 'updated_at', 'name'];
        if (in_array($orderBy, $allowedOrderBy)) {
            $sql .= " ORDER BY c.{$orderBy} {$orderDir}";
        } else {
            $sql .= " ORDER BY c.created_at {$orderDir}";
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
            $capsules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Загружаем вещи и образы для каждой капсулы
            foreach ($capsules as &$capsule) {
                $capsule['items'] = $this->getItems($capsule['id']);
                $capsule['outfits'] = $this->getOutfits($capsule['id']);
            }

            return $capsules;
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Получить капсулу с полной информацией
    public function getWithDetails(int $id, int $userId = null): ?array
    {
        $sql = "SELECT c.*, 
                       s.name as season_name
                FROM {$this->table} c
                LEFT JOIN seasons s ON c.season_id = s.id
                WHERE c.id = :id";

        $params = ['id' => $id];

        if ($userId !== null) {
            $sql .= " AND c.user_id = :user_id";
            $params['user_id'] = $userId;
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $capsule = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$capsule) {
                return null;
            }

            // Загружаем вещи и образы
            $capsule['items'] = $this->getItems($id);
            $capsule['outfits'] = $this->getOutfits($id);

            return $capsule;
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Создать капсулу с вещами и образами
    public function createCapsule(int $userId, array $data): int
    {
        Logger::debug('createCapsule: начало', [
            'user_id' => $userId,
            'data_keys' => array_keys($data)
        ]);

        // Подготавливаем данные для вставки
        $capsuleData = [
            'user_id' => $userId,
            'name' => trim($data['name'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'season_id' => !empty($data['season_id']) ? (int) $data['season_id'] : null
        ];

        // Создаем капсулу
        $capsuleId = $this->create($capsuleData);

        Logger::info('createCapsule: капсула создана в БД', ['capsule_id' => $capsuleId]);

        // Привязываем вещи, если указаны
        if (!empty($data['item_ids']) && is_array($data['item_ids'])) {
            Logger::debug('createCapsule: добавление вещей', ['item_ids' => $data['item_ids']]);
            $this->syncItems($capsuleId, $data['item_ids']);
        }

        // Привязываем образы, если указаны
        if (!empty($data['outfit_ids']) && is_array($data['outfit_ids'])) {
            Logger::debug('createCapsule: добавление образов', ['outfit_ids' => $data['outfit_ids']]);
            $this->syncOutfits($capsuleId, $data['outfit_ids']);
        }

        return $capsuleId;
    }

    // Обновить капсулу
    public function updateCapsule(int $id, int $userId, array $data): bool
    {
        // Проверяем права доступа
        $capsule = $this->find($id);
        if (!$capsule || $capsule['user_id'] != $userId) {
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

        if (isset($data['season_id'])) {
            $updateData['season_id'] = !empty($data['season_id']) ? (int) $data['season_id'] : null;
        }

        // Обновляем капсулу
        $success = false;
        if (!empty($updateData)) {
            $success = $this->update($id, $updateData);
        }

        // Синхронизируем вещи, если указаны
        if (isset($data['item_ids']) && is_array($data['item_ids'])) {
            $this->syncItems($id, $data['item_ids']);
        }

        // Синхронизируем образы, если указаны
        if (isset($data['outfit_ids']) && is_array($data['outfit_ids'])) {
            $this->syncOutfits($id, $data['outfit_ids']);
        }

        return $success;
    }

    // Удалить капсулу
    public function deleteCapsule(int $id, int $userId): bool
    {
        $capsule = $this->find($id);
        if (!$capsule || $capsule['user_id'] != $userId) {
            return false;
        }

        return $this->delete($id);
    }

    // Получить вещи капсулы
    public function getItems(int $capsuleId): array
    {
        $sql = "SELECT i.*, 
                       c.name as category_name,
                       s.name as season_name,
                       cl.name as color_name,
                       cl.hex_code as color_hex
                FROM capsule_items ci
                JOIN items i ON ci.item_id = i.id
                LEFT JOIN categories c ON i.category_id = c.id
                LEFT JOIN seasons s ON i.season_id = s.id
                LEFT JOIN colors cl ON i.color_id = cl.id
                WHERE ci.capsule_id = :capsule_id
                ORDER BY ci.added_at ASC, i.category_id ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['capsule_id' => $capsuleId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Удаляем бинарные данные изображения
            foreach ($items as &$item) {
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

    // Получить образы капсулы
    public function getOutfits(int $capsuleId): array
    {
        $sql = "SELECT o.*, 
                       s.name as season_name
                FROM capsule_outfits co
                JOIN outfits o ON co.outfit_id = o.id
                LEFT JOIN seasons s ON o.season_id = s.id
                WHERE co.capsule_id = :capsule_id
                ORDER BY co.added_at ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['capsule_id' => $capsuleId]);
            $outfits = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Загружаем вещи для каждого образа
            foreach ($outfits as &$outfit) {
                $outfitModel = new Outfit();
                $outfit['items'] = $outfitModel->getItems($outfit['id']);
            }

            return $outfits;
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Синхронизировать вещи капсулы
    public function syncItems(int $capsuleId, array $itemIds): void
    {
        // Удаляем все существующие связи
        $sql = "DELETE FROM capsule_items WHERE capsule_id = :capsule_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['capsule_id' => $capsuleId]);

        // Добавляем новые связи
        if (!empty($itemIds)) {
            $itemIds = array_filter(array_map('intval', $itemIds));
            if (!empty($itemIds)) {
                $sql = "INSERT INTO capsule_items (capsule_id, item_id) VALUES ";
                $values = [];
                $params = ['capsule_id' => $capsuleId];

                foreach ($itemIds as $index => $itemId) {
                    $key = "item_{$index}";
                    $values[] = "(:capsule_id, :{$key})";
                    $params[$key] = $itemId;
                }

                $sql .= implode(', ', $values);
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }
        }
    }

    // Синхронизировать образы капсулы
    public function syncOutfits(int $capsuleId, array $outfitIds): void
    {
        // Удаляем все существующие связи
        $sql = "DELETE FROM capsule_outfits WHERE capsule_id = :capsule_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['capsule_id' => $capsuleId]);

        // Добавляем новые связи
        if (!empty($outfitIds)) {
            $outfitIds = array_filter(array_map('intval', $outfitIds));
            if (!empty($outfitIds)) {
                $sql = "INSERT INTO capsule_outfits (capsule_id, outfit_id) VALUES ";
                $values = [];
                $params = ['capsule_id' => $capsuleId];

                foreach ($outfitIds as $index => $outfitId) {
                    $key = "outfit_{$index}";
                    $values[] = "(:capsule_id, :{$key})";
                    $params[$key] = $outfitId;
                }

                $sql .= implode(', ', $values);
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }
        }
    }

    // Генерация комбинаций внутри капсулы
    public function generateCombinations(int $capsuleId, int $userId): array
    {
        // Проверяем права доступа
        $capsule = $this->find($capsuleId);
        if (!$capsule || $capsule['user_id'] != $userId) {
            return [];
        }

        $items = $this->getItems($capsuleId);
        $outfits = $this->getOutfits($capsuleId);

        $combinations = [];

        // Если есть готовые образы, добавляем их
        foreach ($outfits as $outfit) {
            $combinations[] = [
                'type' => 'outfit',
                'outfit' => $outfit,
                'items' => $outfit['items'] ?? []
            ];
        }

        // Генерируем комбинации из отдельных вещей
        // Группируем вещи по категориям
        $itemsByCategory = [];
        foreach ($items as $item) {
            $categoryId = $item['category_id'];
            if (!isset($itemsByCategory[$categoryId])) {
                $itemsByCategory[$categoryId] = [];
            }
            $itemsByCategory[$categoryId][] = $item;
        }

        // Простая генерация комбинаций: верх + низ + обувь + аксессуар
        $tops = $itemsByCategory[1] ?? []; // Верх
        $bottoms = $itemsByCategory[2] ?? []; // Низ
        $shoes = $itemsByCategory[4] ?? []; // Обувь
        $accessories = $itemsByCategory[6] ?? []; // Аксессуар

        // Генерируем до 20 комбинаций
        $maxCombinations = 20;
        $generated = 0;

        foreach ($tops as $top) {
            if ($generated >= $maxCombinations) break;
            
            foreach ($bottoms as $bottom) {
                if ($generated >= $maxCombinations) break;
                
                $combination = [
                    'type' => 'generated',
                    'items' => [$top, $bottom]
                ];

                // Добавляем обувь, если есть
                if (!empty($shoes)) {
                    $combination['items'][] = $shoes[array_rand($shoes)];
                }

                // Добавляем аксессуар, если есть
                if (!empty($accessories)) {
                    $combination['items'][] = $accessories[array_rand($accessories)];
                }

                $combinations[] = $combination;
                $generated++;
            }
        }

        return $combinations;
    }

    // Генерация и сохранение образов из вещей капсулы
    public function generateOutfits(int $capsuleId, int $userId, int $count): array
    {
        // Проверяем права доступа
        $capsule = $this->find($capsuleId);
        if (!$capsule || $capsule['user_id'] != $userId) {
            throw new \RuntimeException('Капсула не найдена или нет прав доступа');
        }

        // Получаем вещи капсулы
        $items = $this->getItems($capsuleId);
        
        if (empty($items)) {
            throw new \RuntimeException('В капсуле нет вещей для генерации образов');
        }

        // Валидация количества
        if ($count < 1 || $count > 50) {
            throw new \RuntimeException('Количество образов должно быть от 1 до 50');
        }

        // Группируем вещи по категориям
        $itemsByCategory = [];
        foreach ($items as $item) {
            $categoryId = $item['category_id'];
            if (!isset($itemsByCategory[$categoryId])) {
                $itemsByCategory[$categoryId] = [];
            }
            $itemsByCategory[$categoryId][] = $item;
        }

        // Определяем категории для генерации
        $tops = $itemsByCategory[1] ?? []; // Верх
        $bottoms = $itemsByCategory[2] ?? []; // Низ
        $dresses = $itemsByCategory[3] ?? []; // Платье/Костюм
        $shoes = $itemsByCategory[4] ?? []; // Обувь
        $outerwear = $itemsByCategory[5] ?? []; // Верхняя одежда
        $accessories = $itemsByCategory[6] ?? []; // Аксессуар

        $outfitModel = new Outfit();
        $generatedOutfits = [];
        $generated = 0;
        $attempts = 0;
        $maxAttempts = $count * 100; // Защита от бесконечного цикла

        // Генерируем образы
        while ($generated < $count && $attempts < $maxAttempts) {
            $attempts++;
            $outfitItems = [];
            $itemIds = [];

            // Если есть платья, можем использовать их как цельный образ
            if (!empty($dresses) && rand(0, 100) < 30) {
                $dress = $dresses[array_rand($dresses)];
                $outfitItems[] = $dress;
                $itemIds[] = $dress['id'];
            } else {
                // Генерируем комбинацию: верх + низ
                if (!empty($tops) && !empty($bottoms)) {
                    $top = $tops[array_rand($tops)];
                    $bottom = $bottoms[array_rand($bottoms)];
                    $outfitItems[] = $top;
                    $outfitItems[] = $bottom;
                    $itemIds[] = $top['id'];
                    $itemIds[] = $bottom['id'];
                } elseif (!empty($tops)) {
                    // Только верх
                    $top = $tops[array_rand($tops)];
                    $outfitItems[] = $top;
                    $itemIds[] = $top['id'];
                } elseif (!empty($bottoms)) {
                    // Только низ
                    $bottom = $bottoms[array_rand($bottoms)];
                    $outfitItems[] = $bottom;
                    $itemIds[] = $bottom['id'];
                } else {
                    // Если нет верха и низа, пропускаем
                    continue;
                }
            }

            // Добавляем верхнюю одежду, если есть
            if (!empty($outerwear) && rand(0, 100) < 40) {
                $outer = $outerwear[array_rand($outerwear)];
                if (!in_array($outer['id'], $itemIds)) {
                    $outfitItems[] = $outer;
                    $itemIds[] = $outer['id'];
                }
            }

            // Добавляем обувь, если есть
            if (!empty($shoes) && rand(0, 100) < 70) {
                $shoe = $shoes[array_rand($shoes)];
                if (!in_array($shoe['id'], $itemIds)) {
                    $outfitItems[] = $shoe;
                    $itemIds[] = $shoe['id'];
                }
            }

            // Добавляем аксессуар, если есть
            if (!empty($accessories) && rand(0, 100) < 50) {
                $accessory = $accessories[array_rand($accessories)];
                if (!in_array($accessory['id'], $itemIds)) {
                    $outfitItems[] = $accessory;
                    $itemIds[] = $accessory['id'];
                }
            }

            // Проверяем, что у нас есть хотя бы одна вещь
            if (empty($itemIds)) {
                continue;
            }

            // Создаем название образа
            $outfitName = 'Образ из капсулы "' . $capsule['name'] . '" #' . ($generated + 1);
            
            // Определяем сезон образа (берем из капсулы или из первой вещи)
            $seasonId = $capsule['season_id'];
            if (!$seasonId && !empty($outfitItems)) {
                $seasonId = $outfitItems[0]['season_id'] ?? null;
            }

            // Создаем образ
            try {
                $outfitData = [
                    'name' => $outfitName,
                    'description' => 'Автоматически сгенерированный образ из капсулы',
                    'season_id' => $seasonId,
                    'item_ids' => $itemIds
                ];

                $outfitId = $outfitModel->createOutfit($userId, $outfitData);

                // Связываем образ с капсулой
                $this->linkOutfitToCapsule($capsuleId, $outfitId);

                $generatedOutfits[] = $outfitId;
                $generated++;

                Logger::info('Образ сгенерирован из капсулы', [
                    'capsule_id' => $capsuleId,
                    'outfit_id' => $outfitId,
                    'items_count' => count($itemIds)
                ]);
            } catch (\Exception $e) {
                Logger::error('Ошибка при создании образа из капсулы', [
                    'capsule_id' => $capsuleId,
                    'error' => $e->getMessage()
                ]);
                // Продолжаем генерацию, даже если один образ не создался
            }
        }

        return $generatedOutfits;
    }

    // Связать образ с капсулой
    private function linkOutfitToCapsule(int $capsuleId, int $outfitId): void
    {
        // Проверяем, не связан ли уже образ с капсулой
        $sql = "SELECT * FROM capsule_outfits 
                WHERE capsule_id = :capsule_id AND outfit_id = :outfit_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['capsule_id' => $capsuleId, 'outfit_id' => $outfitId]);
        
        if ($stmt->fetch()) {
            return; // Уже связан
        }

        // Создаем связь
        $sql = "INSERT INTO capsule_outfits (capsule_id, outfit_id) 
                VALUES (:capsule_id, :outfit_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['capsule_id' => $capsuleId, 'outfit_id' => $outfitId]);
    }

    // Получить статистику капсул пользователя
    public function getStatistics(int $userId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_capsules,
                    COUNT(DISTINCT season_id) as seasons_count,
                    COUNT(DISTINCT ci.item_id) as unique_items_used,
                    COUNT(DISTINCT co.outfit_id) as unique_outfits_used
                FROM {$this->table} c
                LEFT JOIN capsule_items ci ON c.id = ci.capsule_id
                LEFT JOIN capsule_outfits co ON c.id = co.capsule_id
                WHERE c.user_id = :user_id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Получить общее количество капсул пользователя
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
