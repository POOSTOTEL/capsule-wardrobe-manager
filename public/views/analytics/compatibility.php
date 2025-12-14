<?php

?>

<div class="analytics-page">
    <div class="page-header mb-4">
        <h1>Карта сочетаемости</h1>
        <div class="header-actions">
            <a href="/analytics" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Назад к дашборду
            </a>
        </div>
    </div>

    <div class="compatibility-map">
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle me-2"></i>
            Карта показывает, какие вещи чаще всего комбинируются друг с другом в ваших образах.
        </div>

        <?php if (!empty($compatibility)): ?>
            <div class="compatibility-controls mb-4">
                <div class="controls-info">
                    <span class="text-muted">
                        Показано <?= count($compatibility) ?> самых частых сочетаний
                    </span>
                </div>
            </div>

            <div class="compatibility-list">
                <?php 
                $maxPairs = !empty($compatibility) ? max(array_column($compatibility, 'times_paired')) : 1;
                foreach ($compatibility as $index => $pair): 
                    $strength = ($pair['times_paired'] / $maxPairs) * 100;
                ?>
                    <div class="compatibility-item" data-strength="<?= $strength ?>">
                        <div class="compatibility-rank">
                            <?php if ($index < 3): ?>
                                <span class="rank-badge rank-<?= $index + 1 ?>">
                                    <?= $index + 1 ?>
                                </span>
                            <?php else: ?>
                                <span class="rank-number"><?= $index + 1 ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="compatibility-items">
                            <div class="compatibility-item-card">
                                <div class="item-image-compat">
                                    <img src="/api/items/<?= $pair['item1_id'] ?>/image" 
                                         alt="<?= htmlspecialchars($pair['item1_name']) ?>"
                                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect fill=\'%23ddd\' width=\'100\' height=\'100\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'10\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EНет фото%3C/text%3E%3C/svg%3E';">
                                </div>
                                <div class="item-info-compat">
                                    <div class="item-name-compat">
                                        <a href="/items/<?= $pair['item1_id'] ?>">
                                            <?= htmlspecialchars($pair['item1_name']) ?>
                                        </a>
                                    </div>
                                    <div class="item-meta-compat">
                                        <span class="badge bg-secondary"><?= htmlspecialchars($pair['item1_category'] ?? 'Не указана') ?></span>
                                        <?php if (!empty($pair['item1_color'])): ?>
                                            <span class="color-badge-compat" style="background-color: <?= htmlspecialchars($pair['item1_color_hex'] ?? '#CCCCCC') ?>;" 
                                                  title="<?= htmlspecialchars($pair['item1_color']) ?>"></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="compatibility-connector">
                                <div class="connector-line" style="width: <?= $strength ?>%;"></div>
                                <div class="connector-info">
                                    <i class="fas fa-link"></i>
                                    <span class="pair-count"><?= $pair['times_paired'] ?> раз</span>
                                </div>
                            </div>

                            <div class="compatibility-item-card">
                                <div class="item-image-compat">
                                    <img src="/api/items/<?= $pair['item2_id'] ?>/image" 
                                         alt="<?= htmlspecialchars($pair['item2_name']) ?>"
                                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect fill=\'%23ddd\' width=\'100\' height=\'100\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'10\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EНет фото%3C/text%3E%3C/svg%3E';">
                                </div>
                                <div class="item-info-compat">
                                    <div class="item-name-compat">
                                        <a href="/items/<?= $pair['item2_id'] ?>">
                                            <?= htmlspecialchars($pair['item2_name']) ?>
                                        </a>
                                    </div>
                                    <div class="item-meta-compat">
                                        <span class="badge bg-secondary"><?= htmlspecialchars($pair['item2_category'] ?? 'Не указана') ?></span>
                                        <?php if (!empty($pair['item2_color'])): ?>
                                            <span class="color-badge-compat" style="background-color: <?= htmlspecialchars($pair['item2_color_hex'] ?? '#CCCCCC') ?>;" 
                                                  title="<?= htmlspecialchars($pair['item2_color']) ?>"></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="compatibility-strength">
                            <div class="strength-bar">
                                <div class="strength-fill" style="width: <?= $strength ?>%"></div>
                            </div>
                            <div class="strength-label">
                                Сила связи: <?= number_format($strength, 1) ?>%
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                У вас пока нет образов с комбинациями вещей. Создайте несколько образов, чтобы увидеть карту сочетаемости.
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.compatibility-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-lg);
}

.compatibility-item {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
}

.compatibility-item:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.compatibility-rank {
    text-align: center;
    margin-bottom: var(--spacing-md);
}

.rank-number {
    display: inline-block;
    width: 30px;
    height: 30px;
    line-height: 30px;
    text-align: center;
    border-radius: 50%;
    background: var(--bg-secondary);
    color: var(--text-primary);
    font-weight: 600;
}

.compatibility-items {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-md);
}

.compatibility-item-card {
    flex: 1;
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-md);
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.item-image-compat {
    width: 80px;
    height: 80px;
    border-radius: var(--border-radius);
    overflow: hidden;
    flex-shrink: 0;
}

.item-image-compat img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-info-compat {
    flex: 1;
}

.item-name-compat {
    font-weight: 600;
    margin-bottom: var(--spacing-xs);
}

.item-name-compat a {
    color: var(--text-primary);
    text-decoration: none;
}

.item-name-compat a:hover {
    color: var(--primary-color);
}

.item-meta-compat {
    display: flex;
    gap: var(--spacing-xs);
    align-items: center;
    flex-wrap: wrap;
}

.color-badge-compat {
    display: inline-block;
    width: 20px;
    height: 20px;
    border-radius: 4px;
    border: 1px solid var(--border-color);
}

.compatibility-connector {
    position: relative;
    flex: 0 0 150px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 80px;
}

.connector-line {
    position: absolute;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    border-radius: 2px;
    top: 50%;
    left: 0;
    transform: translateY(-50%);
    transition: width 0.3s ease;
}

.connector-info {
    position: relative;
    z-index: 1;
    background: var(--bg-primary);
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--border-radius);
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

.pair-count {
    font-size: 0.875rem;
}

.compatibility-strength {
    margin-top: var(--spacing-md);
}

.strength-bar {
    height: 8px;
    background: var(--border-color);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: var(--spacing-xs);
}

.strength-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    border-radius: 4px;
    transition: width 0.3s ease;
}

.strength-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
    text-align: center;
}

@media (max-width: 768px) {
    .compatibility-items {
        flex-direction: column;
    }

    .compatibility-connector {
        flex: 0 0 auto;
        width: 100%;
        min-height: 50px;
    }

    .connector-line {
        width: 100% !important;
        height: 2px;
        top: 0;
        transform: none;
    }
}
</style>
