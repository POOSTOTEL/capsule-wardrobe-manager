/**
 * charts.js - Утилиты для работы с диаграммами
 */

// Инициализация всех диаграмм на странице
document.addEventListener('DOMContentLoaded', function() {
    // Автоматическая инициализация диаграмм, если Chart.js загружен
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    }
});

/**
 * Инициализация всех диаграмм
 */
function initializeCharts() {
    // Инициализация круговых диаграмм
    const doughnutCharts = document.querySelectorAll('canvas[data-chart="doughnut"]');
    doughnutCharts.forEach(canvas => {
        const data = JSON.parse(canvas.dataset.chartData || '[]');
        if (data.length > 0) {
            createDoughnutChart(canvas, data);
        }
    });

    // Инициализация линейных диаграмм
    const lineCharts = document.querySelectorAll('canvas[data-chart="line"]');
    lineCharts.forEach(canvas => {
        const data = JSON.parse(canvas.dataset.chartData || '[]');
        if (data.length > 0) {
            createLineChart(canvas, data);
        }
    });

    // Инициализация столбчатых диаграмм
    const barCharts = document.querySelectorAll('canvas[data-chart="bar"]');
    barCharts.forEach(canvas => {
        const data = JSON.parse(canvas.dataset.chartData || '[]');
        if (data.length > 0) {
            createBarChart(canvas, data);
        }
    });
}

/**
 * Создание круговой диаграммы
 */
function createDoughnutChart(canvas, data) {
    const labels = data.map(item => item.name || item.label);
    const values = data.map(item => item.value || item.count || item.item_count);
    const colors = data.map(item => item.color || item.hex_code || getRandomColor());

    return new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return label + ': ' + value + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Создание линейной диаграммы
 */
function createLineChart(canvas, data) {
    const labels = data.map(item => item.label || item.name);
    const values = data.map(item => item.value || item.count);

    return new Chart(canvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Значение',
                data: values,
                borderColor: '#4A6FA5',
                backgroundColor: 'rgba(74, 111, 165, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Создание столбчатой диаграммы
 */
function createBarChart(canvas, data) {
    const labels = data.map(item => item.label || item.name);
    const values = data.map(item => item.value || item.count);

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Значение',
                data: values,
                backgroundColor: '#4A6FA5',
                borderColor: '#4A6FA5',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Генерация случайного цвета
 */
function getRandomColor() {
    const colors = [
        '#4A6FA5', '#6B8E23', '#CD853F', '#4682B4', '#DA70D6',
        '#20B2AA', '#FF6347', '#FFD700', '#9370DB', '#3CB371',
        '#FF69B4', '#00CED1', '#FF4500', '#32CD32', '#8A2BE2'
    ];
    return colors[Math.floor(Math.random() * colors.length)];
}

/**
 * Экспорт диаграммы в изображение
 */
function exportChart(chartId, filename = 'chart') {
    const canvas = document.getElementById(chartId);
    if (!canvas) {
        console.error('Canvas not found:', chartId);
        return;
    }

    const url = canvas.toDataURL('image/png');
    const link = document.createElement('a');
    link.download = filename + '.png';
    link.href = url;
    link.click();
}

