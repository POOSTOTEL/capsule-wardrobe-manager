<?php

?>

<div class="filters-panel mb-4">
    <div class="filters-header mb-3">
        <h3 class="mb-0">
            <i class="fas fa-filter me-2"></i>Фильтры
        </h3>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="reset-filters">
            <i class="fas fa-redo me-1"></i>Сбросить
        </button>
    </div>

    <form id="filters-form" method="GET" action="/items">
        <div class="filters-grid">
            
            <div class="filter-group">
                <label for="filter-search" class="form-label">Поиск</label>
                <input type="text" 
                       id="filter-search" 
                       name="search" 
                       class="form-control" 
                       placeholder="Название вещи..."
                       value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
            </div>

            
            <div class="filter-group">
                <label for="filter-category" class="form-label">Категория</label>
                <select id="filter-category" name="category_id" class="form-control">
                    <option value="">Все категории</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" 
                                <?= ($filters['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            
            <div class="filter-group">
                <label for="filter-color" class="form-label">Цвет</label>
                <select id="filter-color" name="color_id" class="form-control">
                    <option value="">Все цвета</option>
                    <?php foreach ($colors as $color): ?>
                        <option value="<?= $color['id'] ?>" 
                                <?= ($filters['color_id'] ?? '') == $color['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($color['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            
            <div class="filter-group">
                <label for="filter-season" class="form-label">Сезон</label>
                <select id="filter-season" name="season_id" class="form-control">
                    <option value="">Все сезоны</option>
                    <?php foreach ($seasons as $season): ?>
                        <option value="<?= $season['id'] ?>" 
                                <?= ($filters['season_id'] ?? '') == $season['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($season['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            
            <div class="filter-group">
                <label for="filter-order" class="form-label">Сортировка</label>
                <select id="filter-order" name="order_by" class="form-control">
                    <option value="created_at" <?= ($filters['order_by'] ?? 'created_at') == 'created_at' ? 'selected' : '' ?>>
                        По дате добавления
                    </option>
                    <option value="name" <?= ($filters['order_by'] ?? '') == 'name' ? 'selected' : '' ?>>
                        По названию
                    </option>
                    <option value="usage_count" <?= ($filters['order_by'] ?? '') == 'usage_count' ? 'selected' : '' ?>>
                        По использованию
                    </option>
                </select>
            </div>
        </div>

        <div class="filters-actions mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search me-2"></i>Применить фильтры
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    if (typeof TagManager !== 'undefined') {
        TagManager.init().then(() => {
            const container = document.getElementById('filter-tag-selector-container');
            const hiddenInput = document.getElementById('filter-tag-ids');

            if (container && hiddenInput) {
                const tagSelector = new TagSelector({
                    element: container,
                    maxTags: 10,
                    allowCreate: false,
                    onChange: function(selectedTags) {
                        const tagIds = selectedTags.map(tag => tag.id).join(',');
                        hiddenInput.value = tagIds;
                    }
                });

                
                const initialTagIds = hiddenInput.value
                    .split(',')
                    .filter(id => id.trim() !== '')
                    .map(id => parseInt(id));

                if (initialTagIds.length > 0) {
                    tagSelector.setSelectedTags(initialTagIds);
                }
            }
        });
    }

    
    const resetBtn = document.getElementById('reset-filters');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            window.location.href = '/items';
        });
    }
});
</script>
