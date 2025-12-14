<?php


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

        
        if (!empty($filters['season_id'])) {
            $sql .= " AND o.season_id = :season_id";
            $params['season_id'] = (int) $filters['season_id'];
        }

        if (!empty($filters['formality_level'])) {
            $sql .= " AND o.formality_level = :formality_level";
            $params['formality_level'] = (int) $filters['formality_level'];
        }

        if (isset($filters['is_favorite']) && $filters['is_favorite'] !== '') {
            $sql .= " AND o.is_favorite = :is_favorite";
            $params['is_favorite'] = $filters['is_favorite'] === '1' || $filters['is_favorite'] === true;
        }

        
        if (!empty($filters['search'])) {
            $sql .= " AND (LOWER(o.name) LIKE LOWER(:search) OR LOWER(o.description) LIKE LOWER(:search))";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        
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

        
        $sql .= " GROUP BY o.id, s.name";

        
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = strtoupper($filters['order_dir'] ?? 'DESC');
        
        
        $allowedOrderBy = ['created_at', 'updated_at', 'name', 'formality_level', 'is_favorite', 'items_count'];
        if (in_array($orderBy, $allowedOrderBy)) {
            if ($orderBy === 'items_count') {
                $sql .= " ORDER BY items_count {$orderDir}";
            } else {
                $sql .= " ORDER BY o.{$orderBy} {$orderDir}";
            }
        } else {
            $sql .= " ORDER BY o.created_at {$orderDir}";
        }

        
        if (!empty($filters['limit'])) {
            $limit = (int) $filters['limit'];
            $offset = (int) ($filters['offset'] ?? 0);
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $outfits = $stmt->fetchAll(PDO::FETCH_ASSOC);

            
            foreach ($outfits as &$outfit) {
                $outfit['tags'] = $this->getTags($outfit['id']);
                $outfit['items'] = $this->getItems($outfit['id']);
            }

            return $outfits;
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    
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

            
            $outfit['tags'] = $this->getTags($id);
            $outfit['items'] = $this->getItems($id);

            return $outfit;
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    
    public function createOutfit(int $userId, array $data): int
    {
        Logger::debug('createOutfit: начало', [
            'user_id' => $userId,
            'data_keys' => array_keys($data)
        ]);

        
        $outfitData = [
            'user_id' => $userId,
            'name' => trim($data['name'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'formality_level' => isset($data['formality_level']) ? (int) $data['formality_level'] : null,
            'season_id' => !empty($data['season_id']) ? (int) $data['season_id'] : null,
            'is_favorite' => isset($data['is_favorite']) ? (bool) $data['is_favorite'] : false
        ];

        
        if ($outfitData['formality_level'] !== null) {
            if ($outfitData['formality_level'] < 1 || $outfitData['formality_level'] > 5) {
                throw new \RuntimeException("formality_level должен быть от 1 до 5");
            }
        }

        
        $outfitId = $this->create($outfitData);

        Logger::info('createOutfit: образ создан в БД', ['outfit_id' => $outfitId]);

        
        if (!empty($data['item_ids']) && is_array($data['item_ids'])) {
            Logger::debug('createOutfit: добавление вещей', ['item_ids' => $data['item_ids']]);
            $this->syncItems($outfitId, $data['item_ids']);
        }

        
        if (!empty($data['tag_ids']) && is_array($data['tag_ids'])) {
            Logger::debug('createOutfit: синхронизация тегов', ['tag_ids' => $data['tag_ids']]);
            $this->syncTags($outfitId, $data['tag_ids']);
        }

        return $outfitId;
    }

    
    public function updateOutfit(int $id, int $userId, array $data): bool
    {
        
        $outfit = $this->find($id);
        if (!$outfit || $outfit['user_id'] != $userId) {
            return false;
        }

        
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

        
        $success = false;
        if (!empty($updateData)) {
            $success = $this->update($id, $updateData);
        }

        
        if (isset($data['item_ids']) && is_array($data['item_ids'])) {
            $this->syncItems($id, $data['item_ids']);
        }

        
        if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
            $this->syncTags($id, $data['tag_ids']);
        }

        return $success;
    }

    
    public function deleteOutfit(int $id, int $userId): bool
    {
        $outfit = $this->find($id);
        if (!$outfit || $outfit['user_id'] != $userId) {
            return false;
        }

        return $this->delete($id);
    }

    
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

            
            foreach ($items as &$item) {
                $item['tags'] = $this->getItemTags($item['id']);
                
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

    
    public function syncItems(int $outfitId, array $itemIds): void
    {
        
        $sql = "DELETE FROM outfit_items WHERE outfit_id = :outfit_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['outfit_id' => $outfitId]);

        
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

    
    public function syncTags(int $outfitId, array $tagIds): void
    {
        
        $sql = "DELETE FROM outfit_tags WHERE outfit_id = :outfit_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['outfit_id' => $outfitId]);

        
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

    
    public function addItem(int $outfitId, int $itemId, int $position = null): bool
    {
        
        $sql = "SELECT * FROM outfit_items 
                WHERE outfit_id = :outfit_id AND item_id = :item_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['outfit_id' => $outfitId, 'item_id' => $itemId]);
        
        if ($stmt->fetch()) {
            return false; 
        }

        
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

    
    public function toggleFavorite(int $id, int $userId): bool
    {
        $outfit = $this->find($id);
        if (!$outfit || $outfit['user_id'] != $userId) {
            return false;
        }

        $newValue = !$outfit['is_favorite'];
        return $this->update($id, ['is_favorite' => $newValue]);
    }

    
    public function addToCapsule(int $outfitId, int $capsuleId, int $userId): bool
    {
        
        $outfit = $this->find($outfitId);
        if (!$outfit || $outfit['user_id'] != $userId) {
            return false;
        }

        
        $items = $this->getItems($outfitId);
        if (empty($items)) {
            return false;
        }

        
        $capsuleModel = new Capsule();
        $capsule = $capsuleModel->find($capsuleId);
        if (!$capsule || $capsule['user_id'] != $userId) {
            return false;
        }

        
        try {
            foreach ($items as $item) {
                $capsuleModel->addItem($capsuleId, $item['id']);
            }
            return true;
        } catch (\Exception $e) {
            Logger::error('Ошибка при добавлении образа в капсулу', [
                'outfit_id' => $outfitId,
                'capsule_id' => $capsuleId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    
    public function generateFromCapsule(int $capsuleId, int $userId, int $count = 10): array
    {
        $capsuleModel = new Capsule();
        $capsule = $capsuleModel->find($capsuleId);
        
        if (!$capsule || $capsule['user_id'] != $userId) {
            throw new \RuntimeException('Капсула не найдена или нет прав доступа');
        }

        $items = $capsuleModel->getItems($capsuleId);
        if (empty($items)) {
            throw new \RuntimeException('В капсуле нет вещей для генерации образов');
        }

        
        $itemsByCategory = [];
        foreach ($items as $item) {
            $categoryId = $item['category_id'];
            if (!isset($itemsByCategory[$categoryId])) {
                $itemsByCategory[$categoryId] = [];
            }
            $itemsByCategory[$categoryId][] = $item;
        }

        
        $tops = $itemsByCategory[1] ?? [];
        $bottoms = $itemsByCategory[2] ?? [];
        $dresses = $itemsByCategory[3] ?? [];
        $shoes = $itemsByCategory[4] ?? [];
        $outerwear = $itemsByCategory[5] ?? [];
        $accessories = $itemsByCategory[6] ?? [];

        $generatedOutfits = [];
        $generated = 0;
        $attempts = 0;
        $maxAttempts = $count * 100;

        while ($generated < $count && $attempts < $maxAttempts) {
            $attempts++;
            $outfitItems = [];
            $itemIds = [];

            
            if (!empty($dresses) && rand(0, 100) < 30) {
                $dress = $dresses[array_rand($dresses)];
                $outfitItems[] = $dress;
                $itemIds[] = $dress['id'];
            } else {
                
                if (!empty($tops) && !empty($bottoms)) {
                    $top = $tops[array_rand($tops)];
                    $bottom = $bottoms[array_rand($bottoms)];
                    $outfitItems[] = $top;
                    $outfitItems[] = $bottom;
                    $itemIds[] = $top['id'];
                    $itemIds[] = $bottom['id'];
                } elseif (!empty($tops)) {
                    $top = $tops[array_rand($tops)];
                    $outfitItems[] = $top;
                    $itemIds[] = $top['id'];
                } elseif (!empty($bottoms)) {
                    $bottom = $bottoms[array_rand($bottoms)];
                    $outfitItems[] = $bottom;
                    $itemIds[] = $bottom['id'];
                } else {
                    continue;
                }
            }

            
            if (!empty($outerwear) && rand(0, 100) < 40) {
                $outer = $outerwear[array_rand($outerwear)];
                if (!in_array($outer['id'], $itemIds)) {
                    $outfitItems[] = $outer;
                    $itemIds[] = $outer['id'];
                }
            }

            
            if (!empty($shoes) && rand(0, 100) < 70) {
                $shoe = $shoes[array_rand($shoes)];
                if (!in_array($shoe['id'], $itemIds)) {
                    $outfitItems[] = $shoe;
                    $itemIds[] = $shoe['id'];
                }
            }

            
            if (!empty($accessories) && rand(0, 100) < 50) {
                $accessory = $accessories[array_rand($accessories)];
                if (!in_array($accessory['id'], $itemIds)) {
                    $outfitItems[] = $accessory;
                    $itemIds[] = $accessory['id'];
                }
            }

            if (empty($itemIds)) {
                continue;
            }

            
            $seasonId = $capsule['season_id'];
            if (!$seasonId && !empty($outfitItems)) {
                $seasonId = $outfitItems[0]['season_id'] ?? null;
            }

            
            $generatedOutfits[] = [
                'name' => 'Образ из капсулы "' . $capsule['name'] . '" #' . ($generated + 1),
                'description' => 'Автоматически сгенерированный образ из капсулы',
                'season_id' => $seasonId,
                'items' => $outfitItems,
                'item_ids' => $itemIds,
                'is_generated' => true
            ];

            $generated++;
        }

        return $generatedOutfits;
    }

    
    public function saveGeneratedOutfit(int $userId, array $outfitData): int
    {
        return $this->createOutfit($userId, $outfitData);
    }

    
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
