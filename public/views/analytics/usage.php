<?php
/**
 * Индекс использования вещей
 * 
 * @var array $items
 * @var string $filter
 * @var string $title
 */
?>

<div class="analytics-page">
    <div class="page-header mb-4">
        <h1>Индекс использования вещей</h1>
        <div class="header-actions">
            <a href="/analytics" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Назад к дашборду
            </a>
        </div>
    </div>

    <div class="usage-index">
        <div class="usage-filters mb-4">
            <a href="/analytics/usage?filter=all" 
               class="usage-filter <?= $filter === 'all' ? 'active' : '' ?>">
                <i class="fas fa-list me-1"></i>Все вещи
            </a>
            <a href="/analytics/usage?filter=top" 
               class="usage-filter <?= $filter === 'top' ? 'active' : '' ?>">
                <i class="fas fa-star me-1"></i>Топ используемые
            </a>
            <a href="/analytics/usage?filter=unused" 
               class="usage-filter <?= $filter === 'unused' ? 'active' : '' ?>">
                <i class="fas fa-times-circle me-1"></i>Неиспользуемые
            </a>
            <a href="/analytics/usage?filter=bottom" 
               class="usage-filter <?= $filter === 'bottom' ? 'active' : '' ?>">
                <i class="fas fa-arrow-down me-1"></i>Аутсайдеры
            </a>
        </div>

        <?php if (!empty($items)): ?>
            <?php 
            $maxUsage = !empty($items) ? max(array_column($items, 'usage_count')) : 1;
            $avgUsage = !empty($items) ? array_sum(array_column($items, 'usage_count')) / count($items) : 0;
            ?>

            <div class="usage-stats-bar mb-4">
                <div class="stat-item-inline">
                    <span class="stat-label">Максимальное использование:</span>
                    <span class="stat-value"><?= $maxUsage ?></span>
                </div>
                <div class="stat-item-inline">
                    <span class="stat-label">Среднее использование:</span>
                    <span class="stat-value"><?= number_format($avgUsage, 1) ?></span>
                </div>
                <div class="stat-item-inline">
                    <span class="stat-label">Всего вещей:</span>
                    <span class="stat-value"><?= count($items) ?></span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="usage-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Вещь</th>
                            <th style="width: 150px;">Категория</th>
                            <th style="width: 200px;">Использование</th>
                            <th style="width: 100px;">Индекс</th>
                            <th style="width: 100px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $index => $item): ?>
                            <?php 
                            $usagePercentage = $maxUsage > 0 ? ($item['usage_count'] / $maxUsage) * 100 : 0;
                            $isChampion = $item['usage_count'] > $avgUsage;
                            $isOutsider = $item['usage_count'] == 0;
                            ?>
                            <tr class="<?= $isChampion ? 'champion-row' : ($isOutsider ? 'outsider-row' : '') ?>">
                                <td class="usage-rank">
                                    <?php if ($index < 3 && $item['usage_count'] > 0): ?>
                                        <span class="rank-badge rank-<?= $index + 1 ?>">
                                            <?= $index + 1 ?>
                                        </span>
                                    <?php else: ?>
                                        <?= $index + 1 ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="usage-item">
                                        <div class="item-thumb">
                                            <img src="/api/items/<?= $item['id'] ?>/image" 
                                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect fill=\'%23ddd\' width=\'100\' height=\'100\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'10\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EНет фото%3C/text%3E%3C/svg%3E';">
                                        </div>
                                        <div class="item-info">
                                            <div class="item-name">
                                                <a href="/items/<?= $item['id'] ?>">
                                                    <?= htmlspecialchars($item['name']) ?>
                                                </a>
                                                <?php if ($isChampion): ?>
                                                    <span class="badge bg-success ms-2" title="Чемпион использования">
                                                        <i class="fas fa-trophy"></i>
                                                    </span>
                                                <?php elseif ($isOutsider): ?>
                                                    <span class="badge bg-warning ms-2" title="Не используется">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($item['color_name'])): ?>
                                                <div class="item-category">
                                                    <span class="color-badge" style="background-color: <?= htmlspecialchars($item['color_hex'] ?? '#CCCCCC') ?>;"></span>
                                                    <?= htmlspecialchars($item['color_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= htmlspecialchars($item['category_name'] ?? 'Не указана') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="score-bar">
                                        <div class="score-fill" style="width: <?= min(100, $usagePercentage) ?>%"></div>
                                    </div>
                                    <div class="score-value">
                                        <?= $item['usage_count'] ?> раз
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $isChampion ? 'bg-success' : ($isOutsider ? 'bg-warning' : 'bg-primary') ?>">
                                        <?= $item['usage_count'] ?>
                                    </span>
                                </td>
                                <td class="usage-actions">
                                    <a href="/items/<?= $item['id'] ?>" class="btn btn-sm btn-outline-primary" title="Просмотреть">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <?php if ($filter === 'unused'): ?>
                    Отлично! Все ваши вещи используются.
                <?php elseif ($filter === 'top'): ?>
                    У вас пока нет вещей с высоким индексом использования.
                <?php else: ?>
                    У вас пока нет вещей в гардеробе.
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<link rel="stylesheet" href="/assets/css/analytics.css">

<style>
.usage-stats-bar {
    display: flex;
    gap: var(--spacing-lg);
    padding: var(--spacing-md);
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
    flex-wrap: wrap;
}

.stat-item-inline {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
}

.stat-item-inline .stat-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.stat-item-inline .stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary-color);
}

.rank-badge {
    display: inline-block;
    width: 30px;
    height: 30px;
    line-height: 30px;
    text-align: center;
    border-radius: 50%;
    font-weight: 700;
    color: white;
}

.rank-1 {
    background: linear-gradient(135deg, #FFD700, #FFA500);
}

.rank-2 {
    background: linear-gradient(135deg, #C0C0C0, #808080);
}

.rank-3 {
    background: linear-gradient(135deg, #CD7F32, #8B4513);
}

.champion-row {
    background: rgba(40, 167, 69, 0.05);
}

.outsider-row {
    background: rgba(255, 193, 7, 0.05);
}

.color-badge {
    display: inline-block;
    width: 16px;
    height: 16px;
    border-radius: 3px;
    margin-right: 6px;
    vertical-align: middle;
    border: 1px solid var(--border-color);
}
</style>
