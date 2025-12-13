-- 1. Таблица пользователей (убрали avatar_path и settings)
CREATE TABLE IF NOT EXISTS users (
                                     id SERIAL PRIMARY KEY,
                                     email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

-- Индекс для быстрого поиска по email
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_indexes WHERE tablename = 'users' AND indexname = 'idx_users_email') THEN
CREATE INDEX idx_users_email ON users(email);
END IF;
END $$;

-- 2. Таблица категорий вещей (убрали icon)
CREATE TABLE IF NOT EXISTS categories (
                                          id SERIAL PRIMARY KEY,
                                          name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
    );

-- Предзаполнение базовых категорий (вставляем только если их нет)
INSERT INTO categories (name, description)
VALUES
    ('Верх', 'Футболки, рубашки, блузы, свитера'),
    ('Низ', 'Брюки, джинсы, юбки, шорты'),
    ('Платье/Костюм', 'Платья, комбинезоны, костюмы'),
    ('Обувь', 'Туфли, кроссовки, сапоги, ботинки'),
    ('Верхняя одежда', 'Пальто, куртки, пуховики'),
    ('Аксессуар', 'Сумки, шарфы, головные уборы, украшения'),
    ('Белье', 'Нижнее белье, носки, колготки')
    ON CONFLICT (name) DO NOTHING;

-- 3. Таблица сезонности
CREATE TABLE IF NOT EXISTS seasons (
                                       id SERIAL PRIMARY KEY,
                                       name VARCHAR(50) NOT NULL UNIQUE
    );

INSERT INTO seasons (name)
VALUES
    ('Лето'),
    ('Зима'),
    ('Демисезон'),
    ('Всесезон')
    ON CONFLICT (name) DO NOTHING;

-- 4. Таблица цветов (убрали is_basic)
CREATE TABLE IF NOT EXISTS colors (
                                      id SERIAL PRIMARY KEY,
                                      name VARCHAR(50) NOT NULL UNIQUE,
    hex_code VARCHAR(7)
    );

-- Базовые цвета (вставляем только если их нет)
INSERT INTO colors (name, hex_code)
VALUES
    ('Черный', '#000000'),
    ('Белый', '#FFFFFF'),
    ('Серый', '#808080'),
    ('Бежевый', '#F5F5DC'),
    ('Коричневый', '#A52A2A'),
    ('Синий', '#0000FF'),
    ('Голубой', '#ADD8E6'),
    ('Зеленый', '#008000'),
    ('Красный', '#FF0000'),
    ('Розовый', '#FFC0CB'),
    ('Желтый', '#FFFF00'),
    ('Оранжевый', '#FFA500'),
    ('Фиолетовый', '#800080')
    ON CONFLICT (name) DO NOTHING;

-- 5. Таблица вещей (убрали таблицы materials и brands, добавили хранение фото в БД)
CREATE TABLE IF NOT EXISTS items (
                                     id SERIAL PRIMARY KEY,
                                     user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(200) NOT NULL,
    category_id INTEGER NOT NULL REFERENCES categories(id),
    color_id INTEGER REFERENCES colors(id),
    season_id INTEGER REFERENCES seasons(id),
    -- Хранение фотографии прямо в БД (BYTEA для бинарных данных)
    image_data BYTEA NOT NULL,
    image_mime_type VARCHAR(50) NOT NULL,
    -- Текстовое поле для заметок
    notes TEXT,
    -- Системные поля
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Индекс использования (вычисляемое поле, обновляемое триггером)
    usage_count INTEGER DEFAULT 0
    );

-- Индексы для быстрой фильтрации
DO $$
BEGIN
    -- Создаем индексы только если они не существуют
    IF NOT EXISTS (SELECT 1 FROM pg_indexes WHERE tablename = 'items' AND indexname = 'idx_items_user') THEN
CREATE INDEX idx_items_user ON items(user_id);
END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_indexes WHERE tablename = 'items' AND indexname = 'idx_items_category') THEN
CREATE INDEX idx_items_category ON items(category_id);
END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_indexes WHERE tablename = 'items' AND indexname = 'idx_items_color') THEN
CREATE INDEX idx_items_color ON items(color_id);
END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_indexes WHERE tablename = 'items' AND indexname = 'idx_items_season') THEN
CREATE INDEX idx_items_season ON items(season_id);
END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_indexes WHERE tablename = 'items' AND indexname = 'idx_items_usage') THEN
CREATE INDEX idx_items_usage ON items(usage_count);
END IF;
END $$;

-- 6. Таблица тегов (гибкая таксономия)
CREATE TABLE IF NOT EXISTS tags (
                                    id SERIAL PRIMARY KEY,
                                    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(7) DEFAULT '#6B7280',
    is_system BOOLEAN DEFAULT false,
    UNIQUE(user_id, name)
    );

-- Системные теги (общие для всех пользователей) - вставляем только если их нет
INSERT INTO tags (name, color, is_system)
VALUES
    ('Офис', '#3B82F6', true),
    ('Кэжуал', '#10B981', true),
    ('Праздник', '#EC4899', true),
    ('Спорт', '#F59E0B', true),
    ('Повседневный', '#6B7280', true),
    ('Деловой', '#1E40AF', true),
    ('Отпуск', '#06B6D4', true);

-- 7. Связь вещей и тегов (многие-ко-многим)
CREATE TABLE IF NOT EXISTS item_tags (
                                         item_id INTEGER NOT NULL REFERENCES items(id) ON DELETE CASCADE,
    tag_id INTEGER NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (item_id, tag_id)
    );

-- 8. Таблица образов (луков) (убрали occasion и image_path)
CREATE TABLE IF NOT EXISTS outfits (
                                       id SERIAL PRIMARY KEY,
                                       user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    -- Метаданные образа
    formality_level INTEGER CHECK (formality_level BETWEEN 1 AND 5),
    season_id INTEGER REFERENCES seasons(id),
    -- Системные поля
    is_favorite BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

-- 9. Связь образов и вещей (многие-ко-многим)
CREATE TABLE IF NOT EXISTS outfit_items (
                                            outfit_id INTEGER NOT NULL REFERENCES outfits(id) ON DELETE CASCADE,
    item_id INTEGER NOT NULL REFERENCES items(id) ON DELETE CASCADE,
    position INTEGER NOT NULL, -- Порядок отображения (0 - верх, 1 - низ и т.д.)
    PRIMARY KEY (outfit_id, item_id)
    );

-- 10. Связь образов и тегов
CREATE TABLE IF NOT EXISTS outfit_tags (
                                           outfit_id INTEGER NOT NULL REFERENCES outfits(id) ON DELETE CASCADE,
    tag_id INTEGER NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (outfit_id, tag_id)
    );

-- 11. Таблица капсул (убрали duration_days и is_active)
CREATE TABLE IF NOT EXISTS capsules (
                                        id SERIAL PRIMARY KEY,
                                        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    -- Параметры капсулы
    season_id INTEGER REFERENCES seasons(id),
    -- Системные поля
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

-- 12. Связь капсул и вещей
CREATE TABLE IF NOT EXISTS capsule_items (
                                             capsule_id INTEGER NOT NULL REFERENCES capsules(id) ON DELETE CASCADE,
    item_id INTEGER NOT NULL REFERENCES items(id) ON DELETE CASCADE,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (capsule_id, item_id)
    );

-- 13. Связь капсул и образов
CREATE TABLE IF NOT EXISTS capsule_outfits (
                                               capsule_id INTEGER NOT NULL REFERENCES capsules(id) ON DELETE CASCADE,
    outfit_id INTEGER NOT NULL REFERENCES outfits(id) ON DELETE CASCADE,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (capsule_id, outfit_id)
    );

-- 14. Таблица истории использования (для аналитики) (убрали action_data)
CREATE TABLE IF NOT EXISTS usage_history (
                                             id SERIAL PRIMARY KEY,
                                             user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    item_id INTEGER REFERENCES items(id) ON DELETE SET NULL,
    outfit_id INTEGER REFERENCES outfits(id) ON DELETE SET NULL,
    capsule_id INTEGER REFERENCES capsules(id) ON DELETE SET NULL,
    action_type VARCHAR(50) NOT NULL, -- 'view', 'add_to_outfit', 'wear', etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

-- Индекс для аналитики
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_indexes WHERE tablename = 'usage_history' AND indexname = 'idx_usage_history_user') THEN
CREATE INDEX idx_usage_history_user ON usage_history(user_id);
END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_indexes WHERE tablename = 'usage_history' AND indexname = 'idx_usage_history_item') THEN
CREATE INDEX idx_usage_history_item ON usage_history(item_id);
END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_indexes WHERE tablename = 'usage_history' AND indexname = 'idx_usage_history_date') THEN
CREATE INDEX idx_usage_history_date ON usage_history(created_at);
END IF;
END $$;

-- Триггеры и функции

-- Функция для автоматического обновления updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
RETURN NEW;
END;
$$ language 'plpgsql';

-- Удаляем существующие триггеры и создаем заново (для обеспечения правильности логики)
DROP TRIGGER IF EXISTS update_users_updated_at ON users;
CREATE TRIGGER update_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_items_updated_at ON items;
CREATE TRIGGER update_items_updated_at
    BEFORE UPDATE ON items
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_outfits_updated_at ON outfits;
CREATE TRIGGER update_outfits_updated_at
    BEFORE UPDATE ON outfits
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_capsules_updated_at ON capsules;
CREATE TRIGGER update_capsules_updated_at
    BEFORE UPDATE ON capsules
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Функция и триггер для обновления счетчика использования вещей
CREATE OR REPLACE FUNCTION update_item_usage_count()
RETURNS TRIGGER AS $$
BEGIN
    -- Обновляем usage_count для вещей на основе их участия в образах
UPDATE items
SET usage_count = (
                      SELECT COUNT(DISTINCT outfit_id)
                      FROM outfit_items
                      WHERE item_id = items.id
                  ) + (
                      SELECT COUNT(DISTINCT capsule_id)
                      FROM capsule_items
                      WHERE item_id = items.id
                  )
WHERE id IN (
    SELECT DISTINCT item_id FROM outfit_items
    UNION
    SELECT DISTINCT item_id FROM capsule_items
);

RETURN NEW;
END;
$$ language 'plpgsql';

-- Триггеры, которые запускают обновление счетчика использования
DROP TRIGGER IF EXISTS update_usage_after_outfit_change ON outfit_items;
CREATE TRIGGER update_usage_after_outfit_change
    AFTER INSERT OR DELETE OR UPDATE ON outfit_items
    FOR EACH STATEMENT
    EXECUTE FUNCTION update_item_usage_count();

DROP TRIGGER IF EXISTS update_usage_after_capsule_change ON capsule_items;
CREATE TRIGGER update_usage_after_capsule_change
    AFTER INSERT OR DELETE OR UPDATE ON capsule_items
    FOR EACH STATEMENT
    EXECUTE FUNCTION update_item_usage_count();

-- Комментарии к таблицам и столбцам (добавляем только если их еще нет)
COMMENT ON TABLE items IS 'Основная таблица вещей в гардеробе';
COMMENT ON TABLE outfits IS 'Сохраненные образы (луки)';
COMMENT ON TABLE capsules IS 'Капсулы - группы вещей для определенного периода/цели';
COMMENT ON TABLE usage_history IS 'История действий для аналитики и рекомендаций';
COMMENT ON COLUMN items.image_data IS 'Бинарные данные изображения вещи';
COMMENT ON COLUMN items.image_mime_type IS 'MIME-тип изображения (например, image/jpeg, image/png)';

-- Создание представлений для удобных запросов (удаляем старые и создаем заново)

-- Представление для отображения вещей с дополнительной информацией
DROP VIEW IF EXISTS items_view;
CREATE VIEW items_view AS
SELECT
    i.id,
    i.user_id,
    i.name,
    i.category_id,
    i.color_id,
    i.season_id,
    i.notes,
    i.created_at,
    i.updated_at,
    i.usage_count,
    i.image_data,
    i.image_mime_type,
    c.name as category_name,
    s.name as season_name,
    cl.name as color_name,
    cl.hex_code as color_hex,
    u.username as user_username,
    ARRAY(
        SELECT t.name
        FROM tags t
        JOIN item_tags it ON t.id = it.tag_id
        WHERE it.item_id = i.id
    ) as tags
FROM items i
         LEFT JOIN categories c ON i.category_id = c.id
         LEFT JOIN seasons s ON i.season_id = s.id
         LEFT JOIN colors cl ON i.color_id = cl.id
         LEFT JOIN users u ON i.user_id = u.id;

-- Представление для аналитики распределения по категориям
DROP VIEW IF EXISTS category_distribution;
CREATE VIEW category_distribution AS
SELECT
    u.id as user_id,
    c.name as category,
    COUNT(i.id) as item_count,
    ROUND(COUNT(i.id) * 100.0 / NULLIF(SUM(COUNT(i.id)) OVER (PARTITION BY u.id), 0), 2) as percentage
FROM users u
         LEFT JOIN items i ON u.id = i.user_id
         LEFT JOIN categories c ON i.category_id = c.id
GROUP BY u.id, c.name;

-- Представление для сочетаемости вещей
DROP VIEW IF EXISTS item_compatibility;
CREATE VIEW item_compatibility AS
SELECT
    i1.id as item1_id,
    i1.name as item1_name,
    i2.id as item2_id,
    i2.name as item2_name,
    COUNT(DISTINCT o.id) as times_paired,
    ARRAY_AGG(DISTINCT t.name) as common_tags
FROM outfit_items oi1
         JOIN outfit_items oi2 ON oi1.outfit_id = oi2.outfit_id AND oi1.item_id < oi2.item_id
         JOIN items i1 ON oi1.item_id = i1.id
         JOIN items i2 ON oi2.item_id = i2.id
         JOIN outfits o ON oi1.outfit_id = o.id
         LEFT JOIN item_tags it1 ON i1.id = it1.item_id
         LEFT JOIN item_tags it2 ON i2.id = it2.item_id AND it1.tag_id = it2.tag_id
         LEFT JOIN tags t ON it1.tag_id = t.id
GROUP BY i1.id, i1.name, i2.id, i2.name
ORDER BY times_paired DESC;

-- Представление для капсул с информацией о содержащихся вещах
DROP VIEW IF EXISTS capsules_view;
CREATE VIEW capsules_view AS
SELECT
    cp.id,
    cp.user_id,
    cp.name,
    cp.description,
    cp.season_id,
    cp.created_at,
    cp.updated_at,
    s.name as season_name,
    COUNT(DISTINCT ci.item_id) as items_count,
    COUNT(DISTINCT co.outfit_id) as outfits_count,
    ARRAY(
        SELECT DISTINCT i.name
        FROM capsule_items ci2
        JOIN items i ON ci2.item_id = i.id
        WHERE ci2.capsule_id = cp.id
        LIMIT 5
    ) as sample_items
FROM capsules cp
         LEFT JOIN seasons s ON cp.season_id = s.id
         LEFT JOIN capsule_items ci ON cp.id = ci.capsule_id
         LEFT JOIN capsule_outfits co ON cp.id = co.capsule_id
GROUP BY cp.id, cp.user_id, cp.name, cp.description, cp.season_id, cp.created_at, cp.updated_at, s.name;