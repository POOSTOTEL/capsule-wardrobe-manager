<?php


namespace App\Models;

use App\Core\Database;

class Tag extends BaseModel
{
    protected $table = 'tags';
    protected $fillable = ['user_id', 'name', 'color', 'is_system'];

    
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

    
    public function createUserTag(int $userId, array $data): ?int
    {
        
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
                return null; 
            }
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }

        $data['user_id'] = $userId;
        $data['is_system'] = false;
        $data['name'] = trim($data['name']);

        
        if (empty($data['color'])) {
            $data['color'] = $this->generateRandomColor();
        }

        try {
            return $this->create($data);
        } catch (\PDOException $e) {
            
            if (strpos($e->getMessage(), 'unique') !== false || 
                strpos($e->getMessage(), 'duplicate') !== false) {
                return null;
            }
            throw $e;
        }
    }

    
    public function updateUserTag(int $tagId, int $userId, array $data): bool
    {
        
        $tag = $this->find($tagId);
        if (!$tag || $tag['is_system'] || $tag['user_id'] != $userId) {
            return false;
        }

        
        if (isset($data['name'])) {
            $newName = trim($data['name']);
            
            $sql = "SELECT * FROM {$this->table} 
                    WHERE user_id = :user_id 
                    AND LOWER(name) = LOWER(:name)
                    AND id != :id
                    LIMIT 1";
            
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'user_id' => $userId,
                    'name' => $newName,
                    'id' => $tagId
                ]);
                $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($existing) {
                    return false; 
                }
            } catch (\PDOException $e) {
                throw new \RuntimeException("Database error: " . $e->getMessage());
            }
            
            $data['name'] = $newName;
        }
        
        try {
            return $this->update($tagId, $data);
        } catch (\PDOException $e) {
            
            if (strpos($e->getMessage(), 'unique') !== false || 
                strpos($e->getMessage(), 'duplicate') !== false) {
                return false;
            }
            throw $e;
        }
    }

    
    public function deleteUserTag(int $tagId, int $userId): bool
    {
        $tag = $this->find($tagId);

        if (!$tag || $tag['is_system'] || $tag['user_id'] != $userId) {
            return false;
        }

        return $this->delete($tagId);
    }

    
    public function getSystemTags(): array
    {
        return $this->where('is_system', true);
    }

    
    public function getUserTags(int $userId): array
    {
        return $this->where('user_id', $userId);
    }

    
    public function getForItem(int $itemId): array
    {
        $sql = "SELECT t.* FROM tags t
                JOIN item_tags it ON t.id = it.tag_id
                WHERE it.item_id = :item_id
                ORDER BY t.is_system DESC, t.name ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['item_id' => $itemId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    
    public function getForOutfit(int $outfitId): array
    {
        $sql = "SELECT t.* FROM tags t
                JOIN outfit_tags ot ON t.id = ot.tag_id
                WHERE ot.outfit_id = :outfit_id
                ORDER BY t.is_system DESC, t.name ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['outfit_id' => $outfitId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    
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

    
    public function getPopularTags(int $userId, int $limit = 10): array
    {
        $sql = "SELECT t.*, 
                       (COUNT(DISTINCT it.item_id) + COUNT(DISTINCT ot.outfit_id)) as usage_count 
                FROM tags t
                LEFT JOIN item_tags it ON t.id = it.tag_id
                LEFT JOIN outfit_tags ot ON t.id = ot.tag_id
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

    
    public function attachToOutfit(int $tagId, int $outfitId): bool
    {
        $sql = "INSERT INTO outfit_tags (outfit_id, tag_id) 
                VALUES (:outfit_id, :tag_id)
                ON CONFLICT (outfit_id, tag_id) DO NOTHING";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'outfit_id' => $outfitId,
                'tag_id' => $tagId
            ]);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    
    public function detachFromOutfit(int $tagId, int $outfitId): bool
    {
        $sql = "DELETE FROM outfit_tags 
                WHERE outfit_id = :outfit_id AND tag_id = :tag_id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'outfit_id' => $outfitId,
                'tag_id' => $tagId
            ]);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    
    public function syncItemTags(int $itemId, array $tagIds): void
    {
        
        $sql = "DELETE FROM item_tags WHERE item_id = :item_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['item_id' => $itemId]);

        
        if (!empty($tagIds)) {
            $tagIds = array_filter(array_map('intval', $tagIds));
            if (!empty($tagIds)) {
                $sql = "INSERT INTO item_tags (item_id, tag_id) VALUES ";
                $values = [];
                $params = ['item_id' => $itemId];

                foreach ($tagIds as $index => $tagId) {
                    $key = "tag_{$index}";
                    $values[] = "(:item_id, :{$key})";
                    $params[$key] = $tagId;
                }

                $sql .= implode(', ', $values);
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }
        }
    }

    
    public function syncOutfitTags(int $outfitId, array $tagIds): void
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

    
    public function getOutfitsWithTag(int $tagId, int $userId): array
    {
        $sql = "SELECT o.* FROM outfits o
                JOIN outfit_tags ot ON o.id = ot.outfit_id
                WHERE ot.tag_id = :tag_id AND o.user_id = :user_id
                ORDER BY o.name";

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
