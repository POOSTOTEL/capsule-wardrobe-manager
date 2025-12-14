<?php
/**
 * Детальная страница образа
 * 
 * @var array $outfit
 * @var string $title
 */
?>

<div class="detail-header mb-4">
        <div class="header-content">
            <h1>
                <?= htmlspecialchars($outfit['name']) ?>
                <?php if ($outfit['is_favorite']): ?>
                    <i class="fas fa-star text-warning ms-2"></i>
                <?php endif; ?>
            </h1>
            <div class="detail-meta">
                <?php if (!empty($outfit['season_name'])): ?>
                    <span class="meta-badge">
                        <i class="fas fa-calendar me-1"></i>
                        <?= htmlspecialchars($outfit['season_name']) ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($outfit['formality_level'])): ?>
                    <span class="meta-badge">
                        <i class="fas fa-suitcase me-1"></i>
                        Уровень формальности: <?= $outfit['formality_level'] ?>/5
                    </span>
                <?php endif; ?>
                <span class="meta-badge">
                    <i class="fas fa-calendar-plus me-1"></i>
                    <?= date('d.m.Y H:i', strtotime($outfit['created_at'])) ?>
                </span>
            </div>
        </div>
        <div class="detail-actions">
            <button type="button" 
                    class="btn btn-outline-warning toggle-favorite-btn" 
                    data-outfit-id="<?= $outfit['id'] ?>"
                    title="<?= $outfit['is_favorite'] ? 'Убрать из избранного' : 'Добавить в избранное' ?>">
                <i class="fas fa-star<?= $outfit['is_favorite'] ? '' : '-o' ?> me-2"></i>
                <?= $outfit['is_favorite'] ? 'В избранном' : 'В избранное' ?>
            </button>
            <a href="/outfits/<?= $outfit['id'] ?>/edit" class="btn btn-outline-primary">
                <i class="fas fa-edit me-2"></i>Редактировать
            </a>
            <a href="/outfits" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Назад к списку
            </a>
        </div>
    </div>

    <?php if (!empty($outfit['description'])): ?>
        <div class="detail-description mb-4">
            <h3 class="mb-3">
                <i class="fas fa-align-left me-2"></i>Описание
            </h3>
            <p><?= nl2br(htmlspecialchars($outfit['description'])) ?></p>
        </div>
    <?php endif; ?>

    <div class="detail-items">
        <h3 class="mb-4">
            <i class="fas fa-tshirt me-2"></i>Вещи в образе (<?= count($outfit['items']) ?>)
        </h3>

        <?php if (empty($outfit['items'])): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                В этом образе пока нет вещей.
            </div>
        <?php else: ?>
            <div class="items-grid-detail">
                <?php foreach ($outfit['items'] as $item): ?>
                    <div class="item-card-detail">
                        <a href="/items/<?= $item['id'] ?>" class="item-link">
                            <div class="item-image-detail">
                                <img src="/api/items/<?= $item['id'] ?>/image" 
                                     alt="<?= htmlspecialchars($item['name']) ?>"
                                     loading="lazy"
                                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'300\'%3E%3Crect fill=\'%23ddd\' width=\'300\' height=\'300\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'18\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EНет фото%3C/text%3E%3C/svg%3E';">
                            </div>
                            <div class="item-info-detail">
                                <h4 class="item-name-detail"><?= htmlspecialchars($item['name']) ?></h4>
                                <div class="item-meta-detail">
                                    <?php if (!empty($item['category_name'])): ?>
                                        <span class="item-category-detail">
                                            <i class="fas fa-tag me-1"></i>
                                            <?= htmlspecialchars($item['category_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['color_name'])): ?>
                                        <span class="item-color-detail" style="color: <?= htmlspecialchars($item['color_hex'] ?? '#666') ?>">
                                            <i class="fas fa-palette me-1"></i>
                                            <?= htmlspecialchars($item['color_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Переключение избранного
    const toggleBtn = document.querySelector('.toggle-favorite-btn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', async function() {
            const outfitId = this.dataset.outfitId;
            const icon = this.querySelector('i');
            const text = this.querySelector('span') || this;

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
                        if (text.textContent) {
                            text.textContent = 'В избранном';
                        }
                        // Добавляем звездочку в заголовок
                        const header = document.querySelector('.detail-header h1');
                        if (header && !header.querySelector('.fa-star')) {
                            header.innerHTML += ' <i class="fas fa-star text-warning ms-2"></i>';
                        }
                    } else {
                        icon.classList.remove('fa-star');
                        icon.classList.add('fa-star-o');
                        this.title = 'Добавить в избранное';
                        if (text.textContent) {
                            text.textContent = 'В избранное';
                        }
                        // Убираем звездочку из заголовка
                        const starIcon = document.querySelector('.detail-header h1 .fa-star');
                        if (starIcon) starIcon.remove();
                    }
                } else {
                    alert(result.message || 'Ошибка при обновлении');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка при обновлении статуса');
            }
        });
    }
});
</script>
