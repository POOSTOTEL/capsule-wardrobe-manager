<?php
// app/Models/Analytics.php

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

class Analytics extends BaseModel
{
    protected $table = 'usage_history'; // Используем таблицу истории для аналитики

    // Получить распределение по категориям
    public function getCategoryDistribution(int $userId, int $limit = null): array
    {
        $sql = "SELECT 
                    c.id,
                    c.name,
                    COUNT(i.id) as item_count,
                    ROUND(COUNT(i.id) * 100.0 / NULLIF((SELECT COUNT(*) FROM items WHERE user_id = :user_id), 0), 2) as percentage
                FROM categories c
                LEFT JOIN items i ON c.id = i.category_id AND i.user_id = :user_id
                GROUP BY c.id, c.name
                HAVING COUNT(i.id) > 0
                ORDER BY item_count DESC";

        if ($limit !== null) {
            $sql .= " LIMIT :limit";
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            if ($limit !== null) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Получить распределение по цветам
    public function getColorDistribution(int $userId, int $limit = null): array
    {
        $sql = "SELECT 
                    cl.id,
                    cl.name,
                    cl.hex_code,
                    COUNT(i.id) as item_count,
                    ROUND(COUNT(i.id) * 100.0 / NULLIF((SELECT COUNT(*) FROM items WHERE user_id = :user_id), 0), 2) as percentage
                FROM colors cl
                LEFT JOIN items i ON cl.id = i.color_id AND i.user_id = :user_id
                GROUP BY cl.id, cl.name, cl.hex_code
                HAVING COUNT(i.id) > 0
                ORDER BY item_count DESC";

        if ($limit !== null) {
            $sql .= " LIMIT :limit";
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            if ($limit !== null) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Получить топ используемых вещей
    public function getTopUsedItems(int $userId, int $limit = 10): array
    {
        $sql = "SELECT 
                    i.id,
                    i.name,
                    i.usage_count,
                    c.name as category_name,
                    cl.name as color_name,
                    cl.hex_code as color_hex,
                    (SELECT COUNT(*) FROM items WHERE user_id = :user_id) as total_items,
                    ROUND(i.usage_count * 100.0 / NULLIF(GREATEST((SELECT MAX(usage_count) FROM items WHERE user_id = :user_id), 1), 0), 2) as usage_percentage
                FROM items i
                LEFT JOIN categories c ON i.category_id = c.id
                LEFT JOIN colors cl ON i.color_id = cl.id
                WHERE i.user_id = :user_id
                ORDER BY i.usage_count DESC, i.name ASC
                LIMIT :limit";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Удаляем бинарные данные изображений
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

    // Получить индекс использования вещей с фильтрацией
    public function getItemsUsageIndex(int $userId, string $filter = 'all'): array
    {
        $sql = "SELECT 
                    i.id,
                    i.name,
                    i.usage_count,
                    c.name as category_name,
                    cl.name as color_name,
                    cl.hex_code as color_hex,
                    (SELECT MAX(usage_count) FROM items WHERE user_id = :user_id) as max_usage,
                    (SELECT AVG(usage_count) FROM items WHERE user_id = :user_id) as avg_usage
                FROM items i
                LEFT JOIN categories c ON i.category_id = c.id
                LEFT JOIN colors cl ON i.color_id = cl.id
                WHERE i.user_id = :user_id";

        // Применяем фильтры
        switch ($filter) {
            case 'top':
                $sql .= " AND i.usage_count > (SELECT AVG(usage_count) FROM items WHERE user_id = :user_id)";
                break;
            case 'bottom':
                $sql .= " AND i.usage_count < (SELECT AVG(usage_count) FROM items WHERE user_id = :user_id) AND i.usage_count = 0";
                break;
            case 'unused':
                $sql .= " AND i.usage_count = 0";
                break;
        }

        $sql .= " ORDER BY i.usage_count DESC, i.name ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Удаляем бинарные данные изображений
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

    // Получить карту сочетаемости
    public function getCompatibilityMap(int $userId, int $limit = 20): array
    {
        $sql = "SELECT 
                    i1.id as item1_id,
                    i1.name as item1_name,
                    i2.id as item2_id,
                    i2.name as item2_name,
                    COUNT(DISTINCT o.id) as times_paired,
                    c1.name as item1_category,
                    c2.name as item2_category,
                    cl1.name as item1_color,
                    cl2.name as item2_color,
                    cl1.hex_code as item1_color_hex,
                    cl2.hex_code as item2_color_hex
                FROM outfit_items oi1
                JOIN outfit_items oi2 ON oi1.outfit_id = oi2.outfit_id AND oi1.item_id < oi2.item_id
                JOIN items i1 ON oi1.item_id = i1.id
                JOIN items i2 ON oi2.item_id = i2.id
                JOIN outfits o ON oi1.outfit_id = o.id
                LEFT JOIN categories c1 ON i1.category_id = c1.id
                LEFT JOIN categories c2 ON i2.category_id = c2.id
                LEFT JOIN colors cl1 ON i1.color_id = cl1.id
                LEFT JOIN colors cl2 ON i2.color_id = cl2.id
                WHERE o.user_id = :user_id
                GROUP BY i1.id, i1.name, i2.id, i2.name, c1.name, c2.name, cl1.name, cl2.name, cl1.hex_code, cl2.hex_code
                ORDER BY times_paired DESC
                LIMIT :limit";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Получить статистику по сезонам
    public function getSeasonStatistics(int $userId): array
    {
        $sql = "SELECT 
                    s.id,
                    s.name,
                    COUNT(i.id) as items_count,
                    COUNT(DISTINCT o.id) as outfits_count,
                    COUNT(DISTINCT cp.id) as capsules_count
                FROM seasons s
                LEFT JOIN items i ON s.id = i.season_id AND i.user_id = :user_id
                LEFT JOIN outfits o ON s.id = o.season_id AND o.user_id = :user_id
                LEFT JOIN capsules cp ON s.id = cp.season_id AND cp.user_id = :user_id
                GROUP BY s.id, s.name
                HAVING COUNT(i.id) > 0 OR COUNT(DISTINCT o.id) > 0 OR COUNT(DISTINCT cp.id) > 0
                ORDER BY s.name ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Получить статистику использования
    public function getUsageStatistics(int $userId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_items,
                    COUNT(CASE WHEN usage_count > 0 THEN 1 END) as used_items,
                    COUNT(CASE WHEN usage_count = 0 THEN 1 END) as unused_items,
                    AVG(usage_count) as avg_usage,
                    MAX(usage_count) as max_usage,
                    MIN(usage_count) as min_usage,
                    SUM(usage_count) as total_usage
                FROM items
                WHERE user_id = :user_id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Вычисляем проценты
            if ($stats['total_items'] > 0) {
                $stats['used_percentage'] = round(($stats['used_items'] / $stats['total_items']) * 100, 2);
                $stats['unused_percentage'] = round(($stats['unused_items'] / $stats['total_items']) * 100, 2);
            } else {
                $stats['used_percentage'] = 0;
                $stats['unused_percentage'] = 0;
            }

            return $stats;
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }
}
