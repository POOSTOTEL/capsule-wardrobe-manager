<?php
/**
 * Детальная страница вещи
 * 
 * @var array $item
 * @var string $title
 */
?>

<div class="item-detail">
    <div class="detail-header mb-4">
        <h1><?= htmlspecialchars($item['name']) ?></h1>
        <div class="detail-actions">
            <a href="/items/<?= $item['id'] ?>/edit" class="btn btn-outline-primary">
                <i class="fas fa-edit me-2"></i>Редактировать
            </a>
            <a href="/items" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Назад к списку
            </a>
        </div>
    </div>

    <div class="detail-image">
        <img src="/api/items/<?= $item['id'] ?>/image" 
             alt="<?= htmlspecialchars($item['name']) ?>"
             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'800\' height=\'600\'%3E%3Crect fill=\'%23ddd\' width=\'800\' height=\'600\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'24\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EНет фото%3C/text%3E%3C/svg%3E';">
    </div>

    <div class="detail-info">
        <div class="info-grid">
            <?php if (!empty($item['category_name'])): ?>
                <div class="info-item">
                    <span class="info-label">
                        <i class="fas fa-tag me-2"></i>Категория
                    </span>
                    <span class="info-value"><?= htmlspecialchars($item['category_name']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($item['color_name'])): ?>
                <div class="info-item">
                    <span class="info-label">
                        <i class="fas fa-palette me-2"></i>Цвет
                    </span>
                    <span class="info-value">
                        <span class="color-indicator" 
                              style="background-color: <?= htmlspecialchars($item['color_hex'] ?? '#666') ?>"></span>
                        <?= htmlspecialchars($item['color_name']) ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if (!empty($item['season_name'])): ?>
                <div class="info-item">
                    <span class="info-label">
                        <i class="fas fa-calendar me-2"></i>Сезон
                    </span>
                    <span class="info-value"><?= htmlspecialchars($item['season_name']) ?></span>
                </div>
            <?php endif; ?>

            <div class="info-item">
                <span class="info-label">
                    <i class="fas fa-chart-line me-2"></i>Использование
                </span>
                <span class="info-value">
                    <?= $item['usage_count'] ?? 0 ?> раз
                </span>
            </div>

            <div class="info-item">
                <span class="info-label">
                    <i class="fas fa-calendar-plus me-2"></i>Добавлено
                </span>
                <span class="info-value">
                    <?= date('d.m.Y H:i', strtotime($item['created_at'])) ?>
                </span>
            </div>

            <?php if (!empty($item['updated_at']) && $item['updated_at'] != $item['created_at']): ?>
                <div class="info-item">
                    <span class="info-label">
                        <i class="fas fa-calendar-edit me-2"></i>Обновлено
                    </span>
                    <span class="info-value">
                        <?= date('d.m.Y H:i', strtotime($item['updated_at'])) ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($item['tags'])): ?>
            <div class="detail-tags">
                <span class="info-label mb-2 d-block">
                    <i class="fas fa-tags me-2"></i>Теги
                </span>
                <?php foreach ($item['tags'] as $tag): ?>
                    <span class="item-tag" 
                          style="background-color: <?= htmlspecialchars($tag['color'] ?? '#6B7280') ?>">
                        <?= htmlspecialchars($tag['name']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($item['notes'])): ?>
            <div class="detail-notes">
                <h3 class="mb-3">
                    <i class="fas fa-sticky-note me-2"></i>Заметки
                </h3>
                <p><?= nl2br(htmlspecialchars($item['notes'])) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<link rel="stylesheet" href="/assets/css/items.css">
