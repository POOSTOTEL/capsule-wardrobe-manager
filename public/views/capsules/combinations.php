<?php

?>

<div class="capsule-combinations">
    <div class="page-header mb-4">
        <h1>Комбинации капсулы: <?= htmlspecialchars($capsule['name']) ?></h1>
        <div class="header-actions">
            <a href="/capsules/<?= $capsule['id'] ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Назад к капсуле
            </a>
        </div>
    </div>

    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        Здесь показаны возможные комбинации вещей и образов из вашей капсулы.
    </div>

    <?php if (empty($combinations)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Не удалось сгенерировать комбинации. Убедитесь, что в капсуле есть вещи или образы.
        </div>
    <?php else: ?>
        <div class="combinations-grid">
            <?php foreach ($combinations as $index => $combination): ?>
                <div class="combination-card">
                    <div class="combination-header">
                        <h3>
                            <?php if ($combination['type'] === 'outfit'): ?>
                                <i class="fas fa-palette me-2"></i>Образ: <?= htmlspecialchars($combination['outfit']['name']) ?>
                            <?php else: ?>
                                <i class="fas fa-magic me-2"></i>Комбинация #<?= $index + 1 ?>
                            <?php endif; ?>
                        </h3>
                    </div>

                    <div class="combination-items">
                        <?php if (!empty($combination['items'])): ?>
                            <div class="items-grid-combination">
                                <?php foreach ($combination['items'] as $item): ?>
                                    <div class="item-card-combination">
                                        <a href="/items/<?= $item['id'] ?>" class="item-link-combination">
                                            <div class="item-image-combination">
                                                <img src="/api/items/<?= $item['id'] ?>/image" 
                                                     alt="<?= htmlspecialchars($item['name']) ?>"
                                                     loading="lazy"
                                                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect fill=\'%23ddd\' width=\'100\' height=\'100\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'10\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EНет фото%3C/text%3E%3C/svg%3E';">
                                            </div>
                                            <div class="item-info-combination">
                                                <div class="item-name-combination"><?= htmlspecialchars($item['name']) ?></div>
                                                <?php if (!empty($item['category_name'])): ?>
                                                    <div class="item-category-combination"><?= htmlspecialchars($item['category_name']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-items-combination">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p>Нет вещей в комбинации</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.capsule-combinations {
    max-width: 1400px;
    margin: 0 auto;
}

.combinations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

.combination-card {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
}

.combination-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.combination-header {
    margin-bottom: var(--spacing-md);
    padding-bottom: var(--spacing-md);
    border-bottom: 1px solid var(--border-color);
}

.combination-header h3 {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
    color: var(--text-primary);
}

.items-grid-combination {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: var(--spacing-md);
}

.item-card-combination {
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
    overflow: hidden;
    transition: all 0.3s ease;
}

.item-card-combination:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.item-link-combination {
    text-decoration: none;
    color: inherit;
    display: block;
}

.item-image-combination {
    aspect-ratio: 1;
    overflow: hidden;
    background: var(--bg-secondary);
}

.item-image-combination img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-info-combination {
    padding: var(--spacing-sm);
}

.item-name-combination {
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: var(--spacing-xs);
    color: var(--text-primary);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.item-category-combination {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.no-items-combination {
    text-align: center;
    padding: var(--spacing-lg);
    color: var(--text-light);
}

@media (max-width: 768px) {
    .combinations-grid {
        grid-template-columns: 1fr;
    }

    .items-grid-combination {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    }
}
</style>
