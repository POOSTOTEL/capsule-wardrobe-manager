<?php

?>

<div class="page-header mb-4">
    <h1>Редактировать капсулу</h1>
    <a href="/capsules/<?= $capsule['id'] ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Назад
    </a>
</div>

<form id="capsule-form" method="POST" action="/capsules/<?= $capsule['id'] ?>">
        <input type="hidden" name="_method" value="PUT">

        <div class="row">
            <div class="col-md-8">
                
                <div class="form-section mb-4">
                    <h3 class="section-title mb-3">
                        <i class="fas fa-info-circle me-2"></i>Основная информация
                    </h3>

                    <div class="mb-3">
                        <label for="name" class="form-label">Название капсулы <span class="text-danger">*</span></label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               class="form-control" 
                               required
                               maxlength="200"
                               value="<?= htmlspecialchars($capsule['name']) ?>"
                               placeholder="Например: Осенняя рабочая капсула">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Описание</label>
                        <textarea id="description" 
                                  name="description" 
                                  class="form-control" 
                                  rows="4"
                                  maxlength="1000"
                                  placeholder="Опишите назначение капсулы, стиль, особенности..."><?= htmlspecialchars($capsule['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="season_id" class="form-label">Сезон</label>
                        <select id="season_id" name="season_id" class="form-control">
                            <option value="">Не указан</option>
                            <?php foreach ($seasons as $season): ?>
                                <option value="<?= $season['id'] ?>" 
                                        <?= ($capsule['season_id'] ?? '') == $season['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($season['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                
                <div class="form-section mb-4">
                    <h3 class="section-title mb-3">
                        <i class="fas fa-tshirt me-2"></i>Вещи в капсуле
                    </h3>

                    <div class="items-selector">
                        <div class="selector-header mb-3">
                            <input type="text" 
                                   id="item-search" 
                                   class="form-control" 
                                   placeholder="Поиск вещей...">
                        </div>

                        <div class="items-grid-selector" id="items-grid">
                            <?php foreach ($items as $item): ?>
                                <div class="item-selector-card" data-item-id="<?= $item['id'] ?>">
                                    <input type="checkbox" 
                                           name="item_ids[]" 
                                           value="<?= $item['id'] ?>" 
                                           id="item-<?= $item['id'] ?>"
                                           class="item-checkbox"
                                           <?= in_array($item['id'], $selectedItemIds) ? 'checked' : '' ?>>
                                    <label for="item-<?= $item['id'] ?>" class="item-label">
                                        <div class="item-image">
                                            <img src="/api/items/<?= $item['id'] ?>/image" 
                                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                                 loading="lazy"
                                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect fill=\'%23ddd\' width=\'100\' height=\'100\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'10\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EНет фото%3C/text%3E%3C/svg%3E';">
                                        </div>
                                        <div class="item-info">
                                            <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                            <div class="item-meta">
                                                <?php if (!empty($item['category_name'])): ?>
                                                    <span class="item-category"><?= htmlspecialchars($item['category_name']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                
                <div class="form-section mb-4">
                    <h3 class="section-title mb-3">
                        <i class="fas fa-palette me-2"></i>Образы в капсуле
                    </h3>

                    <div class="outfits-selector">
                        <div class="selector-header mb-3">
                            <input type="text" 
                                   id="outfit-search" 
                                   class="form-control" 
                                   placeholder="Поиск образов...">
                        </div>

                        <div class="outfits-grid-selector" id="outfits-grid">
                            <?php foreach ($outfits as $outfit): ?>
                                <div class="outfit-selector-card" data-outfit-id="<?= $outfit['id'] ?>">
                                    <input type="checkbox" 
                                           name="outfit_ids[]" 
                                           value="<?= $outfit['id'] ?>" 
                                           id="outfit-<?= $outfit['id'] ?>"
                                           class="outfit-checkbox"
                                           <?= in_array($outfit['id'], $selectedOutfitIds) ? 'checked' : '' ?>>
                                    <label for="outfit-<?= $outfit['id'] ?>" class="outfit-label">
                                        <div class="outfit-preview">
                                            <?php if (!empty($outfit['items'])): ?>
                                                <?php foreach (array_slice($outfit['items'], 0, 3) as $item): ?>
                                                    <div class="preview-item-small">
                                                        <img src="/api/items/<?= $item['id'] ?>/image" 
                                                             alt="<?= htmlspecialchars($item['name']) ?>"
                                                             loading="lazy"
                                                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect fill=\'%23ddd\' width=\'100\' height=\'100\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'10\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EНет фото%3C/text%3E%3C/svg%3E';">
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="outfit-info">
                                            <div class="outfit-name"><?= htmlspecialchars($outfit['name']) ?></div>
                                            <div class="outfit-meta">
                                                <?php if (!empty($outfit['items'])): ?>
                                                    <span class="outfit-items-count">
                                                        <i class="fas fa-tshirt me-1"></i>
                                                        <?= count($outfit['items']) ?> вещей
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                
                <div class="form-section mb-4">
                    <h3 class="section-title mb-3">
                        <i class="fas fa-chart-bar me-2"></i>Статистика
                    </h3>
                    <div class="stats-panel">
                        <div class="stat-item">
                            <span class="stat-label">Выбрано вещей:</span>
                            <span class="stat-value" id="selected-items-count">0</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Выбрано образов:</span>
                            <span class="stat-value" id="selected-outfits-count">0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="form-actions mt-4">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save me-2"></i>Сохранить изменения
            </button>
            <a href="/capsules/<?= $capsule['id'] ?>" class="btn btn-outline-secondary btn-lg">
                Отмена
            </a>
        </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('capsule-form');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const outfitCheckboxes = document.querySelectorAll('.outfit-checkbox');
    const selectedItemsCount = document.getElementById('selected-items-count');
    const selectedOutfitsCount = document.getElementById('selected-outfits-count');
    const itemSearch = document.getElementById('item-search');
    const outfitSearch = document.getElementById('outfit-search');

    
    function updateCounters() {
        const selectedItems = document.querySelectorAll('.item-checkbox:checked').length;
        const selectedOutfits = document.querySelectorAll('.outfit-checkbox:checked').length;
        
        selectedItemsCount.textContent = selectedItems;
        selectedOutfitsCount.textContent = selectedOutfits;
    }

    
    if (itemSearch) {
        itemSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.item-selector-card').forEach(card => {
                const itemName = card.querySelector('.item-name').textContent.toLowerCase();
                card.style.display = itemName.includes(searchTerm) ? 'block' : 'none';
            });
        });
    }

    
    if (outfitSearch) {
        outfitSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.outfit-selector-card').forEach(card => {
                const outfitName = card.querySelector('.outfit-name').textContent.toLowerCase();
                card.style.display = outfitName.includes(searchTerm) ? 'block' : 'none';
            });
        });
    }

    
    itemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateCounters);
    });

    outfitCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateCounters);
    });

    
    updateCounters();

    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        
        try {
            const response = await fetch('/capsules/<?= $capsule['id'] ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success) {
                window.location.href = '/capsules/<?= $capsule['id'] ?>';
            } else {
                alert(result.message || 'Ошибка при обновлении капсулы');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Ошибка при обновлении капсулы');
        }
    });
});
</script>
