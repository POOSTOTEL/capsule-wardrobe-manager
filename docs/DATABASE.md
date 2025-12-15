# Документация базы данных

## Обзор

База данных приложения "Капсульный Гардероб" построена на PostgreSQL 15. Используется реляционная модель данных с нормализацией для обеспечения целостности данных и эффективности запросов.

## Схема базы данных

### Диаграмма связей

```
users
  ├── items (1:N)
  ├── outfits (1:N)
  ├── capsules (1:N)
  └── tags (1:N, опционально)

categories (справочник)
  └── items (1:N)

colors (справочник)
  └── items (1:N)

seasons (справочник)
  ├── items (1:N)
  ├── outfits (1:N)
  └── capsules (1:N)

items
  ├── item_tags (N:M через item_tags)
  ├── outfit_items (N:M через outfit_items)
  └── capsule_items (N:M через capsule_items)

tags
  ├── item_tags (N:M через item_tags)
  └── outfit_tags (N:M через outfit_tags)

outfits
  ├── outfit_items (N:M через outfit_items)
  ├── outfit_tags (N:M через outfit_tags)
  └── capsule_outfits (N:M через capsule_outfits)

capsules
  ├── capsule_items (N:M через capsule_items)
  └── capsule_outfits (N:M через capsule_outfits)

usage_history
  ├── items (N:1, опционально)
  ├── outfits (N:1, опционально)
  └── capsules (N:1, опционально)
```

## Таблицы

### 1. users (Пользователи)

Хранение информации о пользователях системы.

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| id | SERIAL | PRIMARY KEY | Уникальный идентификатор |
| email | VARCHAR(255) | UNIQUE, NOT NULL | Email пользователя |
| username | VARCHAR(100) | UNIQUE, NOT NULL | Имя пользователя |
| password_hash | VARCHAR(255) | NOT NULL | Хеш пароля (bcrypt) |
| full_name | VARCHAR(200) | NULL | Полное имя |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Дата создания |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Дата обновления |

**Индексы:**
- `idx_users_email` - на поле `email` (для быстрого поиска)

**Триггеры:**
- `update_users_updated_at` - автоматическое обновление `updated_at` при изменении записи

### 2. categories (Категории)

Справочник категорий вещей (верх, низ, обувь и т.д.).

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| id | SERIAL | PRIMARY KEY | Уникальный идентификатор |
| name | VARCHAR(50) | UNIQUE, NOT NULL | Название категории |
| description | TEXT | NULL | Описание категории |

**Предзаполненные данные:**
- Верх
- Низ
- Платье/Костюм
- Обувь
- Верхняя одежда
- Аксессуар
- Белье

### 3. seasons (Сезоны)

Справочник сезонов.

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| id | SERIAL | PRIMARY KEY | Уникальный идентификатор |
| name | VARCHAR(50) | UNIQUE, NOT NULL | Название сезона |

**Предзаполненные данные:**
- Лето
- Зима
- Демисезон
- Всесезон

### 4. colors (Цвета)

Справочник цветов с HEX кодами.

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| id | SERIAL | PRIMARY KEY | Уникальный идентификатор |
| name | VARCHAR(50) | UNIQUE, NOT NULL | Название цвета |
| hex_code | VARCHAR(7) | NULL | HEX код цвета |

**Предзаполненные данные:**
- Черный (#000000)
- Белый (#FFFFFF)
- Серый (#808080)
- Бежевый (#F5F5DC)
- Коричневый (#A52A2A)
- Синий (#0000FF)
- Голубой (#ADD8E6)
- Зеленый (#008000)
- Красный (#FF0000)
- Розовый (#FFC0CB)
- Желтый (#FFFF00)
- Оранжевый (#FFA500)
- Фиолетовый (#800080)

### 5. items (Вещи)

Основная таблица вещей в гардеробе.

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| id | SERIAL | PRIMARY KEY | Уникальный идентификатор |
| user_id | INTEGER | NOT NULL, FK → users(id) | Владелец вещи |
| name | VARCHAR(200) | NOT NULL | Название вещи |
| category_id | INTEGER | NOT NULL, FK → categories(id) | Категория |
| color_id | INTEGER | NULL, FK → colors(id) | Цвет |
| season_id | INTEGER | NULL, FK → seasons(id) | Сезон |
| image_data | BYTEA | NOT NULL | Бинарные данные изображения |
| image_mime_type | VARCHAR(50) | NOT NULL | MIME-тип изображения |
| notes | TEXT | NULL | Заметки |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Дата создания |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Дата обновления |
| usage_count | INTEGER | DEFAULT 0 | Счетчик использования |

**Индексы:**
- `idx_items_user` - на поле `user_id`
- `idx_items_category` - на поле `category_id`
- `idx_items_color` - на поле `color_id`
- `idx_items_season` - на поле `season_id`
- `idx_items_usage` - на поле `usage_count`

**Триггеры:**
- `update_items_updated_at` - автоматическое обновление `updated_at`
- `update_usage_after_outfit_change` - обновление `usage_count` при изменении в `outfit_items`
- `update_usage_after_capsule_change` - обновление `usage_count` при изменении в `capsule_items`

**Внешние ключи:**
- `ON DELETE CASCADE` для `user_id` - при удалении пользователя удаляются все его вещи

### 6. tags (Теги)

Гибкая система тегирования. Поддерживает системные (общие) и пользовательские теги.

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| id | SERIAL | PRIMARY KEY | Уникальный идентификатор |
| user_id | INTEGER | NULL, FK → users(id) | Владелец тега (NULL для системных) |
| name | VARCHAR(50) | NOT NULL | Название тега |
| color | VARCHAR(7) | DEFAULT '#6B7280' | HEX код цвета тега |
| is_system | BOOLEAN | DEFAULT false | Системный тег (нельзя удалить) |

**Уникальные ограничения:**
- `UNIQUE(user_id, name)` - уникальность названия тега для пользователя

**Предзаполненные системные теги:**
- Офис (#3B82F6)
- Кэжуал (#10B981)
- Праздник (#EC4899)
- Спорт (#F59E0B)
- Повседневный (#6B7280)
- Деловой (#1E40AF)
- Отпуск (#06B6D4)

**Внешние ключи:**
- `ON DELETE CASCADE` для `user_id` - при удалении пользователя удаляются его теги

### 7. item_tags (Связь вещей и тегов)

Связь многие-ко-многим между вещами и тегами.

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| item_id | INTEGER | NOT NULL, FK → items(id) | ID вещи |
| tag_id | INTEGER | NOT NULL, FK → tags(id) | ID тега |

**Первичный ключ:**
- `PRIMARY KEY (item_id, tag_id)`

**Внешние ключи:**
- `ON DELETE CASCADE` - при удалении вещи или тега удаляется связь

### 8. outfits (Образы)

Сохраненные образы (луки) - комбинации вещей.

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| id | SERIAL | PRIMARY KEY | Уникальный идентификатор |
| user_id | INTEGER | NOT NULL, FK → users(id) | Владелец образа |
| name | VARCHAR(200) | NOT NULL | Название образа |
| description | TEXT | NULL | Описание образа |
| formality_level | INTEGER | CHECK (1-5) | Уровень формальности (1-5) |
| season_id | INTEGER | NULL, FK → seasons(id) | Сезон |
| is_favorite | BOOLEAN | DEFAULT false | Избранный образ |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Дата создания |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Дата обновления |

**Триггеры:**
- `update_outfits_updated_at` - автоматическое обновление `updated_at`

**Внешние ключи:**
- `ON DELETE CASCADE` для `user_id` - при удалении пользователя удаляются все его образы

### 9. outfit_items (Связь образов и вещей)

Связь многие-ко-многим между образами и вещами с позиционированием.

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| outfit_id | INTEGER | NOT NULL, FK → outfits(id) | ID образа |
| item_id | INTEGER | NOT NULL, FK → items(id) | ID вещи |
| position | INTEGER | NOT NULL | Позиция вещи (0 - верх, 1 - низ и т.д.) |

**Первичный ключ:**
- `PRIMARY KEY (outfit_id, item_id)`

**Внешние ключи:**
- `ON DELETE CASCADE` - при удалении образа или вещи удаляется связь

**Триггеры:**
- `update_usage_after_outfit_change` - обновление `usage_count` в таблице `items`

### 10. outfit_tags (Связь образов и тегов)

Связь многие-ко-многим между образами и тегами.

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| outfit_id | INTEGER | NOT NULL, FK → outfits(id) | ID образа |
| tag_id | INTEGER | NOT NULL, FK → tags(id) | ID тега |

**Первичный ключ:**
- `PRIMARY KEY (outfit_id, tag_id)`

**Внешние ключи:**
- `ON DELETE CASCADE` - при удалении образа или тега удаляется связь

### 11. capsules (Капсулы)

Группы вещей и образов для определенного периода или цели.

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| id | SERIAL | PRIMARY KEY | Уникальный идентификатор |
| user_id | INTEGER | NOT NULL, FK → users(id) | Владелец капсулы |
| name | VARCHAR(200) | NOT NULL | Название капсулы |
| description | TEXT | NULL | Описание капсулы |
| season_id | INTEGER | NULL, FK → seasons(id) | Сезон |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Дата создания |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Дата обновления |

**Триггеры:**
- `update_capsules_updated_at` - автоматическое обновление `updated_at`

**Внешние ключи:**
- `ON DELETE CASCADE` для `user_id` - при удалении пользователя удаляются все его капсулы

### 12. capsule_items (Связь капсул и вещей)

Связь многие-ко-многим между капсулами и вещами.

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| capsule_id | INTEGER | NOT NULL, FK → capsules(id) | ID капсулы |
| item_id | INTEGER | NOT NULL, FK → items(id) | ID вещи |
| added_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Дата добавления |

**Первичный ключ:**
- `PRIMARY KEY (capsule_id, item_id)`

**Внешние ключи:**
- `ON DELETE CASCADE` - при удалении капсулы или вещи удаляется связь

**Триггеры:**
- `update_usage_after_capsule_change` - обновление `usage_count` в таблице `items`

### 13. capsule_outfits (Связь капсул и образов)

Связь многие-ко-многим между капсулами и образами.

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| capsule_id | INTEGER | NOT NULL, FK → capsules(id) | ID капсулы |
| outfit_id | INTEGER | NOT NULL, FK → outfits(id) | ID образа |
| added_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Дата добавления |

**Первичный ключ:**
- `PRIMARY KEY (capsule_id, outfit_id)`

**Внешние ключи:**
- `ON DELETE CASCADE` - при удалении капсулы или образа удаляется связь

### 14. usage_history (История использования)

История действий пользователя для аналитики и рекомендаций.

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| id | SERIAL | PRIMARY KEY | Уникальный идентификатор |
| user_id | INTEGER | NOT NULL, FK → users(id) | Пользователь |
| item_id | INTEGER | NULL, FK → items(id) | Вещь (если применимо) |
| outfit_id | INTEGER | NULL, FK → outfits(id) | Образ (если применимо) |
| capsule_id | INTEGER | NULL, FK → capsules(id) | Капсула (если применимо) |
| action_type | VARCHAR(50) | NOT NULL | Тип действия (view, add_to_outfit, wear и т.д.) |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Дата действия |

**Индексы:**
- `idx_usage_history_user` - на поле `user_id`
- `idx_usage_history_item` - на поле `item_id`
- `idx_usage_history_date` - на поле `created_at`

**Внешние ключи:**
- `ON DELETE CASCADE` для `user_id`
- `ON DELETE SET NULL` для `item_id`, `outfit_id`, `capsule_id` - сохраняется история даже при удалении объекта

## Представления (Views)

### items_view

Представление для удобного отображения вещей с дополнительной информацией.

**Поля:**
- Все поля из таблицы `items`
- `category_name` - название категории
- `season_name` - название сезона
- `color_name` - название цвета
- `color_hex` - HEX код цвета
- `user_username` - имя пользователя
- `tags` - массив названий тегов

### category_distribution

Представление для аналитики распределения вещей по категориям.

**Поля:**
- `user_id` - ID пользователя
- `category` - название категории
- `item_count` - количество вещей
- `percentage` - процент от общего количества

### item_compatibility

Представление для анализа сочетаемости вещей.

**Поля:**
- `item1_id`, `item1_name` - первая вещь
- `item2_id`, `item2_name` - вторая вещь
- `times_paired` - количество раз, когда вещи были вместе в образах
- `common_tags` - общие теги

### capsules_view

Представление для отображения капсул с информацией о содержимом.

**Поля:**
- Все поля из таблицы `capsules`
- `season_name` - название сезона
- `items_count` - количество вещей
- `outfits_count` - количество образов
- `sample_items` - массив названий первых 5 вещей

## Триггеры и функции

### update_updated_at_column()

Функция для автоматического обновления поля `updated_at` при изменении записи.

**Применяется к таблицам:**
- `users`
- `items`
- `outfits`
- `capsules`

### update_item_usage_count()

Функция для автоматического обновления счетчика использования вещей (`usage_count` в таблице `items`).

**Логика:**
- Считает количество уникальных образов, в которых используется вещь
- Считает количество уникальных капсул, в которых используется вещь
- Суммирует эти значения

**Триггеры:**
- `update_usage_after_outfit_change` - срабатывает при изменении в `outfit_items`
- `update_usage_after_capsule_change` - срабатывает при изменении в `capsule_items`

## Индексы

### Основные индексы

1. **users**
   - `idx_users_email` - для быстрого поиска по email

2. **items**
   - `idx_items_user` - для фильтрации по пользователю
   - `idx_items_category` - для фильтрации по категории
   - `idx_items_color` - для фильтрации по цвету
   - `idx_items_season` - для фильтрации по сезону
   - `idx_items_usage` - для сортировки по использованию

3. **usage_history**
   - `idx_usage_history_user` - для фильтрации по пользователю
   - `idx_usage_history_item` - для фильтрации по вещи
   - `idx_usage_history_date` - для фильтрации по дате

## Ограничения целостности

### Внешние ключи (Foreign Keys)

Все внешние ключи настроены с соответствующими действиями при удалении:

- **CASCADE** - каскадное удаление зависимых записей
  - При удалении пользователя удаляются все его вещи, образы, капсулы, теги
  - При удалении вещи/образа/капсулы удаляются все связи

- **SET NULL** - установка NULL при удалении
  - В `usage_history` при удалении вещи/образа/капсулы сохраняется история с NULL

### Уникальные ограничения

- `users.email` - уникальный email
- `users.username` - уникальное имя пользователя
- `categories.name` - уникальное название категории
- `seasons.name` - уникальное название сезона
- `colors.name` - уникальное название цвета
- `tags(user_id, name)` - уникальное название тега для пользователя

### Проверочные ограничения (CHECK)

- `outfits.formality_level` - значение от 1 до 5

## Хранение изображений

Изображения вещей хранятся непосредственно в базе данных в поле `image_data` типа `BYTEA`. Это обеспечивает:

- **Целостность данных** - изображение не может быть потеряно при удалении файла
- **Упрощение резервного копирования** - все данные в одном месте
- **Избежание проблем с файловой системой** - права доступа, пути и т.д.

**Недостатки:**
- Увеличение размера базы данных
- Потенциально более медленный доступ (компенсируется кешированием)

## Миграции и обновления

База данных инициализируется через файл `docker/postgres/init.sql`, который:

1. Создает все таблицы
2. Создает индексы
3. Создает триггеры и функции
4. Заполняет справочники предустановленными данными
5. Создает представления

При изменении схемы БД необходимо обновить `init.sql` и пересоздать контейнер базы данных.

## Резервное копирование

### Рекомендации

1. **Регулярные бэкапы** - ежедневные полные бэкапы
2. **Инкрементальные бэкапы** - для больших баз данных
3. **Хранение бэкапов** - на отдельном сервере или в облаке
4. **Тестирование восстановления** - регулярная проверка работоспособности бэкапов

### Команды для бэкапа

```bash
# Создание бэкапа
docker exec capsule_postgres pg_dump -U capsule_user capsule_wardrobe > backup.sql

# Восстановление из бэкапа
docker exec -i capsule_postgres psql -U capsule_user capsule_wardrobe < backup.sql
```

## Производительность

### Оптимизация запросов

1. Использование индексов для часто используемых полей
2. JOIN вместо множественных запросов
3. Представления для сложных запросов
4. Триггеры для автоматического обновления счетчиков

### Мониторинг

Рекомендуется мониторить:
- Размер базы данных
- Время выполнения запросов
- Использование индексов
- Количество подключений

## Безопасность

### Рекомендации

1. **Ограничение доступа** - только приложение должно иметь доступ к БД
2. **Сильные пароли** - для пользователя БД
3. **Регулярные обновления** - обновление PostgreSQL до последней версии
4. **Шифрование соединений** - использование SSL для подключений
5. **Резервное копирование** - регулярные бэкапы для восстановления

## Заключение

База данных спроектирована с учетом:
- Нормализации данных
- Целостности данных
- Производительности запросов
- Масштабируемости
- Безопасности

Схема позволяет эффективно хранить и извлекать данные о гардеробе, образах и капсулах, обеспечивая при этом гибкость и расширяемость системы.

