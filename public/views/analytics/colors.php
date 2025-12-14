<?php

?>

<div class="analytics-page">
    <div class="page-header mb-4">
        <h1>Распределение по цветам</h1>
        <div class="header-actions">
            <a href="/analytics" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Назад к дашборду
            </a>
        </div>
    </div>

    <div class="color-distribution">
        <div class="charts-section mb-4">
            <h3 class="mb-3">
                <i class="fas fa-chart-pie me-2"></i>Диаграмма распределения
            </h3>
            <div class="chart-container" style="max-height: 400px; position: relative; height: 400px;">
                <canvas id="colorChart"></canvas>
            </div>
        </div>

        <div class="distribution-list">
            <h3 class="mb-3">
                <i class="fas fa-palette me-2"></i>Детальное распределение
            </h3>
            <?php if (!empty($color_distribution)): ?>
                <?php 
                $totalItems = array_sum(array_column($color_distribution, 'item_count'));
                foreach ($color_distribution as $color): 
                ?>
                    <div class="distribution-item">
                        <div class="distribution-icon" style="background: <?= htmlspecialchars($color['hex_code'] ?? '#CCCCCC') ?>;">
                            <i class="fas fa-circle" style="color: <?= htmlspecialchars($color['hex_code'] ?? '#CCCCCC') ?>;"></i>
                        </div>
                        <div class="distribution-content">
                            <div class="distribution-header">
                                <span class="distribution-name">
                                    <span class="color-swatch" style="background-color: <?= htmlspecialchars($color['hex_code'] ?? '#CCCCCC') ?>;"></span>
                                    <?= htmlspecialchars($color['name']) ?>
                                </span>
                                <span class="distribution-percentage"><?= number_format($color['percentage'], 1) ?>%</span>
                            </div>
                            <div class="distribution-bar">
                                <div class="distribution-progress" 
                                     style="width: <?= $color['percentage'] ?>%; background: <?= htmlspecialchars($color['hex_code'] ?? '#CCCCCC') ?>;"></div>
                            </div>
                            <div class="distribution-count">
                                <?= $color['item_count'] ?> вещей из <?= $totalItems ?> всего
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    У вас пока нет вещей с указанными цветами.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const colorData = <?= json_encode($color_distribution, JSON_UNESCAPED_UNICODE) ?>;

    if (colorData && colorData.length > 0 && typeof Chart !== 'undefined') {
        const ctx = document.getElementById('colorChart');
        if (ctx) {
            new Chart(ctx, {
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

<style>
.color-swatch {
    display: inline-block;
    width: 20px;
    height: 20px;
    border-radius: 4px;
    margin-right: 8px;
    vertical-align: middle;
    border: 1px solid var(--border-color);
}
</style>
