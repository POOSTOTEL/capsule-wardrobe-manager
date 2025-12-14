<?php

?>

<div class="page-header mb-4">
    <h1>Мои капсулы</h1>
    <div class="header-actions">
        <a href="/capsules/create" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Создать капсулу
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

        <form id="filters-form" method="GET" action="/capsules">
            <div class="filters-grid">
                
                <div class="filter-group">
                    <label for="filter-search" class="form-label">Поиск</label>
                    <input type="text" 
                           id="filter-search" 
                           name="search" 
                           class="form-control" 
                           placeholder="Название капсулы..."
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
                    <label for="filter-order" class="form-label">Сортировка</label>
                    <select id="filter-order" name="order_by" class="form-control">
                        <option value="created_at" <?= ($filters['order_by'] ?? 'created_at') == 'created_at' ? 'selected' : '' ?>>
                            По дате создания
                        </option>
                        <option value="name" <?= ($filters['order_by'] ?? '') == 'name' ? 'selected' : '' ?>>
                            По названию
                        </option>
                        <option value="updated_at" <?= ($filters['order_by'] ?? '') == 'updated_at' ? 'selected' : '' ?>>
                            По дате обновления
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

    
    <?php if (empty($capsules)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            У вас пока нет капсул. 
            <a href="/capsules/create" class="alert-link">Создайте первую капсулу</a>
        </div>
    <?php else: ?>
        <div class="capsules-grid">
            <?php foreach ($capsules as $capsule): ?>
                <div class="capsule-card" data-capsule-id="<?= $capsule['id'] ?>">
                    <a href="/capsules/<?= $capsule['id'] ?>" class="capsule-link">
                        <div class="capsule-header">
                            <h3 class="capsule-name">
                                <?= htmlspecialchars($capsule['name']) ?>
                            </h3>
                            <div class="capsule-meta">
                                <?php if (!empty($capsule['season_name'])): ?>
                                    <span class="capsule-season">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= htmlspecialchars($capsule['season_name']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="capsule-content">
                            <div class="capsule-stats">
                                <?php if (!empty($capsule['items'])): ?>
                                    <div class="stat-item">
                                        <i class="fas fa-tshirt me-1"></i>
                                        <span><?= count($capsule['items']) ?> вещей</span>
                                    </div>
                                <?php else: ?>
                                    <div class="stat-item">
                                        <i class="fas fa-tshirt me-1"></i>
                                        <span>0 вещей</span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($capsule['outfits'])): ?>
                                    <div class="stat-item">
                                        <i class="fas fa-user-tie me-1"></i>
                                        <span><?= count($capsule['outfits']) ?> образов</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($capsule['description'])): ?>
                            <div class="capsule-description">
                                <?= htmlspecialchars(mb_substr($capsule['description'], 0, 100)) ?>
                                <?= mb_strlen($capsule['description']) > 100 ? '...' : '' ?>
                            </div>
                        <?php endif; ?>
                    </a>

                    <div class="capsule-actions">
                        <a href="/capsules/<?= $capsule['id'] ?>/combinations" 
                           class="btn btn-sm btn-outline-info"
                           title="Комбинации">
                            <i class="fas fa-magic"></i>
                        </a>
                        <a href="/capsules/<?= $capsule['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" 
                                class="btn btn-sm btn-outline-danger delete-capsule-btn" 
                                data-capsule-id="<?= $capsule['id'] ?>"
                                data-capsule-name="<?= htmlspecialchars($capsule['name']) ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const resetBtn = document.getElementById('reset-filters');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            window.location.href = '/capsules';
        });
    }

    
    document.querySelectorAll('.delete-capsule-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();

            const capsuleId = this.dataset.capsuleId;
            const capsuleName = this.dataset.capsuleName;

            if (!confirm(`Вы уверены, что хотите удалить капсулу "${capsuleName}"?`)) {
                return;
            }

            try {
                const response = await fetch(`/api/capsules/${capsuleId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    const card = this.closest('.capsule-card');
                    if (card) {
                        card.style.transition = 'opacity 0.3s';
                        card.style.opacity = '0';
                        setTimeout(() => card.remove(), 300);
                    }
                    showNotification('Капсула успешно удалена', 'success');
                } else {
                    showNotification(result.message || 'Ошибка при удалении капсулы', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Ошибка при удалении капсулы', 'error');
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
