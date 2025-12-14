<?php
/**
 * Форма редактирования образа
 * 
 * @var array $outfit
 * @var array $items
 * @var array $seasons
 * @var array $tags
 * @var array $selectedItemIds
 * @var array $selectedTagIds
 * @var string $title
 */
?>

<div class="outfit-form">
    <div class="page-header mb-4">
        <h1>Редактировать образ</h1>
        <a href="/outfits/<?= $outfit['id'] ?>" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Назад к образу
        </a>
    </div>

    <form id="outfit-form" method="POST" action="/outfits/<?= $outfit['id'] ?>">
        <input type="hidden" name="_method" value="PUT">

        <!-- Основная информация -->
        <div class="form-section">
            <h2 class="section-title">Основная информация</h2>

            <div class="mb-3">
                <label for="outfit-name" class="form-label">
                    Название образа <span class="text-danger">*</span>
                </label>
                <input type="text" 
                       id="outfit-name" 
                       name="name" 
                       class="form-control" 
                       value="<?= htmlspecialchars($outfit['name']) ?>"
                       required
                       maxlength="200">
                <div class="invalid-feedback" id="name-error"></div>
            </div>

            <div class="mb-3">
                <label for="outfit-description" class="form-label">Описание</label>
                <textarea id="outfit-description" 
                          name="description" 
                          class="form-control" 
                          rows="3"
                          placeholder="Дополнительное описание образа..."
                          maxlength="1000"><?= htmlspecialchars($outfit['description'] ?? '') ?></textarea>
                <small class="text-muted">Осталось символов: <span id="description-counter"><?= 1000 - mb_strlen($outfit['description'] ?? '') ?></span></small>
            </div>
        </div>

        <!-- Параметры образа -->
        <div class="form-section">
            <h2 class="section-title">Параметры</h2>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="outfit-season" class="form-label">Сезон</label>
                    <select id="outfit-season" name="season_id" class="form-control">
                        <option value="">Не указан</option>
                        <?php foreach ($seasons as $season): ?>
                            <option value="<?= $season['id'] ?>" 
                                    <?= ($outfit['season_id'] ?? '') == $season['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($season['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="outfit-formality" class="form-label">Уровень формальности</label>
                    <select id="outfit-formality" name="formality_level" class="form-control">
                        <option value="">Не указан</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>" 
                                    <?= ($outfit['formality_level'] ?? '') == $i ? 'selected' : '' ?>>
                                <?= $i ?> - <?= ['', 'Повседневный', 'Кэжуал', 'Умный кэжуал', 'Деловой', 'Официальный'][$i] ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" 
                           id="outfit-favorite" 
                           name="is_favorite" 
                           value="1"
                           class="form-check-input"
                           <?= $outfit['is_favorite'] ? 'checked' : '' ?>>
                    <label for="outfit-favorite" class="form-check-label">
                        <i class="fas fa-star text-warning me-1"></i>Добавить в избранное
                    </label>
                </div>
            </div>
        </div>

        <!-- Выбор вещей -->
        <div class="form-section">
            <h2 class="section-title">Вещи в образе</h2>
            <p class="text-muted mb-3">Выберите вещи, которые входят в этот образ</p>

            <div class="items-selector">
                <input type="hidden" name="item_ids" id="selected-item-ids" value="<?= implode(',', $selectedItemIds ?? []) ?>">
                <div class="items-grid-selector" id="items-grid-selector">
                    <?php foreach ($items as $item): ?>
                        <div class="item-selector-card <?= in_array($item['id'], $selectedItemIds ?? []) ? 'selected' : '' ?>" 
                             data-item-id="<?= $item['id'] ?>">
                            <div class="item-selector-image">
                                <img src="/api/items/<?= $item['id'] ?>/image" 
                                     alt="<?= htmlspecialchars($item['name']) ?>"
                                     loading="lazy"
                                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'200\'%3E%3Crect fill=\'%23ddd\' width=\'200\' height=\'200\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'14\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EНет фото%3C/text%3E%3C/svg%3E';">
                                <div class="item-selector-check">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="item-selector-info">
                                <div class="item-selector-name"><?= htmlspecialchars($item['name']) ?></div>
                                <?php if (!empty($item['category_name'])): ?>
                                    <div class="item-selector-category">
                                        <i class="fas fa-tag me-1"></i><?= htmlspecialchars($item['category_name']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="selected-items-info mt-3">
                    <strong>Выбрано вещей: <span id="selected-count"><?= count($selectedItemIds ?? []) ?></span></strong>
                </div>
            </div>
        </div>

        <!-- Теги -->
        <div class="form-section">
            <h2 class="section-title">Теги</h2>
            <?php 
            $selectedTags = $selectedTagIds ?? [];
            include __DIR__ . '/../items/_tag_selector.php'; 
            ?>
        </div>

        <!-- Кнопки действий -->
        <div class="form-actions d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="/outfits/<?= $outfit['id'] ?>" class="btn btn-outline-secondary">Отмена</a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Сохранить изменения
            </button>
        </div>
    </form>
</div>

<link rel="stylesheet" href="/assets/css/outfits.css">

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('outfit-form');
    const selectedItemIdsInput = document.getElementById('selected-item-ids');
    const selectedCountSpan = document.getElementById('selected-count');
    const descriptionTextarea = document.getElementById('outfit-description');
    const descriptionCounter = document.getElementById('description-counter');
    
    // Инициализируем выбранные вещи
    let selectedItems = <?= json_encode($selectedItemIds ?? []) ?>;

    // Обработка выбора вещей
    document.querySelectorAll('.item-selector-card').forEach(card => {
        card.addEventListener('click', function() {
            const itemId = parseInt(this.dataset.itemId);
            const index = selectedItems.indexOf(itemId);

            if (index > -1) {
                selectedItems.splice(index, 1);
                this.classList.remove('selected');
            } else {
                selectedItems.push(itemId);
                this.classList.add('selected');
            }

            updateSelectedItems();
        });
    });

    function updateSelectedItems() {
        selectedItemIdsInput.value = selectedItems.join(',');
        selectedCountSpan.textContent = selectedItems.length;
    }

    // Счетчик символов для описания
    if (descriptionTextarea && descriptionCounter) {
        descriptionTextarea.addEventListener('input', function() {
            const remaining = 1000 - this.value.length;
            descriptionCounter.textContent = remaining;
            descriptionCounter.style.color = remaining < 50 ? 'var(--danger-color)' : 'var(--text-light)';
        });
    }

    // Валидация формы
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        document.querySelectorAll('.invalid-feedback').forEach(el => {
            el.textContent = '';
        });

        let isValid = true;

        if (!document.getElementById('outfit-name').value.trim()) {
            showError('outfit-name', 'Введите название образа');
            isValid = false;
        }

        if (!isValid) {
            return;
        }

        const formData = new FormData(form);
        
        const tagInput = document.querySelector('input[name="tags"]');
        if (tagInput) {
            formData.append('tag_ids', tagInput.value);
        }

        fetch('/outfits/<?= $outfit['id'] ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(async response => {
            const contentType = response.headers.get('content-type') || '';
            const isJson = contentType.includes('application/json');
            
            if (isJson) {
                return await response.json();
            } else {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Сервер вернул неожиданный ответ');
                }
            }
        })
        .then(data => {
            if (data.success) {
                window.location.href = '/outfits/<?= $outfit['id'] ?>';
            } else {
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        showError(`outfit-${field}`, data.errors[field][0]);
                    });
                } else {
                    alert(data.message || 'Ошибка при обновлении образа');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка при обновлении образа: ' + error.message);
        });
    });

    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorDiv = document.getElementById(fieldId.replace('outfit-', '') + '-error');
        
        if (field) {
            field.classList.add('is-invalid');
        }
        
        if (errorDiv) {
            errorDiv.textContent = message;
        }
    }
});
</script>
