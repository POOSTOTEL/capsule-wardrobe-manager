<?php
// public/views/auth/register.php
$errors = $errors ?? [];
$form_data = $form_data ?? [];
?>
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title">Регистрация</h1>
            <p class="auth-subtitle">Создайте аккаунт для управления вашим гардеробом</p>
        </div>

        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors['general'] as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/register" class="auth-form">
            <div class="mb-3">
                <label for="email" class="form-label">Email *</label>
                <input type="email"
                       class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                       id="email"
                       name="email"
                       value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>"
                       required>
                <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback">
                        <?php foreach ($errors['email'] as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="username" class="form-label">Имя пользователя *</label>
                <input type="text"
                       class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>"
                       id="username"
                       name="username"
                       value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>"
                       required>
                <?php if (isset($errors['username'])): ?>
                    <div class="invalid-feedback">
                        <?php foreach ($errors['username'] as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="full_name" class="form-label">Полное имя</label>
                <input type="text"
                       class="form-control"
                       id="full_name"
                       name="full_name"
                       value="<?php echo htmlspecialchars($form_data['full_name'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Пароль *</label>
                <input type="password"
                       class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                       id="password"
                       name="password"
                       required>
                <?php if (isset($errors['password'])): ?>
                    <div class="invalid-feedback">
                        <?php foreach ($errors['password'] as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="form-text">Пароль должен содержать не менее 6 символов</div>
            </div>

            <div class="mb-3">
                <label for="password_confirm" class="form-label">Подтверждение пароля *</label>
                <input type="password"
                       class="form-control <?php echo isset($errors['password_confirm']) ? 'is-invalid' : ''; ?>"
                       id="password_confirm"
                       name="password_confirm"
                       required>
                <?php if (isset($errors['password_confirm'])): ?>
                    <div class="invalid-feedback">
                        <?php foreach ($errors['password_confirm'] as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="d-grid gap-2 mb-3">
                <button type="submit" class="btn btn-primary btn-lg">Зарегистрироваться</button>
            </div>

            <div class="text-center">
                <p class="mb-0">Уже есть аккаунт? <a href="/login">Войти</a></p>
            </div>
        </form>
    </div>
</div>

<style>
    .auth-container {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .auth-card {
        width: 100%;
        max-width: 400px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        padding: 40px;
    }

    .auth-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .auth-title {
        font-size: 28px;
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
    }

    .auth-subtitle {
        color: #666;
        font-size: 16px;
    }
</style>