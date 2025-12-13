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

    <!-- Дополнительные стили -->
    <?php if (isset($styles) && is_array($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($style); ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <style>
        /* Переменные для совместимости */
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #166088;
            --accent-color: #ff7e5f;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --text-primary: #333333;
            --text-secondary: #666666;
            --text-light: #999999;
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --bg-dark: #343a40;
            --border-color: #dee2e6;
            --border-radius: 6px;
            --border-radius-lg: 10px;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 8px rgba(0,0,0,0.15);
            --shadow-lg: 0 8px 16px rgba(0,0,0,0.2);
            --spacing-xs: 4px;
            --spacing-sm: 8px;
            --spacing-md: 16px;
            --spacing-lg: 24px;
            --spacing-xl: 32px;
        }

        /* Общие стили для карточек и кнопок */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-lg {
            padding: 12px 24px;
            font-size: 1.1rem;
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .card {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            margin-bottom: var(--spacing-lg);
        }

        .card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .alert {
            padding: var(--spacing-md);
            border-radius: var(--border-radius);
            margin-bottom: var(--spacing-md);
            border: 1px solid transparent;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.1);
        }

        .form-label {
            display: block;
            margin-bottom: var(--spacing-sm);
            font-weight: 500;
            color: var(--text-secondary);
        }

        .invalid-feedback {
            color: var(--danger-color);
            font-size: 0.875rem;
            margin-top: var(--spacing-xs);
        }

        .is-invalid {
            border-color: var(--danger-color) !important;
        }

        .mt-1 { margin-top: var(--spacing-xs); }
        .mt-2 { margin-top: var(--spacing-sm); }
        .mt-3 { margin-top: var(--spacing-md); }
        .mt-4 { margin-top: var(--spacing-lg); }
        .mt-5 { margin-top: var(--spacing-xl); }

        .mb-1 { margin-bottom: var(--spacing-xs); }
        .mb-2 { margin-bottom: var(--spacing-sm); }
        .mb-3 { margin-bottom: var(--spacing-md); }
        .mb-4 { margin-bottom: var(--spacing-lg); }
        .mb-5 { margin-bottom: var(--spacing-xl); }

        .p-1 { padding: var(--spacing-xs); }
        .p-2 { padding: var(--spacing-sm); }
        .p-3 { padding: var(--spacing-md); }
        .p-4 { padding: var(--spacing-lg); }
        .p-5 { padding: var(--spacing-xl); }

        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }

        .d-grid { display: grid; }
        .gap-2 { gap: var(--spacing-sm); }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="container">
        <a href="/" class="navbar-brand">
            <i class="fas fa-tshirt me-2"></i>Капсульный Гардероб
        </a>
        <div class="navbar-nav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/" class="nav-link">Главная</a>
                <a href="/items" class="nav-link">Вещи</a>
                <a href="/outfits" class="nav-link">Образы</a>
                <a href="/capsules" class="nav-link">Капсулы</a>
                <a href="/analytics" class="nav-link">Аналитика</a>
                <a href="/profile" class="nav-link">
                    <i class="fas fa-user me-1"></i>Профиль
                </a>
                <a href="/logout" class="nav-link">Выход</a>
            <?php else: ?>
                <a href="/" class="nav-link">Главная</a>
                <a href="/login" class="nav-link">Вход</a>
                <a href="/register" class="nav-link">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container">
    <?php
    // Отображение флеш-сообщений
    if (isset($_SESSION['flash'])):
        foreach ($_SESSION['flash'] as $type => $messages):
            foreach ($messages as $message): ?>
                <div class="alert alert-<?php echo $type; ?> mt-3">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endforeach;
        endforeach;
        unset($_SESSION['flash']);
    endif;
    ?>

    <?php echo $content ?? ''; ?>
</main>

<footer class="footer">
    <div class="container">
        <p class="text-center mb-0">&copy; <?php echo date('Y'); ?> Капсульный Гардероб. Все права защищены.</p>
    </div>
</footer>
</body>
</html>