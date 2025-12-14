<?php
/**
 * Компонент сетки для отображения вещей
 * 
 * @var array $items
 */
?>

<div class="items-grid">
    <?php foreach ($items as $item): ?>
        <div class="item-card" data-item-id="<?= $item['id'] ?>">
            <a href="/items/<?= $item['id'] ?>" class="item-link">
                <div class="item-image">
                    <img src="/api/items/<?= $item['id'] ?>/image" 
                         alt="<?= htmlspecialchars($item['name']) ?>"
                         loading="lazy"
                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'300\'%3E%3Crect fill=\'%23ddd\' width=\'300\' height=\'300\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'18\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EНет фото%3C/text%3E%3C/svg%3E';">
                </div>
                <div class="item-info">
                    <h3 class="item-name"><?= htmlspecialchars($item['name']) ?></h3>
                    <div class="item-meta">
                        <?php if (!empty($item['category_name'])): ?>
                            <span class="item-category">
                                <i class="fas fa-tag me-1"></i>
                                <?= htmlspecialchars($item['category_name']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($item['color_name'])): ?>
                            <span class="item-color" style="color: <?= htmlspecialchars($item['color_hex'] ?? '#666') ?>">
                                <i class="fas fa-palette me-1"></i>
                                <?= htmlspecialchars($item['color_name']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($item['season_name'])): ?>
                            <span class="item-season">
                                <i class="fas fa-calendar me-1"></i>
                                <?= htmlspecialchars($item['season_name']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($item['tags'])): ?>
                        <div class="item-tags">
                            <?php foreach (array_slice($item['tags'], 0, 3) as $tag): ?>
                                <span class="item-tag" 
                                      style="background-color: <?= htmlspecialchars($tag['color'] ?? '#6B7280') ?>">
                                    <?= htmlspecialchars($tag['name']) ?>
                                </span>
                            <?php endforeach; ?>
                            <?php if (count($item['tags']) > 3): ?>
                                <span class="item-tag-more">+<?= count($item['tags']) - 3 ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($item['usage_count'] > 0): ?>
                        <div class="item-usage">
                            <i class="fas fa-chart-line me-1"></i>
                            Использовано: <?= $item['usage_count'] ?> раз
                        </div>
                    <?php endif; ?>
                </div>
            </a>
            <div class="item-actions">
                <a href="/items/<?= $item['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-edit"></i>
                </a>
                <button type="button" 
                        class="btn btn-sm btn-outline-danger delete-item-btn" 
                        data-item-id="<?= $item['id'] ?>"
                        data-item-name="<?= htmlspecialchars($item['name']) ?>">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработка удаления вещи
    document.querySelectorAll('.delete-item-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();

            const itemId = this.dataset.itemId;
            const itemName = this.dataset.itemName;

            if (!confirm(`Вы уверены, что хотите удалить вещь "${itemName}"?`)) {
                return;
            }

            try {
                const response = await fetch(`/api/items/${itemId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    // Удаляем карточку из DOM
                    const card = this.closest('.item-card');
                    if (card) {
                        card.style.transition = 'opacity 0.3s';
                        card.style.opacity = '0';
                        setTimeout(() => card.remove(), 300);
                    }

                    // Показываем сообщение об успехе
                    showNotification('Вещь успешно удалена', 'success');
                } else {
                    showNotification(result.message || 'Ошибка при удалении вещи', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Ошибка при удалении вещи', 'error');
            }
        });
    });

    function showNotification(message, type) {
        // Простая реализация уведомлений
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
