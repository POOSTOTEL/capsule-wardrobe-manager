<?php
/**
 * Конструктор образов (объединенный с созданием)
 * 
 * @var array $items
 * @var array $seasons
 * @var array $tags
 * @var string $title
 */
?>

<div class="outfit-builder">
    <div class="page-header mb-4">
        <h1>Создать образ</h1>
        <a href="/outfits" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Назад к списку
        </a>
    </div>

    <div class="builder-container">
        <!-- Левая панель: Список вещей -->
        <div class="builder-sidebar">
            <div class="sidebar-header">
                <h3>
                    <i class="fas fa-tshirt me-2"></i>Мой гардероб
                </h3>
                <div class="sidebar-filters mb-3">
                    <input type="text" 
                           id="item-search" 
                           class="form-control form-control-sm" 
                           placeholder="Поиск вещей...">
                </div>
            </div>

            <div class="items-list" id="items-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; max-height: 500px; overflow-y: auto;">
                <?php foreach ($items as $item): ?>
                    <div class="builder-item-card" 
                         data-item-id="<?= $item['id'] ?>"
                         data-item-name="<?= htmlspecialchars(strtolower($item['name'])) ?>"
                         data-category="<?= htmlspecialchars(strtolower($item['category_name'] ?? '')) ?>"
                         data-category-id="<?= $item['category_id'] ?? '' ?>"
                         style="position: relative; border: 2px solid #ddd; border-radius: 8px; padding: 8px; cursor: pointer; transition: all 0.3s;">
                        <input type="checkbox" 
                               class="item-checkbox" 
                               data-item-id="<?= $item['id'] ?>"
                               data-category-id="<?= $item['category_id'] ?? '' ?>"
                               style="position: absolute; top: 8px; right: 8px; z-index: 10; width: 20px; height: 20px;">
                        <div class="builder-item-image" style="width: 100%; aspect-ratio: 1; overflow: hidden; border-radius: 4px; margin-bottom: 8px;">
                            <img src="/api/items/<?= $item['id'] ?>/image" 
                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                 loading="lazy"
                                 style="width: 100%; height: 100%; object-fit: cover;"
                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'150\' height=\'150\'%3E%3Crect fill=\'%23ddd\' width=\'150\' height=\'150\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'12\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EНет фото%3C/text%3E%3C/svg%3E';">
                        </div>
                        <div class="builder-item-info" style="text-align: center;">
                            <div class="builder-item-name" title="<?= htmlspecialchars($item['name']) ?>" style="font-size: 0.9rem; font-weight: 500; margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?= htmlspecialchars($item['name']) ?>
                            </div>
                            <?php if (!empty($item['category_name'])): ?>
                                <div class="builder-item-category" style="font-size: 0.75rem; color: #666;">
                                    <i class="fas fa-tag me-1"></i><?= htmlspecialchars($item['category_name']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Центральная панель: Конструктор образа -->
        <div class="builder-main">
            <!-- Форма с информацией об образе -->
            <div class="builder-form-section">
                <h3 class="mb-3">
                    <i class="fas fa-info-circle me-2"></i>Информация об образе
                </h3>
                
                <div class="mb-3">
                    <label for="outfit-name" class="form-label">
                        Название образа <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           id="outfit-name" 
                           name="name" 
                           class="form-control" 
                           placeholder="Например: Деловой образ на осень"
                           required
                           maxlength="200">
                    <div class="invalid-feedback" id="name-error"></div>
                </div>

                <div class="mb-3">
                    <label for="outfit-description" class="form-label">Описание</label>
                    <textarea id="outfit-description" 
                              name="description" 
                              class="form-control" 
                              rows="2"
                              placeholder="Дополнительное описание образа..."
                              maxlength="1000"></textarea>
                    <small class="text-muted">Осталось символов: <span id="description-counter">1000</span></small>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="outfit-season" class="form-label">Сезон</label>
                        <select id="outfit-season" name="season_id" class="form-control">
                            <option value="">Не указан</option>
                            <?php foreach ($seasons as $season): ?>
                                <option value="<?= $season['id'] ?>">
                                    <?= htmlspecialchars($season['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="outfit-formality" class="form-label">Уровень формальности</label>
                        <select id="outfit-formality" name="formality_level" class="form-control">
                            <option value="">Не указан</option>
                            <option value="1">1 - Повседневный</option>
                            <option value="2">2 - Кэжуал</option>
                            <option value="3">3 - Умный кэжуал</option>
                            <option value="4">4 - Деловой</option>
                            <option value="5">5 - Официальный</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" 
                               id="outfit-favorite" 
                               name="is_favorite" 
                               value="1"
                               class="form-check-input">
                        <label for="outfit-favorite" class="form-check-label">
                            <i class="fas fa-star text-warning me-1"></i>Добавить в избранное
                        </label>
                    </div>
                </div>
            </div>

            <!-- Область сборки образа -->
            <div class="outfit-assembly-section">
                <h3 class="mb-3">
                    <i class="fas fa-magic me-2"></i>Соберите образ
                    <span class="badge badge-info ms-2" id="items-count-badge">0 вещей</span>
                </h3>
                
                <p class="text-muted mb-3">
                    Выберите вещи из списка слева, установив галочки. Выбранные вещи появятся ниже.
                </p>

                <div id="selected-items-display" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; margin-bottom: 20px; min-height: 100px; padding: 16px; background: #f8f9fa; border-radius: 8px;">
                    <div style="grid-column: 1 / -1; text-align: center; color: #999; padding: 20px;">
                        Выберите вещи из списка слева
                    </div>
                </div>

                <div class="assembly-zones" style="display: none;">
                    <div class="assembly-zone" 
                         data-zone="top" 
                         data-category="Верх"
                         data-category-id="1">
                        <div class="zone-label">
                            <i class="fas fa-tshirt me-2"></i>Верх
                        </div>
                        <div class="zone-content" id="zone-top" data-zone="top"></div>
                        <div class="zone-empty">Перетащите вещи сюда</div>
                    </div>

                    <div class="assembly-zone" 
                         data-zone="bottom" 
                         data-category="Низ"
                         data-category-id="2">
                        <div class="zone-label">
                            <i class="fas fa-socks me-2"></i>Низ
                        </div>
                        <div class="zone-content" id="zone-bottom" data-zone="bottom"></div>
                        <div class="zone-empty">Перетащите вещи сюда</div>
                    </div>

                    <div class="assembly-zone" 
                         data-zone="shoes" 
                         data-category="Обувь"
                         data-category-id="4">
                        <div class="zone-label">
                            <i class="fas fa-shoe-prints me-2"></i>Обувь
                        </div>
                        <div class="zone-content" id="zone-shoes" data-zone="shoes"></div>
                        <div class="zone-empty">Перетащите вещи сюда</div>
                    </div>

                    <div class="assembly-zone" 
                         data-zone="outerwear" 
                         data-category="Верхняя одежда"
                         data-category-id="5">
                        <div class="zone-label">
                            <i class="fas fa-vest me-2"></i>Верхняя одежда
                        </div>
                        <div class="zone-content" id="zone-outerwear" data-zone="outerwear"></div>
                        <div class="zone-empty">Перетащите вещи сюда</div>
                    </div>

                    <div class="assembly-zone" 
                         data-zone="accessories" 
                         data-category="Аксессуар"
                         data-category-id="6">
                        <div class="zone-label">
                            <i class="fas fa-gem me-2"></i>Аксессуары
                        </div>
                        <div class="zone-content" id="zone-accessories" data-zone="accessories"></div>
                        <div class="zone-empty">Перетащите вещи сюда</div>
                    </div>
                </div>

                <div class="assembly-actions mt-4">
                    <button type="button" class="btn btn-outline-secondary" id="clear-outfit">
                        <i class="fas fa-trash me-2"></i>Очистить все
                    </button>
                    <button type="button" class="btn btn-primary" id="save-outfit">
                        <i class="fas fa-save me-2"></i>Сохранить образ
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Дополнительные стили для конструктора */
.outfit-builder {
    width: 100%;
    max-width: 100%;
}

.outfit-builder .builder-container {
    display: grid !important;
    grid-template-columns: 320px 1fr !important;
    gap: 24px !important;
}

.builder-item-card {
    height: 200px !important;
    min-height: 200px !important;
    max-height: 200px !important;
}

.zone-item {
    height: 200px !important;
    min-height: 200px !important;
    max-height: 200px !important;
}

@media (max-width: 992px) {
    .outfit-builder .builder-container {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('outfit-form') || document.body;
    const descriptionTextarea = document.getElementById('outfit-description');
    const descriptionCounter = document.getElementById('description-counter');
    const itemSearch = document.getElementById('item-search');
    const clearBtn = document.getElementById('clear-outfit');
    const saveBtn = document.getElementById('save-outfit');
    const itemsCountBadge = document.getElementById('items-count-badge');
    
    let draggedElement = null;
    let draggedFromZone = null;

    // Счетчик символов для описания
    if (descriptionTextarea && descriptionCounter) {
        descriptionTextarea.addEventListener('input', function() {
            const remaining = 1000 - this.value.length;
            descriptionCounter.textContent = remaining;
            descriptionCounter.style.color = remaining < 50 ? 'var(--danger-color)' : 'var(--text-light)';
        });
    }

    const selectedItems = new Set();
    const selectedItemsDisplay = document.getElementById('selected-items-display');

    // Поиск вещей
    if (itemSearch) {
        itemSearch.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            document.querySelectorAll('.builder-item-card').forEach(item => {
                const name = item.dataset.itemName || '';
                const category = item.dataset.category || '';
                
                if (name.includes(query) || category.includes(query)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // Обработка выбора вещей через чекбоксы
    function updateSelectedItems() {
        const checkboxes = document.querySelectorAll('.item-checkbox:checked');
        selectedItems.clear();
        checkboxes.forEach(checkbox => {
            selectedItems.add(parseInt(checkbox.dataset.itemId));
        });
        
        updateSelectedItemsDisplay();
        updateItemsCount();
    }

    function updateSelectedItemsDisplay() {
        if (!selectedItemsDisplay) return;
        
        selectedItemsDisplay.innerHTML = '';
        
        if (selectedItems.size === 0) {
            selectedItemsDisplay.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; color: #999; padding: 20px;">Выберите вещи из списка слева</div>';
            return;
        }

        selectedItems.forEach(itemId => {
            const itemCard = document.querySelector(`.builder-item-card[data-item-id="${itemId}"]`);
            if (itemCard) {
                const clone = itemCard.cloneNode(true);
                // Удаляем чекбокс из клона, так как он не нужен в области отображения
                const checkbox = clone.querySelector('.item-checkbox');
                if (checkbox) {
                    checkbox.remove();
                }
                clone.style.border = '2px solid var(--primary-color)';
                clone.style.position = 'relative';
                clone.style.cursor = 'default';
                
                const removeBtn = document.createElement('button');
                removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                removeBtn.style.cssText = 'position: absolute; top: 4px; right: 4px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; z-index: 20; display: flex; align-items: center; justify-content: center;';
                removeBtn.onclick = function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    const checkbox = document.querySelector(`.item-checkbox[data-item-id="${itemId}"]`);
                    if (checkbox) {
                        checkbox.checked = false;
                        updateSelectedItems();
                    }
                };
                clone.appendChild(removeBtn);
                selectedItemsDisplay.appendChild(clone);
            }
        });
    }

    // Обработчики для чекбоксов
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function(e) {
            e.stopPropagation();
            updateSelectedItems();
        });
        
        // Предотвращаем клик на карточку при клике на чекбокс
        checkbox.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
    
    // Также добавляем обработчики для динамически добавленных чекбоксов
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    const checkboxes = node.querySelectorAll ? node.querySelectorAll('.item-checkbox') : [];
                    checkboxes.forEach(checkbox => {
                        if (!checkbox.hasAttribute('data-listener-added')) {
                            checkbox.setAttribute('data-listener-added', 'true');
                            checkbox.addEventListener('change', function(e) {
                                e.stopPropagation();
                                updateSelectedItems();
                            });
                            checkbox.addEventListener('click', function(e) {
                                e.stopPropagation();
                            });
                        }
                    });
                }
            });
        });
    });
    
    const itemsList = document.getElementById('items-list');
    if (itemsList) {
        observer.observe(itemsList, { childList: true, subtree: true });
    }

    // Старый код drag and drop (удаляем или оставляем для совместимости)
    function initDragAndDrop() {
        document.querySelectorAll('.builder-item-card').forEach(item => {
            // Удаляем старые обработчики, если они есть
            const newItem = item.cloneNode(true);
            item.parentNode.replaceChild(newItem, item);
            
            newItem.addEventListener('dragstart', function(e) {
                draggedElement = this.cloneNode(true);
                draggedFromZone = null;
                this.classList.add('dragging');
                this.style.opacity = '0.5';
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', this.outerHTML);
                e.dataTransfer.setData('text/plain', this.dataset.itemId);
            });

            newItem.addEventListener('dragend', function() {
                this.classList.remove('dragging');
                this.style.opacity = '1';
            });
        });
    }
    
    // Инициализируем drag and drop
    initDragAndDrop();

    // Обработка зон
    document.querySelectorAll('.assembly-zone').forEach(zone => {
        const zoneContent = zone.querySelector('.zone-content');
        const zoneEmpty = zone.querySelector('.zone-empty');

        zoneContent.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            zone.classList.add('drag-over');
        });

        zoneContent.addEventListener('dragleave', function() {
            zone.classList.remove('drag-over');
        });

        zoneContent.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            zone.classList.remove('drag-over');
            
            const html = e.dataTransfer.getData('text/html');
            const itemId = e.dataTransfer.getData('text/plain');
            
            if (html && itemId) {
                // Проверяем, не добавлена ли уже эта вещь в эту зону
                const existingItem = zoneContent.querySelector(`[data-item-id="${itemId}"]`);
                if (existingItem) {
                    return; // Вещь уже в зоне
                }
                
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                const droppedItem = tempDiv.querySelector('.builder-item-card');
                
                if (droppedItem) {
                    // Создаем элемент для зоны
                    const zoneItem = droppedItem.cloneNode(true);
                    zoneItem.draggable = true;
                    zoneItem.classList.add('zone-item');
                    zoneItem.classList.remove('dragging');
                    zoneItem.style.opacity = '1';
                    
                    // Добавляем кнопку удаления
                    const removeBtn = document.createElement('button');
                    removeBtn.className = 'zone-item-remove';
                    removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                    removeBtn.title = 'Удалить из образа';
                    removeBtn.type = 'button';
                    removeBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        zoneItem.remove();
                        updateItemsCount();
                        updateZoneEmpty();
                    });
                    
                    zoneItem.appendChild(removeBtn);
                    
                    // Настраиваем drag для элемента в зоне
                    zoneItem.addEventListener('dragstart', function(e) {
                        this.classList.add('dragging');
                        this.style.opacity = '0.5';
                        e.dataTransfer.effectAllowed = 'move';
                        e.dataTransfer.setData('text/html', this.outerHTML);
                        e.dataTransfer.setData('text/plain', this.dataset.itemId);
                    });
                    
                    zoneItem.addEventListener('dragend', function() {
                        this.classList.remove('dragging');
                        this.style.opacity = '1';
                    });
                    
                    zoneContent.appendChild(zoneItem);
                    
                    // Удаляем оригинал, если он был в другой зоне
                    if (draggedFromZone) {
                        const originalItem = draggedFromZone.querySelector(`[data-item-id="${itemId}"]`);
                        if (originalItem && originalItem.closest('.zone-content')) {
                            originalItem.remove();
                        }
                    }
                    
                    updateItemsCount();
                    updateZoneEmpty();
                }
            }
        });
    });

    // Разрешаем перетаскивание обратно в список (удаление из образа)
    const itemsListForDrop = document.getElementById('items-list');
    if (itemsListForDrop) {
        itemsListForDrop.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });

        itemsListForDrop.addEventListener('drop', function(e) {
            e.preventDefault();
            const itemId = e.dataTransfer.getData('text/plain');
            if (itemId) {
                // Удаляем вещь из всех зон
                document.querySelectorAll('.zone-content').forEach(zone => {
                    const item = zone.querySelector(`[data-item-id="${itemId}"]`);
                    if (item) {
                        item.remove();
                    }
                });
                updateItemsCount();
                updateZoneEmpty();
            }
        });
    }

    // Очистка образа
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (confirm('Вы уверены, что хотите очистить весь образ?')) {
                document.querySelectorAll('.zone-content').forEach(zone => {
                    zone.innerHTML = '';
                });
                updateItemsCount();
                updateZoneEmpty();
            }
        });
    }

    // Обновление счетчика вещей
    function updateItemsCount() {
        // Считаем выбранные вещи из чекбоксов
        const count = selectedItems.size;
        if (itemsCountBadge) {
            itemsCountBadge.textContent = count + ' ' + (count === 1 ? 'вещь' : count < 5 ? 'вещи' : 'вещей');
        }
    }

    // Обновление пустых зон
    function updateZoneEmpty() {
        document.querySelectorAll('.assembly-zone').forEach(zone => {
            const zoneContent = zone.querySelector('.zone-content');
            const zoneEmpty = zone.querySelector('.zone-empty');
            if (zoneContent.children.length === 0) {
                zoneEmpty.style.display = 'block';
            } else {
                zoneEmpty.style.display = 'none';
            }
        });
    }

    // Сохранение образа
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            const name = document.getElementById('outfit-name').value.trim();
            
            if (!name) {
                alert('Введите название образа');
                document.getElementById('outfit-name').focus();
                return;
            }

            // Собираем вещи из выбранных чекбоксов
            const itemIds = Array.from(selectedItems);

            if (itemIds.length === 0) {
                alert('Добавьте хотя бы одну вещь в образ');
                return;
            }

            // Подготавливаем данные
            const formData = new FormData();
            formData.append('name', name);
            formData.append('description', document.getElementById('outfit-description').value);
            formData.append('season_id', document.getElementById('outfit-season').value);
            formData.append('formality_level', document.getElementById('outfit-formality').value);
            formData.append('is_favorite', document.getElementById('outfit-favorite').checked ? '1' : '0');
            
            // Добавляем каждый item_id отдельно - PHP автоматически создаст массив из item_ids[]
            itemIds.forEach(itemId => {
                formData.append('item_ids[]', itemId.toString());
            });

            // Добавляем теги
            const tagInput = document.getElementById('tag-ids-input');
            if (tagInput && tagInput.value) {
                const tagIds = tagInput.value.split(',').filter(id => id.trim() !== '');
                if (tagIds.length > 0) {
                    // Отправляем как массив
                    tagIds.forEach(tagId => {
                        formData.append('tag_ids[]', tagId.trim());
                    });
                }
            }

            // Отправляем запрос
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Сохранение...';

            fetch('/outfits', {
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
                    window.location.href = '/outfits';
                } else {
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            const fieldEl = document.getElementById(`outfit-${field}`);
                            if (fieldEl) {
                                fieldEl.classList.add('is-invalid');
                            }
                        });
                        alert('Ошибки валидации. Проверьте форму.');
                    } else {
                        alert(data.message || 'Ошибка при сохранении образа');
                    }
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>Сохранить образ';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка при сохранении образа: ' + error.message);
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>Сохранить образ';
            });
        });
    }

    // Инициализация
    updateItemsCount();
    updateZoneEmpty();
    
    // Проверка инициализации (для отладки)
    console.log('Outfit builder initialized');
    console.log('Items found:', document.querySelectorAll('.builder-item-card').length);
    console.log('Zones found:', document.querySelectorAll('.assembly-zone').length);
    
    // Убеждаемся, что все карточки имеют правильный размер
    setTimeout(() => {
        document.querySelectorAll('.builder-item-card').forEach(card => {
            if (card.offsetHeight !== 200) {
                card.style.height = '200px';
                card.style.minHeight = '200px';
                card.style.maxHeight = '200px';
            }
        });
    }, 100);
});
</script>
