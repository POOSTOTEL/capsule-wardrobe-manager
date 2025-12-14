<?php
// app/Models/Item.php

namespace App\Models;

use App\Core\Database;
use App\Utils\Logger;
use PDO;
use PDOException;

class Item extends BaseModel
{
    protected $table = 'items';
    protected $fillable = [
        'user_id', 'name', 'category_id', 'color_id', 'season_id',
        'image_data', 'image_mime_type', 'notes', 'usage_count'
    ];

    // Получить все вещи пользователя с дополнительной информацией
    public function getByUser(int $userId, array $filters = []): array
    {
        $sql = "SELECT i.*, 
                       c.name as category_name,
                       s.name as season_name,
                       cl.name as color_name,
                       cl.hex_code as color_hex
                FROM {$this->table} i
                LEFT JOIN categories c ON i.category_id = c.id
                LEFT JOIN seasons s ON i.season_id = s.id
                LEFT JOIN colors cl ON i.color_id = cl.id
                WHERE i.user_id = :user_id";

        $params = ['user_id' => $userId];

        // Применяем фильтры
        if (!empty($filters['category_id'])) {
            $sql .= " AND i.category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }

        if (!empty($filters['color_id'])) {
            $sql .= " AND i.color_id = :color_id";
            $params['color_id'] = $filters['color_id'];
        }

        if (!empty($filters['season_id'])) {
            $sql .= " AND i.season_id = :season_id";
            $params['season_id'] = $filters['season_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND LOWER(i.name) LIKE LOWER(:search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        // Фильтр по тегам
        if (!empty($filters['tag_ids']) && is_array($filters['tag_ids'])) {
            $tagIds = array_filter(array_map('intval', $filters['tag_ids']));
            if (!empty($tagIds)) {
                $placeholders = implode(',', array_fill(0, count($tagIds), '?'));
                $sql .= " AND i.id IN (
                    SELECT item_id FROM item_tags 
                    WHERE tag_id IN ({$placeholders})
                    GROUP BY item_id
                    HAVING COUNT(DISTINCT tag_id) = ?
                )";
                $params = array_merge($params, $tagIds, [count($tagIds)]);
            }
        }

        // Сортировка
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = strtoupper($filters['order_dir'] ?? 'DESC');
        $sql .= " ORDER BY i.{$orderBy} {$orderDir}";

        // Пагинация
        if (!empty($filters['limit'])) {
            $limit = (int) $filters['limit'];
            $offset = (int) ($filters['offset'] ?? 0);
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Загружаем теги для каждой вещи
            foreach ($items as &$item) {
                $item['tags'] = $this->getTags($item['id']);
            }

            return $items;
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Получить вещь с полной информацией
    public function getWithDetails(int $id, int $userId = null): ?array
    {
        $sql = "SELECT i.*, 
                       c.name as category_name,
                       c.description as category_description,
                       s.name as season_name,
                       cl.name as color_name,
                       cl.hex_code as color_hex
                FROM {$this->table} i
                LEFT JOIN categories c ON i.category_id = c.id
                LEFT JOIN seasons s ON i.season_id = s.id
                LEFT JOIN colors cl ON i.color_id = cl.id
                WHERE i.id = :id";

        $params = ['id' => $id];

        if ($userId !== null) {
            $sql .= " AND i.user_id = :user_id";
            $params['user_id'] = $userId;
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                return null;
            }

            // Загружаем теги
            $item['tags'] = $this->getTags($id);

            return $item;
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Создать вещь с изображением
    public function createWithImage(int $userId, array $data, string $imagePath = null): int
    {
        Logger::debug('createWithImage: начало', [
            'user_id' => $userId,
            'image_path' => $imagePath,
            'data_keys' => array_keys($data)
        ]);

        // Обрабатываем изображение
        if ($imagePath && file_exists($imagePath)) {
            Logger::debug('createWithImage: чтение файла', ['path' => $imagePath, 'size' => filesize($imagePath)]);
            
            $imageData = file_get_contents($imagePath);
            
            // Определяем MIME тип
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $imagePath);
            finfo_close($finfo);
            
            // Fallback на mime_content_type если finfo недоступен
            if (!$mimeType) {
                $mimeType = mime_content_type($imagePath);
            }

            Logger::debug('createWithImage: MIME тип определен', ['mime_type' => $mimeType]);

            // Валидация MIME типа
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($mimeType, $allowedTypes)) {
                Logger::error('createWithImage: неподдерживаемый тип', ['mime_type' => $mimeType]);
                throw new \RuntimeException("Неподдерживаемый тип изображения: {$mimeType}");
            }

            // Сжимаем изображение, если оно слишком большое
            $maxSize = 2 * 1024 * 1024; // 2MB
            $originalSize = strlen($imageData);
            if ($originalSize > $maxSize) {
                Logger::info('createWithImage: сжатие изображения', [
                    'original_size' => $originalSize,
                    'max_size' => $maxSize
                ]);
                $imageData = $this->compressImage($imageData, $mimeType);
                Logger::info('createWithImage: изображение сжато', ['new_size' => strlen($imageData)]);
            }

            // Сохраняем бинарные данные как есть (будем обрабатывать отдельно)
            $data['image_data'] = $imageData;
            $data['image_mime_type'] = $mimeType;
            
            Logger::debug('createWithImage: данные изображения подготовлены', [
                'image_size' => strlen($imageData),
                'mime_type' => $mimeType
            ]);
        } else {
            Logger::error('createWithImage: файл не найден', ['path' => $imagePath]);
            throw new \RuntimeException("Изображение обязательно для загрузки");
        }

        $data['user_id'] = $userId;
        $data['usage_count'] = 0;

        Logger::debug('createWithImage: вызов createWithBinaryData', [
            'data_keys' => array_keys($data),
            'has_image_data' => isset($data['image_data'])
        ]);

        // Создаем вещь с использованием специального метода для BYTEA
        $itemId = $this->createWithBinaryData($data);

        Logger::info('createWithImage: вещь создана в БД', ['item_id' => $itemId]);

        // Привязываем теги, если указаны
        if (!empty($data['tag_ids']) && is_array($data['tag_ids'])) {
            Logger::debug('createWithImage: синхронизация тегов', ['tag_ids' => $data['tag_ids']]);
            $this->syncTags($itemId, $data['tag_ids']);
        }

        return $itemId;
    }

    // Обновить вещь
    public function updateItem(int $id, int $userId, array $data, string $imagePath = null): bool
    {
        // Проверяем права доступа
        $item = $this->find($id);
        if (!$item || $item['user_id'] != $userId) {
            return false;
        }

        // Обрабатываем новое изображение, если загружено
        if ($imagePath && file_exists($imagePath)) {
            $imageData = file_get_contents($imagePath);
            
            // Определяем MIME тип
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $imagePath);
            finfo_close($finfo);
            
            // Fallback на mime_content_type если finfo недоступен
            if (!$mimeType) {
                $mimeType = mime_content_type($imagePath);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($mimeType, $allowedTypes)) {
                throw new \RuntimeException("Неподдерживаемый тип изображения: {$mimeType}");
            }

            $maxSize = 2 * 1024 * 1024; // 2MB
            if (strlen($imageData) > $maxSize) {
                $imageData = $this->compressImage($imageData, $mimeType);
            }

            // Сохраняем бинарные данные как есть (будем обрабатывать отдельно)
            $data['image_data'] = $imageData;
            $data['image_mime_type'] = $mimeType;
        }

        // Обновляем вещь с использованием специального метода для BYTEA
        $success = $this->updateWithBinaryData($id, $data);

        // Синхронизируем теги
        if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
            $this->syncTags($id, $data['tag_ids']);
        }

        return $success;
    }

    // Удалить вещь
    public function deleteItem(int $id, int $userId): bool
    {
        $item = $this->find($id);
        if (!$item || $item['user_id'] != $userId) {
            return false;
        }

        return $this->delete($id);
    }

    // Получить теги вещи
    public function getTags(int $itemId): array
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
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Синхронизировать теги вещи
    public function syncTags(int $itemId, array $tagIds): void
    {
        // Удаляем все существующие связи
        $sql = "DELETE FROM item_tags WHERE item_id = :item_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['item_id' => $itemId]);

        // Добавляем новые связи
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

    // Получить изображение вещи в base64
    public function getImageBase64(int $id): ?string
    {
        $item = $this->find($id, ['image_data', 'image_mime_type']);
        if (!$item || empty($item['image_data'])) {
            return null;
        }

        $base64 = base64_encode($item['image_data']);
        return "data:{$item['image_mime_type']};base64,{$base64}";
    }

    // Получить URL изображения (для использования в img src)
    public function getImageUrl(int $id): string
    {
        return "/api/items/{$id}/image";
    }

    // Получить статистику вещей пользователя
    public function getStatistics(int $userId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_items,
                    COUNT(DISTINCT category_id) as categories_count,
                    COUNT(DISTINCT color_id) as colors_count,
                    COUNT(DISTINCT season_id) as seasons_count,
                    AVG(usage_count) as avg_usage,
                    MAX(usage_count) as max_usage
                FROM {$this->table}
                WHERE user_id = :user_id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Получить вещи по категориям (для аналитики)
    public function getByCategories(int $userId): array
    {
        $sql = "SELECT c.id, c.name, COUNT(i.id) as count
                FROM categories c
                LEFT JOIN {$this->table} i ON c.id = i.category_id AND i.user_id = :user_id
                GROUP BY c.id, c.name
                ORDER BY count DESC, c.name ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Получить вещи по цветам (для аналитики)
    public function getByColors(int $userId): array
    {
        $sql = "SELECT cl.id, cl.name, cl.hex_code, COUNT(i.id) as count
                FROM colors cl
                LEFT JOIN {$this->table} i ON cl.id = i.color_id AND i.user_id = :user_id
                GROUP BY cl.id, cl.name, cl.hex_code
                HAVING COUNT(i.id) > 0
                ORDER BY count DESC, cl.name ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Создать запись с бинарными данными (BYTEA)
    private function createWithBinaryData(array $data): int
    {
        // Отделяем бинарные данные от обычных
        $imageData = $data['image_data'] ?? null;
        $imageMimeType = $data['image_mime_type'] ?? null;
        unset($data['image_data'], $data['image_mime_type']);
        
        // Фильтруем данные по fillable
        $filteredData = array_intersect_key($data, array_flip($this->fillable));

        if (empty($filteredData) && !$imageData) {
            throw new \RuntimeException("No fillable fields provided for insert");
        }

        // Строим SQL запрос
        $columns = array_keys($filteredData);
        $placeholders = array_map(fn($col) => ':' . $col, $columns);
        
        // Добавляем image_data и image_mime_type если есть
        if ($imageData !== null) {
            $columns[] = 'image_data';
            $columns[] = 'image_mime_type';
            $placeholders[] = ':image_data';
            $placeholders[] = ':image_mime_type';
        }

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ") RETURNING {$this->primaryKey}";

        try {
            // Логируем SQL запрос (без бинарных данных)
            Logger::debug('Выполнение INSERT для items', [
                'columns' => $columns,
                'filtered_data_keys' => array_keys($filteredData),
                'has_image' => $imageData !== null,
                'image_size' => $imageData ? strlen($imageData) : 0
            ]);

            $stmt = $this->db->prepare($sql);
            
            // Привязываем обычные параметры
            foreach ($filteredData as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
                Logger::debug("Привязка параметра: {$key}", ['value' => is_string($value) ? substr($value, 0, 50) : $value]);
            }
            
            // Привязываем бинарные данные для BYTEA
            if ($imageData !== null) {
                // Для PostgreSQL BYTEA используем hex-формат через параметр
                // Конвертируем бинарные данные в hex-строку с префиксом \x
                $hexData = '\\x' . bin2hex($imageData);
                $stmt->bindValue(':image_data', $hexData, PDO::PARAM_STR);
                $stmt->bindValue(':image_mime_type', $imageMimeType ?? 'image/jpeg');
                Logger::debug('Привязка бинарных данных', [
                    'hex_length' => strlen($hexData),
                    'mime_type' => $imageMimeType ?? 'image/jpeg'
                ]);
            }
            
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && isset($result[$this->primaryKey])) {
                Logger::info('Вещь создана успешно', ['item_id' => $result[$this->primaryKey]]);
                return (int) $result[$this->primaryKey];
            }
            
            $sequenceName = $this->table . '_' . $this->primaryKey . '_seq';
            $lastId = $this->db->lastInsertId($sequenceName);
            Logger::info('Вещь создана через lastInsertId', ['item_id' => $lastId]);
            return $lastId ? (int) $lastId : 0;
        } catch (PDOException $e) {
            // Детальное логирование ошибки БД
            Logger::error('Ошибка БД при создании вещи', [
                'sql_state' => $e->getCode(),
                'message' => $e->getMessage(),
                'sql' => $sql,
                'columns' => $columns,
                'error_info' => $stmt->errorInfo() ?? null
            ]);
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Обновить запись с бинарными данными (BYTEA)
    private function updateWithBinaryData(int $id, array $data): bool
    {
        // Отделяем бинарные данные от обычных
        $imageData = $data['image_data'] ?? null;
        $imageMimeType = $data['image_mime_type'] ?? null;
        unset($data['image_data'], $data['image_mime_type']);
        
        // Фильтруем данные по fillable
        $filteredData = array_intersect_key($data, array_flip($this->fillable));

        if (empty($filteredData) && $imageData === null) {
            throw new \RuntimeException("No fillable fields provided for update");
        }

        // Строим SET clause
        $setParts = array_map(fn($key) => "{$key} = :{$key}", array_keys($filteredData));
        
        // Добавляем image_data и image_mime_type если есть
        if ($imageData !== null) {
            $setParts[] = 'image_data = :image_data';
            $setParts[] = 'image_mime_type = :image_mime_type';
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts) . " WHERE {$this->primaryKey} = :id";

        try {
            $stmt = $this->db->prepare($sql);
            
            // Привязываем обычные параметры
            foreach ($filteredData as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            // Привязываем бинарные данные для BYTEA
            if ($imageData !== null) {
                // Для PostgreSQL BYTEA используем hex-формат через параметр
                // Конвертируем бинарные данные в hex-строку с префиксом \x
                $hexData = '\\x' . bin2hex($imageData);
                $stmt->bindValue(':image_data', $hexData, PDO::PARAM_STR);
                $stmt->bindValue(':image_mime_type', $imageMimeType ?? 'image/jpeg');
            }
            
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            return $result && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    // Сжатие изображения
    private function compressImage(string $imageData, string $mimeType): string
    {
        $image = imagecreatefromstring($imageData);
        if (!$image) {
            throw new \RuntimeException("Не удалось создать изображение из данных");
        }

        $maxWidth = 1200;
        $maxHeight = 1200;

        $width = imagesx($image);
        $height = imagesy($image);

        // Вычисляем новые размеры
        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = (int) ($width * $ratio);
            $newHeight = (int) ($height * $ratio);

            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $newImage;
        }

        // Сохраняем в нужном формате
        ob_start();
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($image, null, 85);
                break;
            case 'image/png':
                imagepng($image, null, 8);
                break;
            case 'image/gif':
                imagegif($image);
                break;
            case 'image/webp':
                imagewebp($image, null, 85);
                break;
            default:
                imagejpeg($image, null, 85);
        }
        $compressed = ob_get_clean();
        imagedestroy($image);

        return $compressed;
    }

    // Получить общее количество вещей пользователя
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
