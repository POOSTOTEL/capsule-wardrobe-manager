<?php
/**
 * Распределение по категориям
 * 
 * @var array $category_distribution
 * @var string $title
 */
?>

<div class="analytics-page">
    <div class="page-header mb-4">
        <h1>Распределение по категориям</h1>
        <div class="header-actions">
            <a href="/analytics" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Назад к дашборду
            </a>
        </div>
    </div>

    <div class="category-distribution">
        <div class="charts-section mb-4">
            <h3 class="mb-3">
                <i class="fas fa-chart-pie me-2"></i>Диаграмма распределения
            </h3>
            <div class="chart-container" style="max-height: 400px; position: relative; height: 400px;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <div class="distribution-list">
            <h3 class="mb-3">
                <i class="fas fa-list me-2"></i>Детальное распределение
            </h3>
            <?php if (!empty($category_distribution)): ?>
                <?php 
                $totalItems = array_sum(array_column($category_distribution, 'item_count'));
                foreach ($category_distribution as $category): 
                ?>
                    <div class="distribution-item">
                        <div class="distribution-icon">
                            <i class="fas fa-tshirt"></i>
                        </div>
                        <div class="distribution-content">
                            <div class="distribution-header">
                                <span class="distribution-name"><?= htmlspecialchars($category['name']) ?></span>
                                <span class="distribution-percentage"><?= number_format($category['percentage'], 1) ?>%</span>
                            </div>
                            <div class="distribution-bar">
                                <div class="distribution-progress" style="width: <?= $category['percentage'] ?>%"></div>
                            </div>
                            <div class="distribution-count">
                                <?= $category['item_count'] ?> вещей из <?= $totalItems ?> всего
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    У вас пока нет вещей в гардеробе.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoryData = <?= json_encode($category_distribution, JSON_UNESCAPED_UNICODE) ?>;

    if (categoryData && categoryData.length > 0 && typeof Chart !== 'undefined') {
        const ctx = document.getElementById('categoryChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: categoryData.map(item => item.name),
                    datasets: [{
                        data: categoryData.map(item => item.item_count),
                        backgroundColor: [
                            '#4A6FA5', '#6B8E23', '#CD853F', '#4682B4', '#DA70D6',
                            '#20B2AA', '#FF6347', '#FFD700', '#9370DB', '#3CB371',
                            '#FF69B4', '#00CED1', '#FF4500', '#32CD32', '#8A2BE2'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    }
});
</script>
