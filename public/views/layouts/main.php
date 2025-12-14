<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? 'Капсульный Гардероб'); ?></title>

    <!-- Подключение Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Основные стили -->
    <link rel="stylesheet" href="/assets/css/app.css">
    
    <!-- Адаптивные стили -->
    <link rel="stylesheet" href="/assets/css/responsive.css">

    <!-- Дополнительные стили -->
    <?php if (isset($styles) && is_array($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($style); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
<nav class="navbar">
    <div class="container">
        <div class="navbar-header">
            <a href="/" class="navbar-brand">
                <i class="fas fa-tshirt"></i>
                <span class="brand-text">Капсульный Гардероб</span>
            </a>
        </div>
        <div class="navbar-nav" id="navbar-nav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Главная</span>
                </a>
                <a href="/items" class="nav-link">
                    <i class="fas fa-tshirt"></i>
                    <span>Вещи</span>
                </a>
                <a href="/outfits" class="nav-link">
                    <i class="fas fa-user-tie"></i>
                    <span>Образы</span>
                </a>
                <a href="/capsules" class="nav-link">
                    <i class="fas fa-suitcase"></i>
                    <span>Капсулы</span>
                </a>
                <a href="/analytics" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Аналитика</span>
                </a>
                <a href="/profile" class="nav-link">
                    <i class="fas fa-user"></i>
                    <span>Профиль</span>
                </a>
                <a href="/logout" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Выход</span>
                </a>
            <?php else: ?>
                <a href="/" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Главная</span>
                </a>
                <a href="/login" class="nav-link">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Вход</span>
                </a>
                <a href="/register" class="nav-link">
                    <i class="fas fa-user-plus"></i>
                    <span>Регистрация</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="main-content">
    <div class="container">
        <?php
        // Отображение флеш-сообщений
        if (isset($_SESSION['flash'])):
            foreach ($_SESSION['flash'] as $type => $messages):
                foreach ($messages as $message): ?>
                    <div class="alert alert-<?php echo $type; ?> mt-3" role="alert" style="display: flex; align-items: center; position: relative; padding-right: 40px;">
                        <i class="fas fa-<?php 
                            echo $type === 'success' ? 'check-circle' : 
                                ($type === 'error' || $type === 'danger' ? 'exclamation-circle' : 
                                ($type === 'warning' ? 'exclamation-triangle' : 'info-circle')); 
                        ?>" style="font-size: 1.2rem; margin-right: 12px; flex-shrink: 0;"></i>
                        <div style="flex: 1;">
                            <div class="alert-message"><?php echo htmlspecialchars($message); ?></div>
                        </div>
                        <button type="button" class="alert-close" aria-label="Закрыть" onclick="this.parentElement.remove()" style="position: absolute; top: 8px; right: 8px; background: none; border: none; font-size: 1.2rem; cursor: pointer; opacity: 0.7; padding: 4px 8px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endforeach;
            endforeach;
            unset($_SESSION['flash']);
        endif;
        ?>

        <?php echo $content ?? ''; ?>
    </div>
</main>

<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <p class="footer-text">&copy; <?php echo date('Y'); ?> Капсульный Гардероб. Все права защищены.</p>
            <div class="footer-links">
                <a href="/" class="footer-link">Главная</a>
                <span class="footer-separator">|</span>
                <a href="/items" class="footer-link">Вещи</a>
                <span class="footer-separator">|</span>
                <a href="/outfits" class="footer-link">Образы</a>
            </div>
        </div>
    </div>
</footer>

<script>
// Мобильное меню
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('navbar-toggle');
    const nav = document.getElementById('navbar-nav');
    
    if (toggle && nav) {
        toggle.addEventListener('click', function() {
            nav.classList.toggle('active');
            toggle.classList.toggle('active');
        });
        
        // Закрываем меню при клике вне его
        document.addEventListener('click', function(e) {
            if (!toggle.contains(e.target) && !nav.contains(e.target)) {
                nav.classList.remove('active');
                toggle.classList.remove('active');
            }
        });
    }
});
</script>
</body>
</html>