<?php
/**
 * @var array $items
 * @var array $categories
 * @var array $colors
 * @var array $seasons
 * @var array $tags
 * @var array $filters
 * @var string $title
 */
?>

<div class="page-header mb-4">
    <h1>Мой гардероб</h1>
    <a href="/items/create" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Добавить вещь
    </a>
</div>

    <!-- Фильтры -->
    <?php include __DIR__ . '/_filters.php'; ?>

    <!-- Список вещей -->
    <?php if (empty($items)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            У вас пока нет вещей в гардеробе. 
            <a href="/items/create" class="alert-link">Добавьте первую вещь</a>
        </div>
    <?php else: ?>
        <?php include __DIR__ . '/_grid.php'; ?>
    <?php endif; ?>

<script src="/assets/js/items.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация фильтров
    if (typeof ItemFilters !== 'undefined') {
        ItemFilters.init();
    }
});
</script>
