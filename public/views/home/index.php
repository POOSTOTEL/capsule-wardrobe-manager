<?php
// public/views/home/index.php
?>
<div class="hero-section">
    <h1 class="hero-title">Добро пожаловать в Капсульный Гардероб!</h1>
    <p class="hero-subtitle">Система управления вашим гардеробом, создания стильных образов и капсул одежды</p>

    <div class="mt-4">
        <a href="/items" class="btn btn-primary btn-lg">
            <i class="fas fa-plus-circle me-2"></i>Начать добавление вещей
        </a>
        <a href="/outfits" class="btn btn-outline-primary btn-lg ms-2">
            <i class="fas fa-user-tie me-2"></i>Мои образы
        </a>
    </div>
</div>

<div class="stats-grid mt-5">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-tshirt"></i>
        </div>
        <div class="stat-value">
            <?php echo isset($_SESSION['user_id']) ? ($stats['total_items'] ?? 0) : '0'; ?>
        </div>
        <div class="stat-label">Всего вещей</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-user-tie"></i>
        </div>
        <div class="stat-value">
            <?php echo isset($_SESSION['user_id']) ? ($stats['total_outfits'] ?? 0) : '0'; ?>
        </div>
        <div class="stat-label">Созданных образов</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-suitcase"></i>
        </div>
        <div class="stat-value">
            <?php echo isset($_SESSION['user_id']) ? ($stats['total_capsules'] ?? 0) : '0'; ?>
        </div>
        <div class="stat-label">Капсул</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-value">
            <?php echo isset($_SESSION['user_id']) ? number_format($stats['wardrobe_usage_percentage'] ?? 0, 1) . '%' : '0%'; ?>
        </div>
        <div class="stat-label">Использования гардероба</div>
    </div>
</div>

<div class="action-grid mt-5">
    <a href="/items/create" class="action-card">
        <div class="action-icon">
            <i class="fas fa-plus-circle"></i>
        </div>
        <div class="action-title">Добавить вещь</div>
        <div class="action-description">Добавьте новую вещь в ваш гардероб</div>
    </a>

    <a href="/outfits" class="action-card">
        <div class="action-icon">
            <i class="fas fa-user-tie"></i>
        </div>
        <div class="action-title">Мои образы</div>
        <div class="action-description">Просмотрите и управляйте вашими образами</div>
    </a>

    <a href="/outfits/create" class="action-card">
        <div class="action-icon">
            <i class="fas fa-magic"></i>
        </div>
        <div class="action-title">Создать образ</div>
        <div class="action-description">Соберите стильный образ с помощью конструктора</div>
    </a>

    <a href="/capsules/create" class="action-card">
        <div class="action-icon">
            <i class="fas fa-suitcase"></i>
        </div>
        <div class="action-title">Создать капсулу</div>
        <div class="action-description">Объедините вещи в тематическую капсулу</div>
    </a>

    <a href="/analytics" class="action-card">
        <div class="action-icon">
            <i class="fas fa-chart-bar"></i>
        </div>
        <div class="action-title">Посмотреть аналитику</div>
        <div class="action-description">Проанализируйте ваш гардероб</div>
    </a>
</div>