<?php
// public/views/layouts/main.php
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Капсульный Гардероб' ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Основные CSS файлы приложения -->
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/items.css">
    <link rel="stylesheet" href="/assets/css/outfits.css">
    <link rel="stylesheet" href="/assets/css/capsules.css">
    <link rel="stylesheet" href="/assets/css/analytics.css">
    <link rel="stylesheet" href="/assets/css/profile.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">

    <!-- Дополнительные стили (если есть) -->
    <?= $styles ?? '' ?>
</head>
<body>
<!-- Навигация -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="/">
            <i class="fas fa-tshirt"></i> Капсульный Гардероб
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/">
                        <i class="fas fa-home"></i> Главная
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/items">
                        <i class="fas fa-tshirt"></i> Вещи
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/outfits">
                        <i class="fas fa-user-tie"></i> Образы
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/capsules">
                        <i class="fas fa-suitcase"></i> Капсулы
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/analytics">
                        <i class="fas fa-chart-bar"></i> Аналитика
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> Профиль
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/profile">Мой профиль</a></li>
                            <li><a class="dropdown-item" href="/settings">Настройки</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/logout">Выйти</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/login">
                            <i class="fas fa-sign-in-alt"></i> Войти
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/register">
                            <i class="fas fa-user-plus"></i> Регистрация
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Flash сообщения -->
<?php if (isset($_SESSION['flash'])): ?>
    <div class="container mt-3">
        <?php foreach ($_SESSION['flash'] as $type => $messages): ?>
            <?php foreach ($messages as $message): ?>
                <div class="alert alert-<?= $type ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?php unset($_SESSION['flash']); ?>
    </div>
<?php endif; ?>

<!-- Основное содержимое -->
<main class="container mt-4">
    <?= $content ?? '' ?>
</main>

<!-- Подвал -->
<footer class="footer mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5>Капсульный Гардероб</h5>
                <p>Система управления гардеробом и создания стильных образов</p>
            </div>
            <div class="col-md-4">
                <h5>Разделы</h5>
                <ul class="list-unstyled">
                    <li><a href="/items">Вещи гардероба</a></li>
                    <li><a href="/outfits">Конструктор образов</a></li>
                    <li><a href="/capsules">Капсулы одежды</a></li>
                    <li><a href="/analytics">Аналитика</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>Контакты</h5>
                <p>По вопросам и предложениям:</p>
                <p>Email: support@capsule-wardrobe.ru</p>
            </div>
        </div>
        <hr>
        <div class="text-center">
            <p>&copy; <?= date('Y') ?> Капсульный Гардероб. Все права защищены.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Custom JS -->
<script src="/assets/js/app.js"></script>

<?= $scripts ?? '' ?>
</body>
</html>