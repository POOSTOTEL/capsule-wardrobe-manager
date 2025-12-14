<?php

?>

<div class="page-header mb-4">
    <h1>Мои образы</h1>
    <div class="header-actions">
        <a href="/outfits/create" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Создать образ
        </a>
    </div>
</div>


<div class="filters-panel mb-4">
        <div class="filters-header mb-3">
            <h3 class="mb-0">
                <i class="fas fa-filter me-2"></i>Фильтры
            </h3>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="reset-filters">
                <i class="fas fa-redo me-1"></i>Сбросить
            </button>
        </div>

        <form id="filters-form" method="GET" action="/outfits">
            <input type="hidden" name="tag_ids" id="filter-tag-ids" value="<?= htmlspecialchars(implode(',', $filters['tag_ids'] ?? [])) ?>">
            <div class="filters-grid">
                
                <div class="filter-group">
                    <label for="filter-search" class="form-label">Поиск</label>
                    <input type="text" 
                           id="filter-search" 
                           name="search" 
                           class="form-control" 
                           placeholder="Название образа..."
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
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
                    <label for="filter-formality" class="form-label">Формальность</label>
                    <select id="filter-formality" name="formality_level" class="form-control">
                        <option value="">Все уровни</option>
                        <option value="1" <?= ($filters['formality_level'] ?? '') == '1' ? 'selected' : '' ?>>1 - Повседневный</option>
                        <option value="2" <?= ($filters['formality_level'] ?? '') == '2' ? 'selected' : '' ?>>2 - Кэжуал</option>
                        <option value="3" <?= ($filters['formality_level'] ?? '') == '3' ? 'selected' : '' ?>>3 - Умный кэжуал</option>
                        <option value="4" <?= ($filters['formality_level'] ?? '') == '4' ? 'selected' : '' ?>>4 - Деловой</option>
                        <option value="5" <?= ($filters['formality_level'] ?? '') == '5' ? 'selected' : '' ?>>5 - Официальный</option>
                    </select>
                </div>

                
                <div class="filter-group">
                    <label for="filter-favorite" class="form-label">Избранное</label>
                    <select id="filter-favorite" name="is_favorite" class="form-control">
                        <option value="">Все</option>
                        <option value="1" <?= ($filters['is_favorite'] ?? '') == '1' ? 'selected' : '' ?>>Только избранные</option>
                        <option value="0" <?= ($filters['is_favorite'] ?? '') == '0' ? 'selected' : '' ?>>Не избранные</option>
                    </select>
                </div>

                
                <div class="filter-group">
                    <label for="filter-order" class="form-label">Сортировка</label>
                    <select id="filter-order" name="order_by" class="form-control">
                        <option value="created_at" <?= ($filters['order_by'] ?? 'created_at') == 'created_at' ? 'selected' : '' ?>>
                            По дате создания
                        </option>
                        <option value="name" <?= ($filters['order_by'] ?? '') == 'name' ? 'selected' : '' ?>>
                            По названию
                        </option>
                        <option value="formality_level" <?= ($filters['order_by'] ?? '') == 'formality_level' ? 'selected' : '' ?>>
                            По формальности
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

    
    <?php if (empty($outfits)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            У вас пока нет образов. 
            <a href="/outfits/create" class="alert-link">Создайте первый образ</a>
        </div>
    <?php else: ?>
        <div class="outfits-grid">
            <?php foreach ($outfits as $outfit): ?>
                <div class="outfit-card" data-outfit-id="<?= $outfit['id'] ?>">
                    <a href="/outfits/<?= $outfit['id'] ?>" class="outfit-link">
                        <div class="outfit-header">
                            <h3 class="outfit-name">
                                <?= htmlspecialchars($outfit['name']) ?>
                                <?php if ($outfit['is_favorite']): ?>
                                    <i class="fas fa-star text-warning ms-2"></i>
                                <?php endif; ?>
                            </h3>
                            <div class="outfit-meta">
                                <?php if (!empty($outfit['season_name'])): ?>
                                    <span class="outfit-season">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= htmlspecialchars($outfit['season_name']) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($outfit['formality_level'])): ?>
                                    <span class="outfit-formality">
                                        <i class="fas fa-suitcase me-1"></i>
                                        <?= $outfit['formality_level'] ?>/5
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="outfit-items">
                            <?php if (!empty($outfit['items'])): ?>
                                <div class="items-preview">
                                    <?php foreach (array_slice($outfit['items'], 0, 4) as $item): ?>
                                        <div class="item-preview">
                                            <img src="/api/items/<?= $item['id'] ?>/image" 
                                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                                 loading="lazy"
                                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect fill=\'%23ddd\' width=\'100\' height=\'100\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'10\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EНет фото%3C/text%3E%3C/svg%3E';">
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($outfit['items']) > 4): ?>
                                        <div class="item-preview-more">
                                            +<?= count($outfit['items']) - 4 ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="items-count">
                                    <i class="fas fa-tshirt me-1"></i>
                                    <?= count($outfit['items']) ?> вещей
                                </div>
                            <?php else: ?>
                                <div class="no-items">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>Нет вещей в образе</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($outfit['tags'])): ?>
                            <div class="outfit-tags">
                                <?php foreach (array_slice($outfit['tags'], 0, 3) as $tag): ?>
                                    <span class="outfit-tag" 
                                          style="background-color: <?= htmlspecialchars($tag['color'] ?? '#6B7280') ?>">
                                        <?= htmlspecialchars($tag['name']) ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if (count($outfit['tags']) > 3): ?>
                                    <span class="outfit-tag-more">+<?= count($outfit['tags']) - 3 ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($outfit['description'])): ?>
                            <div class="outfit-description">
                                <?= htmlspecialchars(mb_substr($outfit['description'], 0, 100)) ?>
                                <?= mb_strlen($outfit['description']) > 100 ? '...' : '' ?>
                            </div>
                        <?php endif; ?>
                    </a>

                    <div class="outfit-actions">
                        <button type="button" 
                                class="btn btn-sm btn-outline-warning toggle-favorite-btn" 
                                data-outfit-id="<?= $outfit['id'] ?>"
                                title="<?= $outfit['is_favorite'] ? 'Убрать из избранного' : 'Добавить в избранное' ?>">
                            <i class="fas fa-star<?= $outfit['is_favorite'] ? '' : '-o' ?>"></i>
                        </button>
                        <a href="/outfits/<?= $outfit['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" 
                                class="btn btn-sm btn-outline-danger delete-outfit-btn" 
                                data-outfit-id="<?= $outfit['id'] ?>"
                                data-outfit-name="<?= htmlspecialchars($outfit['name']) ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const filterTags = document.getElementById('filter-tags');
    const filterTagIds = document.getElementById('filter-tag-ids');
    if (filterTags && filterTagIds) {
        filterTags.addEventListener('change', function() {
            const selected = Array.from(this.selectedOptions).map(opt => opt.value);
            filterTagIds.value = selected.join(',');
        });
    }

    
    const resetBtn = document.getElementById('reset-filters');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            window.location.href = '/outfits';
        });
    }

    
    document.querySelectorAll('.toggle-favorite-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();

            const outfitId = this.dataset.outfitId;
            const icon = this.querySelector('i');

            try {
                const response = await fetch(`/outfits/${outfitId}/favorite`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    if (result.data.is_favorite) {
                        icon.classList.remove('fa-star-o');
                        icon.classList.add('fa-star');
                        this.title = 'Убрать из избранного';
                        this.closest('.outfit-card').querySelector('.outfit-name').innerHTML += ' <i class="fas fa-star text-warning ms-2"></i>';
                    } else {
                        icon.classList.remove('fa-star');
                        icon.classList.add('fa-star-o');
                        this.title = 'Добавить в избранное';
                        const starIcon = this.closest('.outfit-card').querySelector('.outfit-name .fa-star');
                        if (starIcon) starIcon.remove();
                    }
                    showNotification('Статус избранного обновлен', 'success');
                } else {
                    showNotification(result.message || 'Ошибка при обновлении', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Ошибка при обновлении статуса', 'error');
            }
        });
    });

    
    document.querySelectorAll('.delete-outfit-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();

            const outfitId = this.dataset.outfitId;
            const outfitName = this.dataset.outfitName;

            if (!confirm(`Вы уверены, что хотите удалить образ "${outfitName}"?`)) {
                return;
            }

            try {
                const response = await fetch(`/api/outfits/${outfitId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    const card = this.closest('.outfit-card');
                    if (card) {
                        card.style.transition = 'opacity 0.3s';
                        card.style.opacity = '0';
                        setTimeout(() => card.remove(), 300);
                    }
                    showNotification('Образ успешно удален', 'success');
                } else {
                    showNotification(result.message || 'Ошибка при удалении образа', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Ошибка при удалении образа', 'error');
            }
        });
    });

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'}`;
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.style.minWidth = '300px';
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transition = 'opacity 0.3s';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
});
</script>
