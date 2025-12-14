<?php
/**
 * Детальная информация о капсуле
 * 
 * @var array $capsule
 * @var string $title
 */
?>

<div class="capsule-detail">
    <div class="page-header mb-4">
        <div>
            <h1><?= htmlspecialchars($capsule['name']) ?></h1>
            <div class="capsule-meta-header">
                <?php if (!empty($capsule['season_name'])): ?>
                    <span class="badge bg-info">
                        <i class="fas fa-calendar me-1"></i>
                        <?= htmlspecialchars($capsule['season_name']) ?>
                    </span>
                <?php endif; ?>
                <span class="text-muted">
                    Создано: <?= date('d.m.Y', strtotime($capsule['created_at'])) ?>
                </span>
            </div>
        </div>
        <div class="header-actions">
            <a href="/capsules/<?= $capsule['id'] ?>/combinations" class="btn btn-info">
                <i class="fas fa-magic me-2"></i>Комбинации
            </a>
            <a href="/capsules/<?= $capsule['id'] ?>/edit" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>Редактировать
            </a>
            <a href="/capsules" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Назад
            </a>
        </div>
    </div>

    <?php if (!empty($capsule['description'])): ?>
        <div class="card p-4 mb-4">
            <h3 class="mb-3">Описание</h3>
            <p class="mb-0"><?= nl2br(htmlspecialchars($capsule['description'])) ?></p>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Вещи -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">
                        <i class="fas fa-tshirt me-2"></i>Вещи в капсуле
                        <span class="badge bg-primary"><?= count($capsule['items']) ?></span>
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($capsule['items'])): ?>
                        <div class="items-grid-detail">
                            <?php foreach ($capsule['items'] as $item): ?>
                                <div class="item-card-detail">
                                    <a href="/items/<?= $item['id'] ?>" class="item-link">
                                        <div class="item-image-detail">
                                            <img src="/api/items/<?= $item['id'] ?>/image" 
                                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                                 loading="lazy"
                                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect fill=\'%23ddd\' width=\'100\' height=\'100\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'10\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EНет фото%3C/text%3E%3C/svg%3E';">
                                        </div>
                                        <div class="item-info-detail">
                                            <div class="item-name-detail"><?= htmlspecialchars($item['name']) ?></div>
                                            <div class="item-meta-detail">
                                                <?php if (!empty($item['category_name'])): ?>
                                                    <span class="item-category-badge"><?= htmlspecialchars($item['category_name']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['color_name'])): ?>
                                                    <span class="item-color-badge" style="background-color: <?= htmlspecialchars($item['color_hex'] ?? '#ccc') ?>">
                                                        <?= htmlspecialchars($item['color_name']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            В капсуле пока нет вещей.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Образы -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">
                        <i class="fas fa-palette me-2"></i>Образы в капсуле
                        <span class="badge bg-primary"><?= count($capsule['outfits']) ?></span>
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($capsule['outfits'])): ?>
                        <div class="outfits-list-detail">
                            <?php foreach ($capsule['outfits'] as $outfit): ?>
                                <div class="outfit-card-detail">
                                    <a href="/outfits/<?= $outfit['id'] ?>" class="outfit-link-detail">
                                        <div class="outfit-preview-detail">
                                            <?php if (!empty($outfit['items'])): ?>
                                                <?php foreach (array_slice($outfit['items'], 0, 4) as $item): ?>
                                                    <div class="preview-item-detail">
                                                        <img src="/api/items/<?= $item['id'] ?>/image" 
                                                             alt="<?= htmlspecialchars($item['name']) ?>"
                                                             loading="lazy"
                                                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect fill=\'%23ddd\' width=\'100\' height=\'100\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'10\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EНет фото%3C/text%3E%3C/svg%3E';">
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if (count($outfit['items']) > 4): ?>
                                                    <div class="preview-more-detail">
                                                        +<?= count($outfit['items']) - 4 ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="outfit-info-detail">
                                            <div class="outfit-name-detail"><?= htmlspecialchars($outfit['name']) ?></div>
                                            <?php if (!empty($outfit['items'])): ?>
                                                <div class="outfit-meta-detail">
                                                    <i class="fas fa-tshirt me-1"></i>
                                                    <?= count($outfit['items']) ?> вещей
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            В капсуле пока нет образов.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Статистика -->
    <div class="card p-4">
        <h3 class="mb-3">Статистика капсулы</h3>
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= count($capsule['items']) ?></div>
                        <div class="stat-label">Вещей</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= count($capsule['outfits']) ?></div>
                        <div class="stat-label">Образов</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= date('d.m.Y', strtotime($capsule['created_at'])) ?></div>
                        <div class="stat-label">Создано</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-sync"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= date('d.m.Y', strtotime($capsule['updated_at'])) ?></div>
                        <div class="stat-label">Обновлено</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="/assets/css/capsules.css">
