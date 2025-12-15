# API Документация

## Обзор

API приложения "Капсульный Гардероб" предоставляет RESTful интерфейс для управления гардеробом, образами, капсулами и аналитикой. Все эндпоинты требуют аутентификации через сессии (кроме публичных эндпоинтов).

## Базовый URL

```
http://localhost/api
```

## Формат ответов

### Успешный ответ

```json
{
  "success": true,
  "data": {...},
  "message": "Опциональное сообщение"
}
```

### Ошибка

```json
{
  "success": false,
  "message": "Описание ошибки",
  "errors": {
    "field": ["Ошибка валидации"]
  }
}
```

## Коды состояния HTTP

- `200` - Успешный запрос
- `201` - Ресурс создан
- `400` - Ошибка валидации или неверный запрос
- `401` - Требуется аутентификация
- `403` - Доступ запрещен
- `404` - Ресурс не найден
- `405` - Метод не разрешен
- `500` - Внутренняя ошибка сервера

---

## Аутентификация

Аутентификация выполняется через сессии PHP. После успешного входа в систему через веб-интерфейс, сессия автоматически используется для всех API запросов.

### Публичные эндпоинты

- `GET /api/taxonomies` - Получение справочников
- `GET /api/taxonomies/categories` - Получение категорий
- `GET /api/taxonomies/colors` - Получение цветов
- `GET /api/taxonomies/seasons` - Получение сезонов

---

## Справочники (Taxonomies)

### Получить все справочники

```
GET /api/taxonomies
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "categories": [...],
    "colors": [...],
    "seasons": [...]
  }
}
```

### Получить данные для форм

```
GET /api/taxonomies/forms
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "categories": [...],
    "colors": [...],
    "seasons": [...]
  }
}
```

### Получить категории

```
GET /api/taxonomies/categories
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Верх",
      "description": "Футболки, рубашки, блузы, свитера"
    }
  ],
  "count": 7
}
```

### Получить цвета

```
GET /api/taxonomies/colors
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Черный",
      "hex_code": "#000000"
    }
  ],
  "count": 13
}
```

### Получить сезоны

```
GET /api/taxonomies/seasons
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Лето"
    }
  ],
  "count": 4
}
```

---

## Вещи (Items)

### Получить список вещей

```
GET /api/items
```

**Параметры запроса:**
- `category_id` (integer, опционально) - Фильтр по категории
- `color_id` (integer, опционально) - Фильтр по цвету
- `season_id` (integer, опционально) - Фильтр по сезону
- `search` (string, опционально) - Поиск по названию
- `tag_ids` (string, опционально) - Список ID тегов через запятую (например: "1,2,3")
- `order_by` (string, опционально) - Поле сортировки: `created_at`, `name`, `usage_count` (по умолчанию: `created_at`)
- `order_dir` (string, опционально) - Направление сортировки: `ASC`, `DESC` (по умолчанию: `DESC`)
- `limit` (integer, опционально) - Количество записей (по умолчанию: 50)
- `offset` (integer, опционально) - Смещение для пагинации (по умолчанию: 0)

**Пример запроса:**
```
GET /api/items?category_id=1&color_id=2&search=куртка&order_by=name&order_dir=ASC
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Синяя куртка",
      "category_id": 5,
      "category_name": "Верхняя одежда",
      "color_id": 6,
      "color_name": "Синий",
      "color_hex": "#0000FF",
      "season_id": 3,
      "season_name": "Демисезон",
      "notes": "Теплая куртка",
      "usage_count": 5,
      "image_url": "/api/items/1/image",
      "tags": [
        {
          "id": 1,
          "name": "Повседневный",
          "color": "#6B7280"
        }
      ],
      "created_at": "2024-01-15 10:30:45",
      "updated_at": "2024-01-15 10:30:45"
    }
  ],
  "count": 1
}
```

### Получить вещь по ID

```
GET /api/items/{id}
```

**Параметры пути:**
- `id` (integer, обязательно) - ID вещи

**Ответ:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Синяя куртка",
    "category_id": 5,
    "category_name": "Верхняя одежда",
    "color_id": 6,
    "color_name": "Синий",
    "color_hex": "#0000FF",
    "season_id": 3,
    "season_name": "Демисезон",
    "notes": "Теплая куртка",
    "usage_count": 5,
    "image_url": "/api/items/1/image",
    "tags": [...],
    "created_at": "2024-01-15 10:30:45",
    "updated_at": "2024-01-15 10:30:45"
  }
}
```

### Получить изображение вещи

```
GET /api/items/{id}/image
```

**Параметры пути:**
- `id` (integer, обязательно) - ID вещи

**Ответ:** Бинарные данные изображения с соответствующим `Content-Type` заголовком

**Заголовки ответа:**
- `Content-Type: image/jpeg` (или другой MIME-тип)
- `Content-Length: <размер файла>`
- `Cache-Control: public, max-age=31536000`

### Создать вещь

```
POST /items
Content-Type: multipart/form-data
```

**Поля формы:**
- `name` (string, обязательно) - Название вещи (макс. 200 символов)
- `category_id` (integer, обязательно) - ID категории
- `image` (file, обязательно) - Изображение вещи (JPEG, PNG, GIF, WebP, макс. 5MB)
- `color_id` (integer, опционально) - ID цвета
- `season_id` (integer, опционально) - ID сезона
- `tags` (string, опционально) - Список ID тегов через запятую (например: "1,2,3")
- `notes` (string, опционально) - Заметки (макс. 1000 символов)

**Пример запроса:**
```javascript
const formData = new FormData();
formData.append('name', 'Синяя куртка');
formData.append('category_id', 5);
formData.append('image', fileInput.files[0]);
formData.append('color_id', 6);
formData.append('season_id', 3);
formData.append('tags', '1,2');
formData.append('notes', 'Теплая куртка');

fetch('/items', {
  method: 'POST',
  body: formData
});
```

**Ответ:**
```json
{
  "success": true,
  "message": "Вещь успешно добавлена",
  "data": {
    "id": 1,
    "redirect_url": "/items"
  }
}
```

**Ошибки валидации:**
```json
{
  "success": false,
  "message": "Ошибки валидации",
  "errors": {
    "name": ["Название вещи обязательно для заполнения"],
    "category_id": ["Категория обязательна для выбора"],
    "image": ["Ошибка загрузки изображения"]
  }
}
```

### Обновить вещь

```
POST /items/{id}
PUT /items/{id}
PATCH /items/{id}
Content-Type: multipart/form-data
```

**Параметры пути:**
- `id` (integer, обязательно) - ID вещи

**Поля формы:** (все опциональны, кроме тех, что нужно изменить)
- `name` (string) - Название вещи
- `category_id` (integer) - ID категории
- `image` (file) - Новое изображение (если нужно заменить)
- `color_id` (integer) - ID цвета
- `season_id` (integer) - ID сезона
- `tags` (string) - Список ID тегов через запятую
- `notes` (string) - Заметки

**Ответ:**
```json
{
  "success": true,
  "message": "Вещь успешно обновлена",
  "data": {
    "id": 1,
    "name": "Обновленное название",
    ...
  }
}
```

### Удалить вещь

```
DELETE /api/items/{id}
POST /items/{id}/delete
```

**Параметры пути:**
- `id` (integer, обязательно) - ID вещи

**Ответ:**
```json
{
  "success": true,
  "message": "Вещь успешно удалена"
}
```

---

## Образы (Outfits)

### Получить список образов

```
GET /api/outfits
```

**Параметры запроса:**
- `season_id` (integer, опционально) - Фильтр по сезону
- `formality_level` (integer, опционально) - Фильтр по уровню формальности (1-5)
- `is_favorite` (boolean, опционально) - Фильтр по избранным
- `search` (string, опционально) - Поиск по названию
- `tag_ids` (string, опционально) - Список ID тегов через запятую
- `order_by` (string, опционально) - Поле сортировки: `created_at`, `name` (по умолчанию: `created_at`)
- `order_dir` (string, опционально) - Направление сортировки: `ASC`, `DESC` (по умолчанию: `DESC`)
- `limit` (integer, опционально) - Количество записей (по умолчанию: 50)
- `offset` (integer, опционально) - Смещение для пагинации

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Офисный образ",
      "description": "Деловой стиль",
      "formality_level": 4,
      "season_id": 3,
      "season_name": "Демисезон",
      "is_favorite": true,
      "items": [
        {
          "id": 1,
          "name": "Белая рубашка",
          "position": 0
        }
      ],
      "tags": [...],
      "created_at": "2024-01-15 10:30:45",
      "updated_at": "2024-01-15 10:30:45"
    }
  ],
  "count": 1
}
```

### Получить образ по ID

```
GET /api/outfits/{id}
```

**Параметры пути:**
- `id` (integer, обязательно) - ID образа

**Ответ:** Аналогичен элементу массива в списке образов

### Создать образ

```
POST /outfits
Content-Type: application/x-www-form-urlencoded
```

**Поля формы:**
- `name` (string, обязательно) - Название образа (макс. 200 символов)
- `description` (string, опционально) - Описание (макс. 1000 символов)
- `formality_level` (integer, опционально) - Уровень формальности (1-5)
- `season_id` (integer, опционально) - ID сезона
- `is_favorite` (boolean, опционально) - Добавить в избранное
- `item_ids` (array или string, опционально) - Массив ID вещей или строка через запятую
- `tag_ids` (array или string, опционально) - Массив ID тегов или строка через запятую

**Пример запроса:**
```javascript
fetch('/outfits', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/x-www-form-urlencoded'
  },
  body: new URLSearchParams({
    name: 'Офисный образ',
    description: 'Деловой стиль',
    formality_level: 4,
    season_id: 3,
    is_favorite: true,
    item_ids: [1, 2, 3],
    tag_ids: [1, 2]
  })
});
```

**Ответ:**
```json
{
  "success": true,
  "message": "Образ успешно создан",
  "data": {
    "id": 1,
    "outfit": {...},
    "redirect_url": "/outfits"
  }
}
```

### Обновить образ

```
POST /outfits/{id}
PUT /outfits/{id}
PATCH /outfits/{id}
Content-Type: application/x-www-form-urlencoded
```

**Параметры пути:**
- `id` (integer, обязательно) - ID образа

**Поля формы:** (все опциональны)
- Аналогичны полям при создании

**Ответ:**
```json
{
  "success": true,
  "message": "Образ успешно обновлен",
  "data": {...}
}
```

### Удалить образ

```
DELETE /api/outfits/{id}
POST /outfits/{id}/delete
```

**Параметры пути:**
- `id` (integer, обязательно) - ID образа

**Ответ:**
```json
{
  "success": true,
  "message": "Образ успешно удален"
}
```

### Переключить избранное

```
POST /outfits/{id}/favorite
```

**Параметры пути:**
- `id` (integer, обязательно) - ID образа

**Ответ:**
```json
{
  "success": true,
  "message": "Статус избранного обновлен",
  "data": {
    "is_favorite": true
  }
}
```

### Добавить вещь в образ

```
POST /outfits/{id}/items
Content-Type: application/x-www-form-urlencoded
```

**Параметры пути:**
- `id` (integer, обязательно) - ID образа

**Поля формы:**
- `item_id` (integer, обязательно) - ID вещи
- `position` (integer, опционально) - Позиция вещи в образе

**Ответ:**
```json
{
  "success": true,
  "message": "Вещь успешно добавлена в образ",
  "data": {...}
}
```

### Удалить вещь из образа

```
DELETE /outfits/{id}/items/{itemId}
POST /outfits/{id}/items/remove
Content-Type: application/x-www-form-urlencoded
```

**Параметры пути:**
- `id` (integer, обязательно) - ID образа
- `itemId` (integer, обязательно) - ID вещи (для DELETE метода)

**Поля формы (для POST):**
- `item_id` (integer, обязательно) - ID вещи

**Ответ:**
```json
{
  "success": true,
  "message": "Вещь успешно удалена из образа",
  "data": {...}
}
```

### Добавить образ в капсулу

```
POST /outfits/{id}/capsule
Content-Type: application/x-www-form-urlencoded
```

**Параметры пути:**
- `id` (integer, обязательно) - ID образа

**Поля формы:**
- `capsule_id` (integer, обязательно) - ID капсулы

**Ответ:**
```json
{
  "success": true,
  "message": "Вещи из образа успешно добавлены в капсулу"
}
```

### Сгенерировать образы из капсулы

```
POST /api/capsules/{id}/generate-outfits
Content-Type: application/x-www-form-urlencoded
```

**Параметры пути:**
- `id` (integer, обязательно) - ID капсулы

**Поля формы:**
- `count` (integer, опционально) - Количество образов для генерации (1-50, по умолчанию: 10)

**Ответ:**
```json
{
  "success": true,
  "message": "Образы успешно сгенерированы",
  "data": {
    "outfits": [
      {
        "id": null,
        "name": "Сгенерированный образ 1",
        "items": [...]
      }
    ],
    "count": 10
  }
}
```

### Сохранить сгенерированный образ

```
POST /api/capsules/{id}/save-outfit
Content-Type: application/x-www-form-urlencoded
```

**Параметры пути:**
- `id` (integer, обязательно) - ID капсулы

**Поля формы:**
- `name` (string, обязательно) - Название образа
- `description` (string, опционально) - Описание
- `formality_level` (integer, опционально) - Уровень формальности
- `season_id` (integer, опционально) - ID сезона
- `is_favorite` (boolean, опционально) - Добавить в избранное
- `item_ids` (array или string, обязательно) - Массив ID вещей
- `tag_ids` (array или string, опционально) - Массив ID тегов

**Ответ:**
```json
{
  "success": true,
  "message": "Образ успешно сохранен",
  "data": {
    "id": 1,
    "outfit": {...}
  }
}
```

---

## Капсулы (Capsules)

### Получить список капсул

```
GET /api/capsules
```

**Параметры запроса:**
- `season_id` (integer, опционально) - Фильтр по сезону
- `search` (string, опционально) - Поиск по названию
- `order_by` (string, опционально) - Поле сортировки: `created_at`, `name` (по умолчанию: `created_at`)
- `order_dir` (string, опционально) - Направление сортировки: `ASC`, `DESC` (по умолчанию: `DESC`)
- `limit` (integer, опционально) - Количество записей (по умолчанию: 50)
- `offset` (integer, опционально) - Смещение для пагинации

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Весенняя капсула",
      "description": "Капсула на весну",
      "season_id": 3,
      "season_name": "Демисезон",
      "items": [...],
      "outfits": [...],
      "items_count": 10,
      "outfits_count": 5,
      "created_at": "2024-01-15 10:30:45",
      "updated_at": "2024-01-15 10:30:45"
    }
  ],
  "count": 1
}
```

### Получить капсулу по ID

```
GET /api/capsules/{id}
```

**Параметры пути:**
- `id` (integer, обязательно) - ID капсулы

**Ответ:** Аналогичен элементу массива в списке капсул

### Создать капсулу

```
POST /capsules
Content-Type: application/x-www-form-urlencoded
```

**Поля формы:**
- `name` (string, обязательно) - Название капсулы (макс. 200 символов)
- `description` (string, опционально) - Описание (макс. 1000 символов)
- `season_id` (integer, опционально) - ID сезона
- `item_ids` (array или string, опционально) - Массив ID вещей или строка через запятую
- `outfit_ids` (array или string, опционально) - Массив ID образов или строка через запятую

**Ответ:**
```json
{
  "success": true,
  "message": "Капсула успешно создана",
  "data": {
    "id": 1,
    "capsule": {...},
    "redirect_url": "/capsules"
  }
}
```

### Обновить капсулу

```
POST /capsules/{id}
PUT /capsules/{id}
PATCH /capsules/{id}
Content-Type: application/x-www-form-urlencoded
```

**Параметры пути:**
- `id` (integer, обязательно) - ID капсулы

**Поля формы:** (все опциональны)
- Аналогичны полям при создании

**Ответ:**
```json
{
  "success": true,
  "message": "Капсула успешно обновлена",
  "data": {...}
}
```

### Удалить капсулу

```
DELETE /api/capsules/{id}
POST /capsules/{id}/delete
```

**Параметры пути:**
- `id` (integer, обязательно) - ID капсулы

**Ответ:**
```json
{
  "success": true,
  "message": "Капсула успешно удалена"
}
```

### Сгенерировать образы из капсулы

```
POST /capsules/{id}/generate-outfits
Content-Type: application/x-www-form-urlencoded
```

**Параметры пути:**
- `id` (integer, обязательно) - ID капсулы

**Поля формы:**
- `count` (integer, опционально) - Количество образов (1-50, по умолчанию: 5)

**Ответ:**
```json
{
  "success": true,
  "message": "Образы успешно сгенерированы",
  "data": {
    "generated_count": 5,
    "requested_count": 5,
    "capsule": {...}
  }
}
```

---

## Теги (Tags)

### Получить список тегов

```
GET /api/tags
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Офис",
      "color": "#3B82F6",
      "is_system": true,
      "user_id": null
    }
  ],
  "count": 10
}
```

### Получить сгруппированные теги

```
GET /api/tags/grouped
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "system": [
      {
        "id": 1,
        "name": "Офис",
        "color": "#3B82F6"
      }
    ],
    "user": [
      {
        "id": 8,
        "name": "Мой тег",
        "color": "#FF0000"
      }
    ]
  }
}
```

### Поиск тегов

```
GET /api/tags/search?query=оф
```

**Параметры запроса:**
- `query` (string, обязательно, мин. 2 символа) - Поисковый запрос

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Офис",
      "color": "#3B82F6"
    }
  ],
  "query": "оф"
}
```

### Получить популярные теги

```
GET /api/tags/popular
```

**Параметры запроса:**
- `limit` (integer, опционально) - Количество тегов (по умолчанию: 10)

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Офис",
      "color": "#3B82F6",
      "usage_count": 15
    }
  ]
}
```

### Получить теги вещи

```
GET /api/tags/item/{id}
```

**Параметры пути:**
- `id` (integer, обязательно) - ID вещи

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Офис",
      "color": "#3B82F6"
    }
  ],
  "item_id": 1
}
```

### Получить теги образа

```
GET /api/tags/outfit/{id}
```

**Параметры пути:**
- `id` (integer, обязательно) - ID образа

**Ответ:**
```json
{
  "success": true,
  "data": [...],
  "outfit_id": 1
}
```

### Создать тег

```
POST /api/tags
Content-Type: application/x-www-form-urlencoded
```

**Поля формы:**
- `name` (string, обязательно) - Название тега (макс. 50 символов)
- `color` (string, опционально) - HEX код цвета (по умолчанию: #6B7280)

**Ответ:**
```json
{
  "success": true,
  "message": "Тег успешно создан",
  "data": {
    "id": 8,
    "name": "Мой тег",
    "color": "#FF0000",
    "is_system": false,
    "user_id": 1
  }
}
```

**Ошибки:**
- `409` - Тег с таким названием уже существует

### Обновить тег

```
PUT /api/tags/{id}
POST /api/tags/{id}
PATCH /api/tags/{id}
Content-Type: application/x-www-form-urlencoded
```

**Параметры пути:**
- `id` (integer, обязательно) - ID тега

**Поля формы:**
- `name` (string, обязательно) - Название тега
- `color` (string, опционально) - HEX код цвета

**Примечание:** Системные теги нельзя редактировать

**Ответ:**
```json
{
  "success": true,
  "message": "Тег успешно обновлен",
  "data": {...}
}
```

### Удалить тег

```
DELETE /api/tags/{id}
POST /api/tags/{id}
```

**Параметры пути:**
- `id` (integer, обязательно) - ID тега

**Примечание:** Системные теги нельзя удалить

**Ответ:**
```json
{
  "success": true,
  "message": "Тег успешно удален"
}
```

### Привязать тег к вещи

```
POST /api/tags/item/{id}/attach
Content-Type: application/x-www-form-urlencoded
```

**Параметры пути:**
- `id` (integer, обязательно) - ID вещи

**Поля формы:**
- `tag_id` (integer, обязательно) - ID тега

**Ответ:**
```json
{
  "success": true,
  "message": "Тег успешно привязан к вещи"
}
```

### Отвязать тег от вещи

```
DELETE /api/tags/item/{itemId}/{tagId}
POST /api/tags/item/{itemId}/detach
Content-Type: application/x-www-form-urlencoded
```

**Параметры пути:**
- `itemId` (integer, обязательно) - ID вещи
- `tagId` (integer, обязательно) - ID тега (для DELETE метода)

**Поля формы (для POST):**
- `tag_id` (integer, обязательно) - ID тега

**Ответ:**
```json
{
  "success": true,
  "message": "Тег успешно отвязан от вещи"
}
```

### Привязать тег к образу

```
POST /api/tags/outfit/{id}/attach
Content-Type: application/x-www-form-urlencoded
```

**Параметры пути:**
- `id` (integer, обязательно) - ID образа

**Поля формы:**
- `tag_id` (integer, обязательно) - ID тега

**Ответ:**
```json
{
  "success": true,
  "message": "Тег успешно привязан к образу"
}
```

### Отвязать тег от образа

```
DELETE /api/tags/outfit/{outfitId}/{tagId}
POST /api/tags/outfit/{outfitId}/detach
Content-Type: application/x-www-form-urlencoded
```

**Параметры пути:**
- `outfitId` (integer, обязательно) - ID образа
- `tagId` (integer, обязательно) - ID тега (для DELETE метода)

**Поля формы (для POST):**
- `tag_id` (integer, обязательно) - ID тега

**Ответ:**
```json
{
  "success": true,
  "message": "Тег успешно отвязан от образа"
}
```

---

## Аналитика (Analytics)

### Получить общую аналитику

```
GET /api/analytics
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "total_items": 50,
    "total_outfits": 20,
    "total_capsules": 5,
    "category_distribution": [
      {
        "category": "Верх",
        "count": 15,
        "percentage": 30.0
      }
    ],
    "color_distribution": [
      {
        "color": "Черный",
        "count": 10,
        "percentage": 20.0
      }
    ],
    "top_used_items": [
      {
        "id": 1,
        "name": "Синяя куртка",
        "usage_count": 15
      }
    ],
    "season_stats": {
      "Лето": 10,
      "Зима": 15,
      "Демисезон": 20,
      "Всесезон": 5
    },
    "usage_stats": {
      "total_uses": 100,
      "average_per_item": 2.0
    }
  }
}
```

### Получить распределение по категориям

```
GET /api/analytics/categories
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "category": "Верх",
      "count": 15,
      "percentage": 30.0
    }
  ]
}
```

### Получить распределение по цветам

```
GET /api/analytics/colors
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "color": "Черный",
      "count": 10,
      "percentage": 20.0,
      "hex_code": "#000000"
    }
  ]
}
```

### Получить индекс использования вещей

```
GET /api/analytics/usage
```

**Параметры запроса:**
- `filter` (string, опционально) - Фильтр: `all`, `used`, `unused` (по умолчанию: `all`)

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Синяя куртка",
      "usage_count": 15,
      "category_name": "Верхняя одежда"
    }
  ]
}
```

### Получить карту сочетаемости

```
GET /api/analytics/compatibility
```

**Параметры запроса:**
- `limit` (integer, опционально) - Количество пар (по умолчанию: 20)

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "item1_id": 1,
      "item1_name": "Синяя куртка",
      "item2_id": 2,
      "item2_name": "Белые джинсы",
      "times_paired": 10,
      "common_tags": ["Повседневный", "Кэжуал"]
    }
  ]
}
```

---

## Обработка ошибок

### Типичные ошибки

**401 Unauthorized**
```json
{
  "success": false,
  "message": "Требуется авторизация"
}
```

**404 Not Found**
```json
{
  "success": false,
  "message": "Ресурс не найден"
}
```

**400 Bad Request (валидация)**
```json
{
  "success": false,
  "message": "Ошибки валидации",
  "errors": {
    "name": ["Название обязательно для заполнения"],
    "category_id": ["Категория обязательна для выбора"]
  }
}
```

**500 Internal Server Error**
```json
{
  "success": false,
  "message": "Внутренняя ошибка сервера"
}
```

---

## Ограничения

- Максимальный размер изображения: 5MB
- Поддерживаемые форматы изображений: JPEG, PNG, GIF, WebP
- Максимальная длина названия вещи/образа/капсулы: 200 символов
- Максимальная длина описания: 1000 символов
- Максимальная длина названия тега: 50 символов
- Максимальное количество образов для генерации: 50
- По умолчанию возвращается 50 записей в списках

---

## Примеры использования

### JavaScript (Fetch API)

```javascript
// Получить список вещей
fetch('/api/items?category_id=1')
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      console.log(data.data);
    }
  });

// Создать вещь
const formData = new FormData();
formData.append('name', 'Новая вещь');
formData.append('category_id', 1);
formData.append('image', fileInput.files[0]);

fetch('/items', {
  method: 'POST',
  body: formData
})
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      console.log('Вещь создана:', data.data);
    }
  });

// Обновить образ
fetch('/outfits/1', {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/x-www-form-urlencoded'
  },
  body: new URLSearchParams({
    name: 'Обновленное название',
    is_favorite: true
  })
})
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      console.log('Образ обновлен');
    }
  });
```

### cURL

```bash
# Получить список вещей
curl -X GET "http://localhost/api/items?category_id=1" \
  -H "Cookie: PHPSESSID=your_session_id"

# Создать тег
curl -X POST "http://localhost/api/tags" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d "name=Новый тег&color=%23FF0000"

# Удалить вещь
curl -X DELETE "http://localhost/api/items/1" \
  -H "Cookie: PHPSESSID=your_session_id"
```

