<?php
/**
 * Главная страница аналитики (дашборд)
 * 
 * @var int $total_items
 * @var int $total_outfits
 * @var int $total_capsules
 * @var array $category_distribution
 * @var array $color_distribution
 * @var array $top_used_items
 * @var array $season_stats
 * @var array $usage_stats
 * @var string $title
 */
?>

<div class="analytics-dashboard">
    <div class="page-header mb-4">
        <h1>Аналитика гардероба</h1>
        <div class="header-actions">
            <a href="/analytics/categories" class="btn btn-outline-primary">
                <i class="fas fa-th-large me-2"></i>По категориям
            </a>
            <a href="/analytics/colors" class="btn btn-outline-primary">
                <i class="fas fa-palette me-2"></i>По цветам
            </a>
            <a href="/analytics/usage" class="btn btn-outline-primary">
                <i class="fas fa-chart-line me-2"></i>Использование
            </a>
            <a href="/analytics/compatibility" class="btn btn-outline-primary">
                <i class="fas fa-project-diagram me-2"></i>Сочетаемость
            </a>
        </div>
    </div>

    <!-- Общая статистика -->
    <div class="stats-overview mb-5">
        <div class="overview-card">
            <div class="overview-icon">
                <i class="fas fa-tshirt"></i>
            </div>
            <div class="overview-value"><?= $total_items ?></div>
            <div class="overview-label">Всего вещей</div>
        </div>

        <div class="overview-card">
            <div class="overview-icon">
                <i class="fas fa-palette"></i>
            </div>
            <div class="overview-value"><?= $total_outfits ?></div>
            <div class="overview-label">Всего образов</div>
        </div>

        <div class="overview-card">
            <div class="overview-icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="overview-value"><?= $total_capsules ?></div>
            <div class="overview-label">Всего капсул</div>
        </div>

        <div class="overview-card">
            <div class="overview-icon">
                <i class="fas fa-chart-bar"></i>
            </div>
            <div class="overview-value">
                <?= $usage_stats['used_percentage'] ?? 0 ?>%
            </div>
            <div class="overview-label">Используется</div>
            <div class="overview-change change-positive">
                <i class="fas fa-arrow-up"></i>
                <?= $usage_stats['used_items'] ?? 0 ?> из <?= $usage_stats['total_items'] ?? 0 ?>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Распределение по категориям -->
        <div class="col-md-6 mb-4">
            <div class="charts-section">
                <div class="charts-header">
                    <h3>
                        <i class="fas fa-th-large me-2"></i>Распределение по категориям
                    </h3>
                    <a href="/analytics/categories" class="btn btn-sm btn-outline-primary">
                        Подробнее <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="chart-container" style="height: 400px; position: relative;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Распределение по цветам -->
        <div class="col-md-6 mb-4">
            <div class="charts-section">
                <div class="charts-header">
                    <h3>
                        <i class="fas fa-palette me-2"></i>Распределение по цветам
                    </h3>
                    <a href="/analytics/colors" class="btn btn-sm btn-outline-primary">
                        Подробнее <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="chart-container" style="height: 400px; position: relative;">
                    <canvas id="colorChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Топ используемых вещей -->
    <div class="charts-section mb-4">
        <div class="charts-header">
            <h3>
                <i class="fas fa-star me-2"></i>Топ используемых вещей
            </h3>
            <a href="/analytics/usage" class="btn btn-sm btn-outline-primary">
                Все вещи <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="top-items-list">
            <?php if (!empty($top_used_items)): ?>
                <div class="table-responsive">
                    <table class="usage-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Вещь</th>
                                <th style="width: 150px;">Категория</th>
                                <th style="width: 200px;">Использование</th>
                                <th style="width: 100px;">Индекс</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_used_items as $index => $item): ?>
                                <tr>
                                    <td class="usage-rank"><?= $index + 1 ?></td>
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
                                                </div>
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
                                            <div class="score-fill" style="width: <?= min(100, ($item['usage_percentage'] ?? 0)) ?>%"></div>
                                        </div>
                                        <div class="score-value">
                                            <?= $item['usage_count'] ?> раз
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?= $item['usage_count'] ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    У вас пока нет вещей с использованием.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Статистика по сезонам -->
    <div class="charts-section mb-4">
        <div class="charts-header">
            <h3>
                <i class="fas fa-calendar-alt me-2"></i>Статистика по сезонам
            </h3>
        </div>
        <div class="season-stats-grid">
            <?php if (!empty($season_stats)): ?>
                <?php foreach ($season_stats as $season): ?>
                    <div class="season-stat-card">
                        <div class="season-name">
                            <i class="fas fa-calendar me-2"></i>
                            <?= htmlspecialchars($season['name']) ?>
                        </div>
                        <div class="season-metrics">
                            <div class="metric">
                                <span class="metric-value"><?= $season['items_count'] ?></span>
                                <span class="metric-label">Вещей</span>
                            </div>
                            <div class="metric">
                                <span class="metric-value"><?= $season['outfits_count'] ?></span>
                                <span class="metric-label">Образов</span>
                            </div>
                            <div class="metric">
                                <span class="metric-value"><?= $season['capsules_count'] ?></span>
                                <span class="metric-label">Капсул</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Нет данных по сезонам.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="/assets/js/charts.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Данные для диаграмм
    const categoryData = <?= json_encode($category_distribution, JSON_UNESCAPED_UNICODE) ?>;
    const colorData = <?= json_encode($color_distribution, JSON_UNESCAPED_UNICODE) ?>;

    // Диаграмма категорий
    if (categoryData && categoryData.length > 0 && typeof Chart !== 'undefined') {
        const categoryCtx = document.getElementById('categoryChart');
        if (categoryCtx) {
            new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: categoryData.map(item => item.name),
                    datasets: [{
                        data: categoryData.map(item => item.item_count),
                        backgroundColor: [
                            '#4A6FA5', '#6B8E23', '#CD853F', '#4682B4', '#DA70D6',
                            '#20B2AA', '#FF6347', '#FFD700', '#9370DB', '#3CB371'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }

    // Диаграмма цветов
    if (colorData && colorData.length > 0 && typeof Chart !== 'undefined') {
        const colorCtx = document.getElementById('colorChart');
        if (colorCtx) {
            new Chart(colorCtx, {
                type: 'pie',
                data: {
                    labels: colorData.map(item => item.name),
                    datasets: [{
                        data: colorData.map(item => item.item_count),
                        backgroundColor: colorData.map(item => item.hex_code || '#CCCCCC')
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }
});
</script>

<style>
.season-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-md);
}

.season-stat-card {
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
    padding: var(--spacing-md);
    border-left: 4px solid var(--primary-color);
}

.season-name {
    font-weight: 600;
    margin-bottom: var(--spacing-md);
    color: var(--text-primary);
}

.season-metrics {
    display: flex;
    justify-content: space-around;
    gap: var(--spacing-sm);
}

.metric {
    text-align: center;
    flex: 1;
}

.metric-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: var(--spacing-xs);
}

.metric-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
}
</style>
