<?php
/**
 * Форма создания новой вещи
 * 
 * @var array $categories
 * @var array $colors
 * @var array $seasons
 * @var array $tags
 * @var string $title
 */
?>

<div class="item-form">
    <div class="page-header mb-4">
        <h1>Добавить вещь</h1>
        <a href="/items" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Назад к списку
        </a>
    </div>

    <form id="item-form" method="POST" action="/items" enctype="multipart/form-data">
        <!-- Основная информация -->
        <div class="form-section">
            <h2 class="section-title">Основная информация</h2>

            <div class="mb-3">
                <label for="item-name" class="form-label">
                    Название <span class="text-danger">*</span>
                </label>
                <input type="text" 
                       id="item-name" 
                       name="name" 
                       class="form-control" 
                       placeholder="Например: Синяя джинсовая куртка"
                       required
                       maxlength="200">
                <div class="invalid-feedback" id="name-error"></div>
            </div>

            <div class="mb-3">
                <label for="item-category" class="form-label">
                    Категория <span class="text-danger">*</span>
                </label>
                <select id="item-category" name="category_id" class="form-control" required>
                    <option value="">Выберите категорию</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>">
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback" id="category-error"></div>
            </div>
        </div>

        <!-- Изображение -->
        <div class="form-section">
            <h2 class="section-title">Фотография</h2>

            <div class="image-upload" id="image-upload-area">
                <input type="file" 
                       id="item-image" 
                       name="image" 
                       accept="image/jpeg,image/png,image/gif,image/webp"
                       required
                       style="display: none;">
                
                <div id="upload-placeholder" class="upload-placeholder">
                    <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                    <p>Нажмите для загрузки изображения</p>
                    <small class="text-muted">JPEG, PNG, GIF или WebP (макс. 5MB)</small>
                </div>

                <div id="upload-preview" class="upload-preview" style="display: none;">
                    <img id="preview-image" src="" alt="Предпросмотр">
                    <button type="button" class="upload-remove" id="remove-image">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="invalid-feedback" id="image-error"></div>
        </div>

        <!-- Атрибуты -->
        <div class="form-section">
            <h2 class="section-title">Атрибуты</h2>

            <div class="mb-3">
                <label for="item-color" class="form-label">Цвет</label>
                <select id="item-color" name="color_id" class="form-control">
                    <option value="">Не указан</option>
                    <?php foreach ($colors as $color): ?>
                        <option value="<?= $color['id'] ?>" 
                                data-hex="<?= htmlspecialchars($color['hex_code']) ?>">
                            <?= htmlspecialchars($color['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="item-season" class="form-label">Сезон</label>
                <select id="item-season" name="season_id" class="form-control">
                    <option value="">Не указан</option>
                    <?php foreach ($seasons as $season): ?>
                        <option value="<?= $season['id'] ?>">
                            <?= htmlspecialchars($season['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Теги -->
        <div class="form-section">
            <h2 class="section-title">Теги</h2>
            <?php 
            $selectedTags = [];
            include __DIR__ . '/_tag_selector.php'; 
            ?>
        </div>

        <!-- Заметки -->
        <div class="form-section">
            <h2 class="section-title">Заметки</h2>
            <div class="mb-3">
                <label for="item-notes" class="form-label">Дополнительная информация</label>
                <textarea id="item-notes" 
                          name="notes" 
                          class="form-control" 
                          rows="4"
                          placeholder="Любые дополнительные заметки о вещи..."
                          maxlength="1000"></textarea>
                <small class="text-muted">Осталось символов: <span id="notes-counter">1000</span></small>
            </div>
        </div>

        <!-- Кнопки действий -->
        <div class="form-actions d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="/items" class="btn btn-outline-secondary">Отмена</a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Сохранить вещь
            </button>
        </div>
    </form>
</div>

<link rel="stylesheet" href="/assets/css/items.css">
<script src="/assets/js/items.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('item-form');
    const imageInput = document.getElementById('item-image');
    const uploadArea = document.getElementById('image-upload-area');
    const uploadPlaceholder = document.getElementById('upload-placeholder');
    const uploadPreview = document.getElementById('upload-preview');
    const previewImage = document.getElementById('preview-image');
    const removeImageBtn = document.getElementById('remove-image');
    const notesTextarea = document.getElementById('item-notes');
    const notesCounter = document.getElementById('notes-counter');

    // Обработка загрузки изображения
    uploadArea.addEventListener('click', function() {
        imageInput.click();
    });

    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Валидация размера
            if (file.size > 5 * 1024 * 1024) {
                alert('Файл слишком большой. Максимальный размер: 5MB');
                return;
            }

            // Валидация типа
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('Неподдерживаемый тип файла. Разрешены: JPEG, PNG, GIF, WebP');
                return;
            }

            // Показываем превью
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                uploadPlaceholder.style.display = 'none';
                uploadPreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    // Удаление изображения
    removeImageBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        imageInput.value = '';
        uploadPlaceholder.style.display = 'block';
        uploadPreview.style.display = 'none';
    });

    // Счетчик символов для заметок
    if (notesTextarea && notesCounter) {
        notesTextarea.addEventListener('input', function() {
            const remaining = 1000 - this.value.length;
            notesCounter.textContent = remaining;
            notesCounter.style.color = remaining < 50 ? 'var(--danger-color)' : 'var(--text-light)';
        });
    }

    // Валидация формы
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Сбрасываем предыдущие ошибки
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        document.querySelectorAll('.invalid-feedback').forEach(el => {
            el.textContent = '';
        });

        // Простая валидация на клиенте
        let isValid = true;

        if (!document.getElementById('item-name').value.trim()) {
            showError('item-name', 'Введите название вещи');
            isValid = false;
        }

        if (!document.getElementById('item-category').value) {
            showError('item-category', 'Выберите категорию');
            isValid = false;
        }

        if (!imageInput.files || !imageInput.files[0]) {
            showError('item-image', 'Загрузите изображение');
            isValid = false;
        }

        if (!isValid) {
            return;
        }

        // Отправляем форму
        const formData = new FormData(form);

        fetch('/items', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(async response => {
            // Логируем статус ответа
            console.log('Response status:', response.status);
            
            // Проверяем Content-Type перед чтением
            const contentType = response.headers.get('content-type') || '';
            const isJson = contentType.includes('application/json');
            
            // Читаем тело ответа только один раз
            if (isJson) {
                const data = await response.json();
                console.log('Response data:', data);
                return data;
            } else {
                // Если не JSON, читаем как текст
                const text = await response.text();
                console.error('Non-JSON response:', text);
                // Пытаемся распарсить как JSON, если это текст с JSON
                try {
                    const data = JSON.parse(text);
                    return data;
                } catch (e) {
                    throw new Error('Сервер вернул неожиданный ответ: ' + text.substring(0, 200));
                }
            }
        })
        .then(data => {
            console.log('Response data:', data);
            
            if (data.success) {
                // Редирект на список всех вещей
                const redirectUrl = data.data?.redirect_url || '/items';
                window.location.href = redirectUrl;
            } else {
                // Показываем ошибки
                if (data.errors) {
                    console.error('Validation errors:', data.errors);
                    Object.keys(data.errors).forEach(field => {
                        showError(`item-${field}`, data.errors[field][0]);
                    });
                } else {
                    console.error('Error response:', data);
                    alert(data.message || 'Ошибка при создании вещи');
                }
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            console.error('Error stack:', error.stack);
            alert('Ошибка при создании вещи: ' + error.message);
        });
    });

    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorDiv = document.getElementById(fieldId.replace('item-', '') + '-error');
        
        if (field) {
            field.classList.add('is-invalid');
        }
        
        if (errorDiv) {
            errorDiv.textContent = message;
        }
    }
});
</script>
